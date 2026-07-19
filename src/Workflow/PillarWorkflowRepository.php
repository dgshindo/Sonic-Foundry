<?php
declare(strict_types=1);

namespace SonicFoundry\Workflow;

use DateTimeImmutable;
use PDO;
use SonicFoundry\Work\WorkPillar;

final class PillarWorkflowRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /**
     * Ensure every pillar has a workflow row.
     *
     * Story begins available. All later pillars begin locked.
     */
    public function initializeForWork(
        int $workId,
    ): void {
        $statement = $this->pdo->prepare(
            '
            INSERT IGNORE INTO work_pillar_workflow (
                work_id,
                pillar,
                status,
                unlocked_at,
                revision
            ) VALUES (
                :work_id,
                :pillar,
                :status,
                :unlocked_at,
                1
            )
            '
        );

        foreach (WorkPillar::cases() as $pillar) {
            $isStory = $pillar === WorkPillar::Story;

            $statement->execute([
                'work_id' => $workId,
                'pillar' => $pillar->value,
                'status' => $isStory
                    ? WorkflowStatus::Available->value
                    : WorkflowStatus::Locked->value,
                'unlocked_at' => $isStory
                    ? (new DateTimeImmutable())
                        ->format('Y-m-d H:i:s')
                    : null,
            ]);
        }
    }

    public function findByWorkAndPillar(
        int $workId,
        WorkPillar $pillar,
    ): ?PillarWorkflow {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                work_id,
                pillar,
                status,
                unlocked_at,
                completed_at,
                revision,
                created_at,
                updated_at
            FROM work_pillar_workflow
            WHERE work_id = :work_id
              AND pillar = :pillar
            LIMIT 1
            '
        );

        $statement->execute([
            'work_id' => $workId,
            'pillar' => $pillar->value,
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $this->hydrate($row)
            : null;
    }

    /**
     * @return list<PillarWorkflow>
     */
    public function findForWork(
        int $workId,
    ): array {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                work_id,
                pillar,
                status,
                unlocked_at,
                completed_at,
                revision,
                created_at,
                updated_at
            FROM work_pillar_workflow
            WHERE work_id = :work_id
            '
        );

        $statement->execute([
            'work_id' => $workId,
        ]);

        $rows = $statement->fetchAll();

        if (!is_array($rows)) {
            return [];
        }

        $workflows = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $workflow = $this->hydrate($row);

            $workflows[
                $workflow->pillar()->value
            ] = $workflow;
        }

        $ordered = [];

        foreach (WorkPillar::cases() as $pillar) {
            if (isset($workflows[$pillar->value])) {
                $ordered[] = $workflows[
                    $pillar->value
                ];
            }
        }

        return $ordered;
    }

    /**
     * Complete the current pillar and unlock the next pillar.
     */
    public function completeAndUnlock(
        PillarWorkflow $current,
        ?PillarWorkflow $next,
    ): PillarWorkflow {
        $this->pdo->beginTransaction();

        try {
            $completed = $this->changeStatus(
                workflow: $current,
                status: WorkflowStatus::Completed,
                setCompletedAt: true,
                setUnlockedAt: false,
            );

            if (
                $next !== null
                && $next->isLocked()
            ) {
                $this->changeStatus(
                    workflow: $next,
                    status: WorkflowStatus::Available,
                    setCompletedAt: false,
                    setUnlockedAt: true,
                );
            }

            $this->pdo->commit();

            return $completed;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $error;
        }
    }

    private function changeStatus(
        PillarWorkflow $workflow,
        WorkflowStatus $status,
        bool $setCompletedAt,
        bool $setUnlockedAt,
    ): PillarWorkflow {
        $nextRevision =
            $workflow->revision() + 1;

        $statement = $this->pdo->prepare(
            '
            UPDATE work_pillar_workflow
            SET
                status = :status,

                unlocked_at = CASE
                    WHEN :set_unlocked_at = 1
                    THEN CURRENT_TIMESTAMP
                    ELSE unlocked_at
                END,

                completed_at = CASE
                    WHEN :set_completed_at = 1
                    THEN CURRENT_TIMESTAMP
                    ELSE completed_at
                END,

                revision = :next_revision

            WHERE id = :id
              AND revision = :current_revision
            '
        );

        $statement->execute([
            'status' => $status->value,

            'set_unlocked_at' =>
                $setUnlockedAt ? 1 : 0,

            'set_completed_at' =>
                $setCompletedAt ? 1 : 0,

            'next_revision' => $nextRevision,

            'id' => $workflow->id(),

            'current_revision' =>
                $workflow->revision(),
        ]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException(
                'Workflow was modified by another request.'
            );
        }

        $updated = $this->findByWorkAndPillar(
            workId: $workflow->workId(),
            pillar: $workflow->pillar(),
        );

        if ($updated === null) {
            throw new \RuntimeException(
                'Updated workflow could not be reloaded.'
            );
        }

        $this->insertRevision(
            $updated
        );

        return $updated;
    }

    private function insertRevision(
        PillarWorkflow $workflow,
    ): void {
        $statement = $this->pdo->prepare(
            '
            INSERT INTO work_pillar_workflow_revisions (
                workflow_id,
                work_id,
                pillar,
                status,
                unlocked_at,
                completed_at,
                revision
            ) VALUES (
                :workflow_id,
                :work_id,
                :pillar,
                :status,
                :unlocked_at,
                :completed_at,
                :revision
            )
            '
        );

        $statement->execute([
            'workflow_id' => $workflow->id(),
            'work_id' => $workflow->workId(),
            'pillar' => $workflow->pillar()->value,
            'status' => $workflow->status()->value,

            'unlocked_at' => $workflow
                ->unlockedAt()
                ?->format('Y-m-d H:i:s'),

            'completed_at' => $workflow
                ->completedAt()
                ?->format('Y-m-d H:i:s'),

            'revision' => $workflow->revision(),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row,
    ): PillarWorkflow {
        return new PillarWorkflow(
            id: (int) $row['id'],

            workId: (int) $row['work_id'],

            pillar: WorkPillar::from(
                (string) $row['pillar']
            ),

            status: WorkflowStatus::from(
                (string) $row['status']
            ),

            unlockedAt:
                $this->nullableDate(
                    $row['unlocked_at'] ?? null
                ),

            completedAt:
                $this->nullableDate(
                    $row['completed_at'] ?? null
                ),

            revision:
                (int) $row['revision'],

            createdAt:
                new DateTimeImmutable(
                    (string) $row['created_at']
                ),

            updatedAt:
                new DateTimeImmutable(
                    (string) $row['updated_at']
                ),
        );
    }

    private function nullableDate(
        mixed $value,
    ): ?DateTimeImmutable {
        if (
            !is_string($value)
            || trim($value) === ''
        ) {
            return null;
        }

        return new DateTimeImmutable(
            $value
        );
    }
}