<?php
declare(strict_types=1);

namespace SonicFoundry\Forge;

use SonicFoundry\AI\OpenAIClient;
use SonicFoundry\AI\PromptAssembler;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Conversation\ConversationMessage;
use SonicFoundry\Conversation\ConversationRepository;
use SonicFoundry\Conversation\MessageRole;
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

        $instructions = $this->buildInstructions(
            work: $work,
            pillar: $pillar,
            creatorFirstName: $user->firstName(),
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
    ): string {
        return $this->prompts->assemble(
            promptPaths: [
                'core/creative-partner.md',
                $this->pillarPromptPath(
                    $pillar
                ),
            ],

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

        if (!$pillar) {
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
         * The Work parameter is intentionally retained.
         * Persistent pillar progress will later determine
         * availability from the Work.
         */

        if ($pillar !== WorkPillar::Story) {
            throw new \RuntimeException(
                'That creative pillar is not yet available.'
            );
        }
    }
}