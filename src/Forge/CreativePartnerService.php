<?php
declare(strict_types=1);

namespace SonicFoundry\Forge;

use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Conversation\ConversationMessage;
use SonicFoundry\Conversation\ConversationRepository;
use SonicFoundry\Conversation\MessageRole;
use SonicFoundry\Memory\PillarMemory;
use SonicFoundry\Memory\PillarMemoryService;
use SonicFoundry\Work\Work;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;

final class CreativePartnerService
{
    private const MAX_CONTEXT_MESSAGES = 30;

    public function __construct(
        private readonly OpenAIClient $openAI,
        private readonly PromptAssembler $prompts,
        private readonly ConversationRepository $messages,
        private readonly WorkService $works,
        private readonly PillarMemoryService $memory,
    ) {
    }

    /**
     * Generate, stream, and persist a Creative Partner response.
     *
     * @param callable(string): void $onTextDelta
     */
    public function respond(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
        callable $onTextDelta,
    ): ConversationMessage {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        $this->assertPillarAvailable(
            work: $work,
            pillar: $pillar,
        );

        $history = $this->messages
            ->findForWorkAndPillar(
                workId: $work->id(),
                pillar: $pillar,
            );

        if ($history === []) {
            throw new \RuntimeException(
                'A creator message is required before '
                . 'the Creative Partner can respond.'
            );
        }

        $lastMessage = $history[
            array_key_last($history)
        ];

        if (!$lastMessage->isUserMessage()) {
            throw new \RuntimeException(
                'The Creative Partner has already responded.'
            );
        }

        $confirmedMemory = $this->confirmedMemory(
            user: $user,
            workId: $work->id(),
            pillar: $pillar,
        );

        $instructions = $this->buildInstructions(
            work: $work,
            pillar: $pillar,
            creatorFirstName: $user->firstName(),
            confirmedMemory: $confirmedMemory,
        );

        $completeResponse =
            $this->openAI->streamResponse(
                instructions: $instructions,

                input: $this->buildInput(
                    $history
                ),

                onTextDelta: $onTextDelta,
            );

        return $this->messages->create(
            workId: $work->id(),
            pillar: $pillar,
            role: MessageRole::Partner,
            content: $completeResponse,
        );
    }

    /**
     * @param list<ConversationMessage> $history
     *
     * @return list<array{
     *     role: string,
     *     content: string
     * }>
     */
    private function buildInput(
        array $history,
    ): array {
        $limitedHistory = array_slice(
            $history,
            -self::MAX_CONTEXT_MESSAGES
        );

        $input = [];

        foreach ($limitedHistory as $message) {
            if (
                !$message->isUserMessage()
                && !$message->isPartnerMessage()
            ) {
                continue;
            }

            $input[] = [
                'role' => $message->isUserMessage()
                    ? 'user'
                    : 'assistant',

                'content' => $message->content(),
            ];
        }

        return $input;
    }

    private function buildInstructions(
        Work $work,
        WorkPillar $pillar,
        string $creatorFirstName,
        ?PillarMemory $confirmedMemory,
    ): string {
        $promptPaths = [
            'core/creative-partner.md',
            $this->pillarPromptPath(
                $pillar
            ),
        ];

        if ($confirmedMemory !== null) {
            $promptPaths[] =
                'memory/confirmed-context.md';
        }

        $instructions = $this->prompts->assemble(
            promptPaths: $promptPaths,

            variables: [
                'creator_first_name' =>
                    $creatorFirstName,

                'work_title' =>
                    $work->title(),

                'work_type' =>
                    $work->typeLabel(),

                'pillar_name' =>
                    $pillar->label(),
            ],
        );

        if ($confirmedMemory === null) {
            return $instructions;
        }

        return $instructions
            . "\n\n---\n\n"
            . $this->serializeConfirmedMemory(
                $confirmedMemory
            );
    }

    private function confirmedMemory(
        AuthenticatedUser $user,
        int $workId,
        WorkPillar $pillar,
    ): ?PillarMemory {
        $memory = $this->memory->memoryForWork(
            user: $user,
            workId: $workId,
            pillarValue: $pillar->value,
        );

        if (
            $memory === null
            || !$memory->isConfirmed()
        ) {
            return null;
        }

        return $memory;
    }

    private function serializeConfirmedMemory(
        PillarMemory $memory,
    ): string {
        $payload = [
            'pillar' => $memory->pillar()->value,

            'status' => $memory->status()->value,

            'revision' => $memory->revision(),

            'summary' => $memory->summary(),

            'perspective' =>
                $memory->perspective(),

            'core_tension' =>
                $memory->coreTension(),

            'listener_takeaway' =>
                $memory->listenerTakeaway(),

            'themes' => $memory->themes(),

            'key_subjects' =>
                $memory->keySubjects(),
        ];

        $json = json_encode(
            $payload,
            JSON_THROW_ON_ERROR
            | JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );

        return implode(
            "\n",
            [
                '<confirmed_creative_memory>',
                $json,
                '</confirmed_creative_memory>',
            ]
        );
    }

    private function pillarPromptPath(
        WorkPillar $pillar,
    ): string {
        return match ($pillar) {
            WorkPillar::Story =>
                'pillars/story.md',

            WorkPillar::Emotion =>
                'pillars/emotion.md',

            WorkPillar::Identity =>
                'pillars/identity.md',

            WorkPillar::Sound =>
                'pillars/sound.md',

            WorkPillar::Impact =>
                'pillars/impact.md',
        };
    }

    private function resolvePillar(
        string $pillarValue,
    ): WorkPillar {
        $pillar = WorkPillar::tryFrom(
            mb_strtolower(
                trim($pillarValue)
            )
        );

        if ($pillar === null) {
            throw new \RuntimeException(
                'A valid creative pillar is required.'
            );
        }

        return $pillar;
    }

    private function assertPillarAvailable(
        Work $work,
        WorkPillar $pillar,
    ): void {
        /*
         * Work is intentionally retained here.
         * Persistent pillar progress will later determine
         * availability from the Work itself.
         */

        if ($pillar !== WorkPillar::Story) {
            throw new \RuntimeException(
                'That creative pillar is not yet available.'
            );
        }
    }
}