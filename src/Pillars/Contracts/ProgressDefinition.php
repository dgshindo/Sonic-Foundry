<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Contracts;

use SonicFoundry\Pillars\Support\ProgressCriterion;

final class ProgressDefinition
{
    /**
     * @param list<ProgressCriterion> $criteria
     */
    public function __construct(
        private string $title,
        private array $criteria,
    ) {
        foreach ($this->criteria as $criterion) {
            if (!$criterion instanceof ProgressCriterion) {
                throw new \InvalidArgumentException(
                    'Every progress criterion must be a ProgressCriterion.'
                );
            }
        }
    }

    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return list<ProgressCriterion>
     */
    public function criteria(): array
    {
        return $this->criteria;
    }
}