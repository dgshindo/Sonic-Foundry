<?php
declare(strict_types=1);

namespace SonicFoundry\Conversation;

use DomainException;
use SonicFoundry\Auth\AuthenticatedUser;
use SonicFoundry\Work\Work;
use SonicFoundry\Work\WorkPillar;
use SonicFoundry\Work\WorkService;
use SonicFoundry\Workflow\PillarWorkflowService;

final class ConversationService
{
    private const MAX_MESSAGE_LENGTH = 6000;

    public function __construct(
        private readonly ConversationRepository $messages,
        private readonly WorkService $works,
        private readonly PillarWorkflowService $workflow,
    ) {
    }

    public function addUserMessage(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
        string $content,
    ): ConversationMessage {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        $this->assertPillarAvailable(
            user: $user,
            workId: $work->id(),
            pillar: $pillar,
        );

        $normalizedContent = trim($content);

        if ($normalizedContent === '') {
            throw new DomainException(
                'Write a message before sending it.'
            );
        }

        if (
            mb_strlen($normalizedContent)
            > self::MAX_MESSAGE_LENGTH
        ) {
            throw new DomainException(
                'Your message may not exceed '
                . self::MAX_MESSAGE_LENGTH
                . ' characters.'
            );
        }

        return $this->messages->create(
            workId: $work->id(),
            pillar: $pillar,
            role: MessageRole::User,
            content: $normalizedContent,
        );
    }

    /**
     * @return list<ConversationMessage>
     */
    public function messagesForWork(
        AuthenticatedUser $user,
        int $workId,
        string $pillarValue,
    ): array {
        $work = $this->works->findOwnedWork(
            workId: $workId,
            user: $user,
        );

        $pillar = $this->resolvePillar(
            $pillarValue
        );

        $this->assertPillarAvailable(
            user: $user,
            workId: $work->id(),
            pillar: $pillar,
        );

        return $this->messages->findForWorkAndPillar(
            workId: $work->id(),
            pillar: $pillar,
        );
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
            throw new DomainException(
                'Select a valid creative pillar.'
            );
        }

        return $pillar;
    }

    private function assertPillarAvailable(
        AuthenticatedUser $user,
        int $workId,
        WorkPillar $pillar,
    ): void {
        $workflow = $this->workflow
            ->pillarForWork(
                user: $user,
                workId: $workId,
                pillarValue: $pillar->value,
            );

        if ($workflow->isLocked()) {
            throw new \DomainException(
                'That creative pillar is not yet available.'
            );
        }
    }
}