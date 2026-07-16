<?php
declare(strict_types=1);

namespace SonicFoundry\Work;

use DateTimeImmutable;
use PDO;
use RuntimeException;

final class WorkRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function create(
        int $userId,
        string $title,
        WorkType $type,
    ): Work {
        $statement = $this->pdo->prepare(
            '
            INSERT INTO works (
                user_id,
                title,
                work_type,
                status,
                current_pillar
            ) VALUES (
                :user_id,
                :title,
                :work_type,
                :status,
                :current_pillar
            )
            '
        );

        $statement->execute([
            'user_id' => $userId,
            'title' => $title,
            'work_type' => $type->value,
            'status' => WorkStatus::Draft->value,
            'current_pillar' => WorkPillar::Story->value,
        ]);

        $workId = (int) $this->pdo->lastInsertId();

        $work = $this->findByIdForUser(
            workId: $workId,
            userId: $userId,
        );

        if (!$work) {
            throw new RuntimeException(
                'The work was created but could not be reloaded.'
            );
        }

        return $work;
    }

    public function findByIdForUser(
        int $workId,
        int $userId,
    ): ?Work {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                user_id,
                title,
                work_type,
                status,
                current_pillar,
                created_at,
                updated_at
            FROM works
            WHERE id = :id
              AND user_id = :user_id
            LIMIT 1
            '
        );

        $statement->execute([
            'id' => $workId,
            'user_id' => $userId,
        ]);

        $row = $statement->fetch();

        return $row
            ? $this->hydrate($row)
            : null;
    }

    /**
     * @return list<Work>
     */
    public function findAllByUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                user_id,
                title,
                work_type,
                status,
                current_pillar,
                created_at,
                updated_at
            FROM works
            WHERE user_id = :user_id
              AND status <> :archived_status
            ORDER BY updated_at DESC
            '
        );

        $statement->execute([
            'user_id' => $userId,
            'archived_status' => WorkStatus::Archived->value,
        ]);

        $works = [];

        foreach ($statement->fetchAll() as $row) {
            $works[] = $this->hydrate($row);
        }

        return $works;
    }

    private function hydrate(array $row): Work
    {
        return new Work(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            title: (string) $row['title'],
            type: WorkType::from(
                (string) $row['work_type']
            ),
            status: WorkStatus::from(
                (string) $row['status']
            ),
            currentPillar: WorkPillar::from(
                (string) $row['current_pillar']
            ),
            createdAt: new DateTimeImmutable(
                (string) $row['created_at']
            ),
            updatedAt: new DateTimeImmutable(
                (string) $row['updated_at']
            ),
        );
    }
}