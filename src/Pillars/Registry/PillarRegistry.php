<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Registry;

use SonicFoundry\Pillars\Contracts\PillarDefinition;
use SonicFoundry\Work\WorkPillar;

final class PillarRegistry
{
    /**
     * @var array<string, PillarDefinition>
     */
    private array $definitions = [];

    public function register(
        PillarDefinition $definition,
    ): void {
        $key = $definition
            ->pillar()
            ->value;

        if (isset($this->definitions[$key])) {
            throw new \LogicException(
                'A definition for pillar "'
                . $key
                . '" has already been registered.'
            );
        }

        $this->definitions[$key] =
            $definition;
    }

    public function definition(
        WorkPillar $pillar,
    ): PillarDefinition {
        $definition =
            $this->definitions[
                $pillar->value
            ]
            ?? null;

        if (!$definition instanceof PillarDefinition) {
            throw new \DomainException(
                'No definition is registered for pillar "'
                . $pillar->value
                . '".'
            );
        }

        return $definition;
    }

    public function has(
        WorkPillar $pillar,
    ): bool {
        return isset(
            $this->definitions[
                $pillar->value
            ]
        );
    }

    /**
     * @return list<PillarDefinition>
     */
    public function all(): array
    {
        $ordered = [];

        foreach (WorkPillar::cases() as $pillar) {
            if (!$this->has($pillar)) {
                continue;
            }

            $ordered[] =
                $this->definition(
                    $pillar
                );
        }

        return $ordered;
    }
}