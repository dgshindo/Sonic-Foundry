<?php
declare(strict_types=1);

namespace SonicFoundry\Artifact;

use DateTimeImmutable;
use PDO;

final class CreativeArtifactRepository
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function findByWorkAndType(
        int $workId,
        CreativeArtifactType $type,
    ): ?CreativeArtifact {
        $statement = $this->pdo->prepare(
            '
                SELECT
                    id,
                    work_id,
                    artifact_type,
                    title,
                    content,
                    revision,
                    created_at,
                    updated_at
                FROM creative_artifacts
                WHERE work_id = :work_id
                  AND artifact_type = :artifact_type
                LIMIT 1
            '
        );

        $statement->execute([
            'work_id' =>
                $workId,

            'artifact_type' =>
                $type->value,
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $this->hydrate($row)
            : null;
    }

    public function save(
        int $workId,
        CreativeArtifactType $type,
        string $title,
        string $content,
    ): CreativeArtifact {
        $title = trim($title);
        $content = trim($content);

        if ($workId < 1) {
            throw new \InvalidArgumentException(
                'A valid Work ID is required.'
            );
        }

        if ($title === '') {
            throw new \InvalidArgumentException(
                'Artifact title cannot be empty.'
            );
        }

        if ($content === '') {
            throw new \InvalidArgumentException(
                'Artifact content cannot be empty.'
            );
        }

        $this->pdo->beginTransaction();

        try {
            $existing =
                $this->findByWorkAndType(
                    workId: $workId,
                    type: $type,
                );

            if ($existing === null) {
                $this->insert(
                    workId: $workId,
                    type: $type,
                    title: $title,
                    content: $content,
                );
            } else {
                $this->update(
                    existing: $existing,
                    title: $title,
                    content: $content,
                );
            }

            $artifact =
                $this->findByWorkAndType(
                    workId: $workId,
                    type: $type,
                );

            if ($artifact === null) {
                throw new \RuntimeException(
                    'Saved artifact could not be reloaded.'
                );
            }

            $this->pdo->commit();

            return $artifact;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $error;
        }
    }

    private function insert(
        int $workId,
        CreativeArtifactType $type,
        string $title,
        string $content,
    ): void {
        $statement = $this->pdo->prepare(
            '
                INSERT INTO creative_artifacts (
                    work_id,
                    artifact_type,
                    title,
                    content,
                    revision
                ) VALUES (
                    :work_id,
                    :artifact_type,
                    :title,
                    :content,
                    1
                )
            '
        );

        $statement->execute([
            'work_id' =>
                $workId,

            'artifact_type' =>
                $type->value,

            'title' =>
                $title,

            'content' =>
                $content,
        ]);
    }

    private function update(
        CreativeArtifact $existing,
        string $title,
        string $content,
    ): void {
        $nextRevision =
            $existing->revision() + 1;

        $statement = $this->pdo->prepare(
            '
                UPDATE creative_artifacts
                SET
                    title = :title,
                    content = :content,
                    revision = :next_revision
                WHERE id = :id
                  AND revision = :current_revision
            '
        );

        $statement->execute([
            'title' =>
                $title,

            'content' =>
                $content,

            'next_revision' =>
                $nextRevision,

            'id' =>
                $existing->id(),

            'current_revision' =>
                $existing->revision(),
        ]);

        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException(
                'The artifact was modified by another request.'
            );
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(
        array $row,
    ): CreativeArtifact {
        return new CreativeArtifact(
            id:
                (int) $row['id'],

            workId:
                (int) $row['work_id'],

            type:
                CreativeArtifactType::from(
                    (string) $row[
                        'artifact_type'
                    ]
                ),

            title:
                (string) $row['title'],

            content:
                (string) $row['content'],

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