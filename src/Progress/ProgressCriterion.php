<?php
declare(strict_types=1);

namespace SonicFoundry\Progress;

final class ProgressCriterion
{
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly CriterionStatus $status,
        private readonly ?string $evidence,
        private readonly ?string $guidance,
    ) {
        if (trim($this->key) === '') {
            throw new \InvalidArgumentException(
                'Progress criterion key cannot be empty.'
            );
        }

        if (trim($this->label) === '') {
            throw new \InvalidArgumentException(
                'Progress criterion label cannot be empty.'
            );
        }
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function status(): CriterionStatus
    {
        return $this->status;
    }

    public function statusLabel(): string
    {
        return $this->status->label();
    }

    public function evidence(): ?string
    {
        return $this->normalize(
            $this->evidence
        );
    }

    public function guidance(): ?string
    {
        return $this->normalize(
            $this->guidance
        );
    }

    public function isEstablished(): bool
    {
        return $this->status
            === CriterionStatus::Established;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'status' => $this->status()->value,
            'evidence' => $this->evidence(),
            'guidance' => $this->guidance(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        array $data,
    ): self {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? ''),

            status: CriterionStatus::from(
                (string) ($data['status'] ?? '')
            ),

            evidence: is_string(
                $data['evidence'] ?? null
            )
                ? $data['evidence']
                : null,

            guidance: is_string(
                $data['guidance'] ?? null
            )
                ? $data['guidance']
                : null,
        );
    }

    private function normalize(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}