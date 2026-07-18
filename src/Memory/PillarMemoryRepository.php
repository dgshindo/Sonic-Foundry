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
                summary,
                perspective,
                core_tension,
                listener_takeaway,
                themes,
                key_subjects,
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

        return $row
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

            if ($existing === null) {
                $memory = $this->insertMemory(
                    workId: $workId,
                    pillar: $pillar,
                    extraction: $extraction,
                    status: $status,
                );
            } else {
                $memory = $this->updateMemory(
                    existing: $existing,
                    extraction: $extraction,
                    status: $status,
                );
            }

            $this->insertRevision($memory);

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
            $nextRevision = $memory->revision() + 1;

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
                'current_revision' => $memory->revision(),
            ]);

            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException(
                    'Memory was modified by another request.'
                );
            }

            $updated = $this->findByWorkAndPillar(
                workId: $memory->workId(),
                pillar: $memory->pillar(),
            );

            if ($updated === null) {
                throw new \RuntimeException(
                    'Updated memory could not be reloaded.'
                );
            }

            $this->insertRevision($updated);

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
                summary,
                perspective,
                core_tension,
                listener_takeaway,
                themes,
                key_subjects,
                confidence,
                status,
                revision
            ) VALUES (
                :work_id,
                :pillar,
                :summary,
                :perspective,
                :core_tension,
                :listener_takeaway,
                :themes,
                :key_subjects,
                :confidence,
                :status,
                1
            )
            '
        );

        $statement->execute(
            $this->extractionParameters(
                workId: $workId,
                pillar: $pillar,
                extraction: $extraction,
                status: $status,
            )
        );

        $memory = $this->findByWorkAndPillar(
            workId: $workId,
            pillar: $pillar,
        );

        if ($memory === null) {
            throw new \RuntimeException(
                'Created memory could not be reloaded.'
            );
        }

        return $memory;
    }

    private function updateMemory(
        PillarMemory $existing,
        MemoryExtraction $extraction,
        MemoryStatus $status,
    ): PillarMemory {
        $nextRevision = $existing->revision() + 1;

        $statement = $this->pdo->prepare(
            '
            UPDATE work_pillar_memory
            SET
                summary = :summary,
                perspective = :perspective,
                core_tension = :core_tension,
                listener_takeaway = :listener_takeaway,
                themes = :themes,
                key_subjects = :key_subjects,
                confidence = :confidence,
                status = :status,
                revision = :next_revision
            WHERE id = :id
              AND revision = :current_revision
            '
        );

        $statement->execute([
            'summary' => $extraction->summary(),
            'perspective' => $extraction->perspective(),
            'core_tension' => $extraction->coreTension(),
            'listener_takeaway' =>
                $extraction->listenerTakeaway(),

            'themes' => $this->encodeList(
                $extraction->themes()
            ),

            'key_subjects' => $this->encodeList(
                $extraction->keySubjects()
            ),

            'confidence' => $extraction->confidence(),
            'status' => $status->value,
            'next_revision' => $nextRevision,
            'id' => $existing->id(),
            'current_revision' => $existing->revision(),
        ]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException(
                'Memory was modified by another request.'
            );
        }

        $updated = $this->findByWorkAndPillar(
            workId: $existing->workId(),
            pillar: $existing->pillar(),
        );

        if ($updated === null) {
            throw new \RuntimeException(
                'Updated memory could not be reloaded.'
            );
        }

        return $updated;
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
                summary,
                perspective,
                core_tension,
                listener_takeaway,
                themes,
                key_subjects,
                confidence,
                status,
                revision
            ) VALUES (
                :memory_id,
                :work_id,
                :pillar,
                :summary,
                :perspective,
                :core_tension,
                :listener_takeaway,
                :themes,
                :key_subjects,
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
            'summary' => $memory->summary(),
            'perspective' => $memory->perspective(),
            'core_tension' => $memory->coreTension(),
            'listener_takeaway' =>
                $memory->listenerTakeaway(),

            'themes' => $this->encodeList(
                $memory->themes()
            ),

            'key_subjects' => $this->encodeList(
                $memory->keySubjects()
            ),

            'confidence' => $memory->confidence(),
            'status' => $memory->status()->value,
            'revision' => $memory->revision(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractionParameters(
        int $workId,
        WorkPillar $pillar,
        MemoryExtraction $extraction,
        MemoryStatus $status,
    ): array {
        return [
            'work_id' => $workId,
            'pillar' => $pillar->value,
            'summary' => $extraction->summary(),
            'perspective' => $extraction->perspective(),
            'core_tension' => $extraction->coreTension(),
            'listener_takeaway' =>
                $extraction->listenerTakeaway(),

            'themes' => $this->encodeList(
                $extraction->themes()
            ),

            'key_subjects' => $this->encodeList(
                $extraction->keySubjects()
            ),

            'confidence' => $extraction->confidence(),
            'status' => $status->value,
        ];
    }

    /**
     * @param list<string> $values
     */
    private function encodeList(
        array $values,
    ): string {
        return json_encode(
            array_values($values),
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * @return list<string>
     */
    private function decodeList(
        mixed $value,
    ): array {
        if (!is_string($value) || $value === '') {
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

        return array_values(
            array_filter(
                $decoded,
                static fn (mixed $item): bool =>
                    is_string($item)
                    && trim($item) !== ''
            )
        );
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

            summary: $this->nullableString(
                $row['summary'] ?? null
            ),

            perspective: $this->nullableString(
                $row['perspective'] ?? null
            ),

            coreTension: $this->nullableString(
                $row['core_tension'] ?? null
            ),

            listenerTakeaway: $this->nullableString(
                $row['listener_takeaway'] ?? null
            ),

            themes: $this->decodeList(
                $row['themes'] ?? null
            ),

            keySubjects: $this->decodeList(
                $row['key_subjects'] ?? null
            ),

            confidence: $row['confidence'] !== null
                ? (float) $row['confidence']
                : null,

            status: MemoryStatus::from(
                (string) $row['status']
            ),

            revision: (int) $row['revision'],

            createdAt: new DateTimeImmutable(
                (string) $row['created_at']
            ),

            updatedAt: new DateTimeImmutable(
                (string) $row['updated_at']
            ),
        );
    }

    private function nullableString(
        mixed $value,
    ): ?string {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}