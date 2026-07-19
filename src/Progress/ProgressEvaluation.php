<?php
declare(strict_types=1);

namespace SonicFoundry\Progress;

final class ProgressEvaluation
{
    /**
     * @param list<ProgressCriterion> $criteria
     */
    public function __construct(
        private readonly array $criteria,
        private readonly int $readinessScore,
        private readonly bool $ready,
        private readonly ?string $recommendation,
    ) {
        if (
            $this->readinessScore < 0
            || $this->readinessScore > 100
        ) {
            throw new \InvalidArgumentException(
                'Readiness score must be between 0 and 100.'
            );
        }

        if ($this->criteria === []) {
            throw new \InvalidArgumentException(
                'A progress evaluation requires criteria.'
            );
        }

        foreach ($this->criteria as $criterion) {
            if (
                !$criterion
                instanceof ProgressCriterion
            ) {
                throw new \InvalidArgumentException(
                    'Every progress criterion must be valid.'
                );
            }
        }
    }

    /**
     * @return list<ProgressCriterion>
     */
    public function criteria(): array
    {
        return $this->criteria;
    }

    public function readinessScore(): int
    {
        return $this->readinessScore;
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function recommendation(): ?string
    {
        if ($this->recommendation === null) {
            return null;
        }

        $value = trim(
            $this->recommendation
        );

        return $value !== ''
            ? $value
            : null;
    }

    public function status(): ProgressStatus
    {
        return $this->isReady()
            ? ProgressStatus::Ready
            : ProgressStatus::Developing;
    }
}