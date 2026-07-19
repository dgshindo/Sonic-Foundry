<?php
declare(strict_types=1);

namespace SonicFoundry\Progress;

use DateTimeImmutable;
use PDO;
use SonicFoundry\Work\WorkPillar;

final class PillarProgressRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByWorkAndPillar(
        int $workId,
        WorkPillar $pillar,
    ): ?PillarProgress {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                work_id,
                pillar,
                status,
                readiness_score,
                is_ready,
                criteria,
                recommendation,
                revision,
                evaluated_at,
                created_at,
                updated_at
            FROM work_pillar_progress
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

        return $row
            ? $this->hydrate($row)
            : null;
    }

    public function saveEvaluation(
        int $workId,
        WorkPillar $pillar,
        ProgressEvaluation $evaluation,
    ): PillarProgress {
        $this->pdo->beginTransaction();

        try {
            $existing = $this->findByWorkAndPillar(
                workId: $workId,
                pillar: $pillar,
            );

            $progress = $existing === null
                ? $this->insert(
                    workId: $workId,
                    pillar: $pillar,
                    evaluation: $evaluation,
                )
                : $this->update(
                    existing: $existing,
                    evaluation: $evaluation,
                );

            $this->insertRevision(
                $progress
            );

            $this->pdo->commit();

            return $progress;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $error;
        }
    }

    private function insert(
        int $workId,
        WorkPillar $pillar,
        ProgressEvaluation $evaluation,
    ): PillarProgress {
        $statement = $this->pdo->prepare(
            '
            INSERT INTO work_pillar_progress (
                work_id,
                pillar,
                status,
                readiness_score,
                is_ready,
                criteria,
                recommendation,
                revision,
                evaluated_at
            ) VALUES (
                :work_id,
                :pillar,
                :status,
                :readiness_score,
                :is_ready,
                :criteria,
                :recommendation,
                1,
                CURRENT_TIMESTAMP
            )
            '
        );

        $statement->execute([
            'work_id' => $workId,
            'pillar' => $pillar->value,
            'status' => $evaluation->status()->value,
            'readiness_score' =>
                $evaluation->readinessScore(),
            'is_ready' => $evaluation->isReady()
                ? 1
                : 0,
            'criteria' => $this->encodeCriteria(
                $evaluation->criteria()
            ),
            'recommendation' =>
                $evaluation->recommendation(),
        ]);

        return $this->reload(
            $workId,
            $pillar
        );
    }

    private function update(
        PillarProgress $existing,
        ProgressEvaluation $evaluation,
    ): PillarProgress {
        $nextRevision =
            $existing->revision() + 1;

        $statement = $this->pdo->prepare(
            '
            UPDATE work_pillar_progress
            SET
                status = :status,
                readiness_score = :readiness_score,
                is_ready = :is_ready,
                criteria = :criteria,
                recommendation = :recommendation,
                revision = :next_revision,
                evaluated_at = CURRENT_TIMESTAMP
            WHERE id = :id
              AND revision = :current_revision
            '
        );

        $statement->execute([
            'status' => $evaluation->status()->value,
            'readiness_score' =>
                $evaluation->readinessScore(),
            'is_ready' => $evaluation->isReady()
                ? 1
                : 0,
            'criteria' => $this->encodeCriteria(
                $evaluation->criteria()
            ),
            'recommendation' =>
                $evaluation->recommendation(),
            'next_revision' => $nextRevision,
            'id' => $existing->id(),
            'current_revision' =>
                $existing->revision(),
        ]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException(
                'Progress was modified by another request.'
            );
        }

        return $this->reload(
            $existing->workId(),
            $existing->pillar()
        );
    }

    private function insertRevision(
        PillarProgress $progress,
    ): void {
        $statement = $this->pdo->prepare(
            '
            INSERT INTO work_pillar_progress_revisions (
                progress_id,
                work_id,
                pillar,
                status,
                readiness_score,
                is_ready,
                criteria,
                recommendation,
                revision,
                evaluated_at
            ) VALUES (
                :progress_id,
                :work_id,
                :pillar,
                :status,
                :readiness_score,
                :is_ready,
                :criteria,
                :recommendation,
                :revision,
                :evaluated_at
            )
            '
        );

        $statement->execute([
            'progress_id' => $progress->id(),
            'work_id' => $progress->workId(),
            'pillar' => $progress->pillar()->value,
            'status' => $progress->status()->value,
            'readiness_score' =>
                $progress->readinessScore(),
            'is_ready' => $progress->isReady()
                ? 1
                : 0,
            'criteria' => $this->encodeCriteria(
                $progress->criteria()
            ),
            'recommendation' =>
                $progress->recommendation(),
            'revision' => $progress->revision(),
            'evaluated_at' => $progress
                ->evaluatedAt()
                ->format('Y-m-d H:i:s'),
        ]);
    }

    private function reload(
        int $workId,
        WorkPillar $pillar,
    ): PillarProgress {
        $progress = $this->findByWorkAndPillar(
            workId: $workId,
            pillar: $pillar,
        );

        if ($progress === null) {
            throw new \RuntimeException(
                'Saved progress could not be reloaded.'
            );
        }

        return $progress;
    }

    /**
     * @param list<ProgressCriterion> $criteria
     */
    private function encodeCriteria(
        array $criteria,
    ): string {
        return json_encode(
            array_map(
                static fn (
                    ProgressCriterion $criterion
                ): array => $criterion->toArray(),
                $criteria
            ),
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @return list<ProgressCriterion>
     */
    private function decodeCriteria(
        mixed $value,
    ): array {
        if (!is_string($value)) {
            return [];
        }

        $decoded = json_decode(
            $value,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($decoded)) {
            return [];
        }

        $criteria = [];

        foreach ($decoded as $criterion) {
            if (!is_array($criterion)) {
                continue;
            }

            $criteria[] =
                ProgressCriterion::fromArray(
                    $criterion
                );
        }

        return $criteria;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row,
    ): PillarProgress {
        return new PillarProgress(
            id: (int) $row['id'],
            workId: (int) $row['work_id'],

            pillar: WorkPillar::from(
                (string) $row['pillar']
            ),

            status: ProgressStatus::from(
                (string) $row['status']
            ),

            readinessScore:
                (int) $row['readiness_score'],

            ready:
                (bool) $row['is_ready'],

            criteria: $this->decodeCriteria(
                $row['criteria'] ?? null
            ),

            recommendation:
                is_string(
                    $row['recommendation']
                    ?? null
                )
                    ? $row['recommendation']
                    : null,

            revision:
                (int) $row['revision'],

            evaluatedAt:
                new DateTimeImmutable(
                    (string) $row['evaluated_at']
                ),

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
}