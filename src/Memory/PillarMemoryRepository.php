<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

use DateTimeImmutable;
use PDO;
use SonicFoundry\Work\WorkPillar;

final class PillarMemoryRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function findByWorkAndPillar(
        int $workId,
        WorkPillar $pillar,
    ): ?PillarMemory {
        $statement = $this->pdo->prepare(
            '
            SELECT
                id,
                work_id,
                pillar,
                memory_data,
                confidence,
                status,
                revision,
                created_at,
                updated_at
            FROM work_pillar_memory
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

    public function saveExtraction(
        int $workId,
        WorkPillar $pillar,
        MemoryExtraction $extraction,
        MemoryStatus $status,
    ): PillarMemory {
        $this->pdo->beginTransaction();

        try {
            $existing = $this->findByWorkAndPillar(
                workId: $workId,
                pillar: $pillar,
            );

            $memory = $existing === null
                ? $this->insertMemory(
                    workId: $workId,
                    pillar: $pillar,
                    extraction: $extraction,
                    status: $status,
                )
                : $this->updateMemory(
                    existing: $existing,
                    extraction: $extraction,
                    status: $status,
                );

            $this->insertRevision(
                $memory
            );

            $this->pdo->commit();

            return $memory;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $error;
        }
    }

    public function changeStatus(
        PillarMemory $memory,
        MemoryStatus $status,
    ): PillarMemory {
        $this->pdo->beginTransaction();

        try {
            $nextRevision =
                $memory->revision() + 1;

            $statement = $this->pdo->prepare(
                '
                UPDATE work_pillar_memory
                SET
                    status = :status,
                    revision = :next_revision
                WHERE id = :id
                  AND revision = :current_revision
                '
            );

            $statement->execute([
                'status' => $status->value,
                'next_revision' => $nextRevision,
                'id' => $memory->id(),
                'current_revision' =>
                    $memory->revision(),
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException(
                    'Memory was modified by another request.'
                );
            }

            $updated = $this->reload(
                workId: $memory->workId(),
                pillar: $memory->pillar(),
            );

            $this->insertRevision(
                $updated
            );

            $this->pdo->commit();

            return $updated;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $error;
        }
    }

    private function insertMemory(
        int $workId,
        WorkPillar $pillar,
        MemoryExtraction $extraction,
        MemoryStatus $status,
    ): PillarMemory {
        $statement = $this->pdo->prepare(
            '
            INSERT INTO work_pillar_memory (
                work_id,
                pillar,
                memory_data,
                confidence,
                status,
                revision
            ) VALUES (
                :work_id,
                :pillar,
                :memory_data,
                :confidence,
                :status,
                1
            )
            '
        );

        $statement->execute([
            'work_id' => $workId,
            'pillar' => $pillar->value,

            'memory_data' =>
                $this->encodeDocument(
                    $extraction->data()
                ),

            'confidence' =>
                $extraction->confidence(),

            'status' => $status->value,
        ]);

        return $this->reload(
            workId: $workId,
            pillar: $pillar,
        );
    }

    private function updateMemory(
        PillarMemory $existing,
        MemoryExtraction $extraction,
        MemoryStatus $status,
    ): PillarMemory {
        $nextRevision =
            $existing->revision() + 1;

        $statement = $this->pdo->prepare(
            '
            UPDATE work_pillar_memory
            SET
                memory_data = :memory_data,
                confidence = :confidence,
                status = :status,
                revision = :next_revision
            WHERE id = :id
              AND revision = :current_revision
            '
        );

        $statement->execute([
            'memory_data' =>
                $this->encodeDocument(
                    $extraction->data()
                ),

            'confidence' =>
                $extraction->confidence(),

            'status' => $status->value,
            'next_revision' => $nextRevision,
            'id' => $existing->id(),

            'current_revision' =>
                $existing->revision(),
        ]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException(
                'Memory was modified by another request.'
            );
        }

        return $this->reload(
            workId: $existing->workId(),
            pillar: $existing->pillar(),
        );
    }

    private function insertRevision(
        PillarMemory $memory,
    ): void {
        $statement = $this->pdo->prepare(
            '
            INSERT INTO work_pillar_memory_revisions (
                memory_id,
                work_id,
                pillar,
                memory_data,
                confidence,
                status,
                revision
            ) VALUES (
                :memory_id,
                :work_id,
                :pillar,
                :memory_data,
                :confidence,
                :status,
                :revision
            )
            '
        );

        $statement->execute([
            'memory_id' => $memory->id(),
            'work_id' => $memory->workId(),
            'pillar' => $memory->pillar()->value,

            'memory_data' =>
                $this->encodeDocument(
                    $memory->data()
                ),

            'confidence' => $memory->confidence(),
            'status' => $memory->status()->value,
            'revision' => $memory->revision(),
        ]);
    }

    private function reload(
        int $workId,
        WorkPillar $pillar,
    ): PillarMemory {
        $memory = $this->findByWorkAndPillar(
            workId: $workId,
            pillar: $pillar,
        );

        if ($memory === null) {
            throw new \RuntimeException(
                'Saved memory could not be reloaded.'
            );
        }

        return $memory;
    }

    /**
     * @param array<string, mixed> $document
     */
    private function encodeDocument(
        array $document,
    ): string {
        return json_encode(
            $document,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeDocument(
        mixed $value,
    ): array {
        if (
            !is_string($value)
            || trim($value) === ''
        ) {
            throw new \RuntimeException(
                'Creative Memory contains no document.'
            );
        }

        $decoded = json_decode(
            $value,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($decoded)) {
            throw new \RuntimeException(
                'Creative Memory document is invalid.'
            );
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row,
    ): PillarMemory {
        return new PillarMemory(
            id: (int) $row['id'],

            workId: (int) $row['work_id'],

            pillar: WorkPillar::from(
                (string) $row['pillar']
            ),

            data: $this->decodeDocument(
                $row['memory_data'] ?? null
            ),

            confidence:
                $row['confidence'] !== null
                    ? (float) $row['confidence']
                    : null,

            status: MemoryStatus::from(
                (string) $row['status']
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
}