<?php
declare(strict_types=1);

namespace SonicFoundry\Memory;

final class MemoryPresenter
{
    /**
     * Convert persisted pillar memory into a stable UI view model.
     *
     * @return array<string, mixed>
     */
    public function present(
        ?PillarMemory $memory,
    ): array {
        if ($memory === null) {
            return $this->emptyView();
        }

        return [
            'exists' => true,

            'id' => $memory->id(),
            'workId' => $memory->workId(),

            'pillar' => [
                'value' => $memory->pillar()->value,
                'label' => $memory->pillar()->label(),
            ],

            'status' => [
                'value' => $memory->status()->value,
                'label' => $memory->statusLabel(),
            ],

            'summary' => [
                'value' => $memory->summary(),
                'display' => $this->displayText(
                    $memory->summary(),
                    'No summary has been established.'
                ),
                'hasValue' => $memory->summary() !== null,
            ],

            'perspective' => [
                'value' => $memory->perspective(),
                'display' => $this->displayText(
                    $memory->perspective(),
                    'Perspective has not been established.'
                ),
                'hasValue' => $memory->perspective() !== null,
            ],

            'coreTension' => [
                'value' => $memory->coreTension(),
                'display' => $this->displayText(
                    $memory->coreTension(),
                    'Core tension has not been established.'
                ),
                'hasValue' => $memory->coreTension() !== null,
            ],

            'listenerTakeaway' => [
                'value' => $memory->listenerTakeaway(),
                'display' => $this->displayText(
                    $memory->listenerTakeaway(),
                    'Listener takeaway has not been established.'
                ),
                'hasValue' => (
                    $memory->listenerTakeaway()
                    !== null
                ),
            ],

            'themes' => [
                'values' => $memory->themes(),
                'display' => $this->displayList(
                    $memory->themes(),
                    'No themes have been established.'
                ),
                'hasValues' => $memory->themes() !== [],
            ],

            'keySubjects' => [
                'values' => $memory->keySubjects(),
                'display' => $this->displayList(
                    $memory->keySubjects(),
                    'No key subjects have been established.'
                ),
                'hasValues' => (
                    $memory->keySubjects()
                    !== []
                ),
            ],

            'confidence' => [
                'value' => $memory->confidence(),
                'display' => $this->displayConfidence(
                    $memory->confidence()
                ),
                'percentage' => (
                    $memory->confidence() !== null
                        ? (int) round(
                            $memory->confidence() * 100
                        )
                        : null
                ),
            ],

            'revision' => $memory->revision(),

            'canConfirm' => $memory->isProposed(),

            'isProposed' => $memory->isProposed(),

            'isConfirmed' => $memory->isConfirmed(),

            'createdAt' => [
                'iso' => $memory
                    ->createdAt()
                    ->format(DATE_ATOM),

                'display' => $memory
                    ->createdAt()
                    ->format('M j, Y \a\t g:i A'),
            ],

            'updatedAt' => [
                'iso' => $memory
                    ->updatedAt()
                    ->format(DATE_ATOM),

                'display' => $memory
                    ->updatedAt()
                    ->format('M j, Y \a\t g:i A'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyView(): array
    {
        return [
            'exists' => false,

            'id' => null,
            'workId' => null,

            'pillar' => [
                'value' => null,
                'label' => null,
            ],

            'status' => [
                'value' => 'empty',
                'label' => 'Not Yet Established',
            ],

            'summary' => [
                'value' => null,
                'display' => (
                    'No Story summary has been proposed yet.'
                ),
                'hasValue' => false,
            ],

            'perspective' => [
                'value' => null,
                'display' => (
                    'The narrative perspective has not yet '
                    . 'been established.'
                ),
                'hasValue' => false,
            ],

            'coreTension' => [
                'value' => null,
                'display' => (
                    'The central tension has not yet '
                    . 'been established.'
                ),
                'hasValue' => false,
            ],

            'listenerTakeaway' => [
                'value' => null,
                'display' => (
                    'The intended listener takeaway has not '
                    . 'yet been established.'
                ),
                'hasValue' => false,
            ],

            'themes' => [
                'values' => [],
                'display' => (
                    'Themes will appear after they are '
                    . 'discussed and proposed.'
                ),
                'hasValues' => false,
            ],

            'keySubjects' => [
                'values' => [],
                'display' => (
                    'Key subjects will appear after they are '
                    . 'identified.'
                ),
                'hasValues' => false,
            ],

            'confidence' => [
                'value' => null,
                'display' => 'Not available',
                'percentage' => null,
            ],

            'revision' => null,

            'canConfirm' => false,
            'isProposed' => false,
            'isConfirmed' => false,

            'createdAt' => [
                'iso' => null,
                'display' => null,
            ],

            'updatedAt' => [
                'iso' => null,
                'display' => null,
            ],
        ];
    }

    private function displayText(
        ?string $value,
        string $fallback,
    ): string {
        if ($value === null) {
            return $fallback;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : $fallback;
    }

    /**
     * @param list<string> $values
     */
    private function displayList(
        array $values,
        string $fallback,
    ): string {
        if ($values === []) {
            return $fallback;
        }

        return implode(', ', $values);
    }

    private function displayConfidence(
        ?float $confidence,
    ): string {
        if ($confidence === null) {
            return 'Not available';
        }

        return number_format(
            $confidence * 100,
            1
        ) . '%';
    }
}