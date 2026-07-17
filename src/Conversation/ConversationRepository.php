<?php
declare(strict_types=1);

namespace SonicFoundry\Conversation;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use SonicFoundry\Work\WorkPillar;

final class ConversationRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function create(
        int $workId,
        WorkPillar $pillar,
        MessageRole $role,
        string $content,
    ): ConversationMessage {
        $statement = $this->pdo->prepare(
            '
            INSERT INTO work_messages (
                work_id,
                pillar,
                role,
                content
            ) VALUES (
                :work_id,
                :pillar,
                :role,
                :content
            )
            '
        );

        $statement->execute([
            'work_id' => $workId,
            'pillar' => $pillar->value,
            'role' => $role->value,
            'content' => $content,
        ]);

        $messageId = (int) $this->pdo->lastInsertId();

        $message = $this->findById($messageId);

        if (!$message) {
            throw new RuntimeException(
                'The message was saved but could not be reloaded.'
            );
        }

        return $message;
    }

    /**
     * @return list<ConversationMessage>
     */
    public function findForWorkAndPillar(
        int $workId,
        WorkPillar $pillar,
    ): array {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                work_id,
                pillar,
                role,
                content,
                created_at
            FROM work_messages
            WHERE work_id = :work_id
              AND pillar = :pillar
            ORDER BY id ASC
            '
        );

        $statement->execute([
            'work_id' => $workId,
            'pillar' => $pillar->value,
        ]);

        $messages = [];

        foreach ($statement->fetchAll() as $row) {
            $messages[] = $this->hydrate($row);
        }

        return $messages;
    }

    public function countForWorkAndPillar(
        int $workId,
        WorkPillar $pillar,
    ): int {
        $statement = $this->pdo->prepare(
            '
            SELECT COUNT(*)
            FROM work_messages
            WHERE work_id = :work_id
              AND pillar = :pillar
            '
        );

        $statement->execute([
            'work_id' => $workId,
            'pillar' => $pillar->value,
        ]);

        return (int) $statement->fetchColumn();
    }

    private function findById(
        int $messageId,
    ): ?ConversationMessage {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                work_id,
                pillar,
                role,
                content,
                created_at
            FROM work_messages
            WHERE id = :id
            LIMIT 1
            '
        );

        $statement->execute([
            'id' => $messageId,
        ]);

        $row = $statement->fetch();

        return $row
            ? $this->hydrate($row)
            : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row,
    ): ConversationMessage {
        return new ConversationMessage(
            id: (int) $row['id'],
            workId: (int) $row['work_id'],
            pillar: WorkPillar::from(
                (string) $row['pillar']
            ),
            role: MessageRole::from(
                (string) $row['role']
            ),
            content: (string) $row['content'],
            createdAt: new DateTimeImmutable(
                (string) $row['created_at']
            ),
        );
    }
}