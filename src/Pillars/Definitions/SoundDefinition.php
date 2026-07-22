<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Definitions;

use SonicFoundry\Pillars\Contracts\ConversationDefinition;
use SonicFoundry\Pillars\Contracts\MemoryDefinition;
use SonicFoundry\Pillars\Contracts\ProgressDefinition;
use SonicFoundry\Work\WorkPillar;

final class SoundDefinition
    extends AbstractPillarDefinition
{
    public function pillar(): WorkPillar
    {
        return WorkPillar::Sound;
    }

    public function conversation(): ConversationDefinition
    {
        return new ConversationDefinition(
            promptPath: 'pillars/Sound.md',
        );
    }

    public function memory(): MemoryDefinition
    {
        return new MemoryDefinition(
            fields: [
                $this->textField(
                    key: 'sonic_identity',
                    label: 'Sonic Identity',
                    emptyMessage:
                        'The sonic identity of the Work has not yet been established.',
                ),

                $this->textField(
                    key: 'energy_profile',
                    label: 'Energy Profile',
                    emptyMessage:
                        'The energy profile has not yet been established.',
                ),

                $this->listField(
                    key: 'instrumentation_direction',
                    label: 'Instrumentation Direction',
                    emptyMessage:
                        'Instrumentation direction will appear after it has been identified.',
                ),

                $this->textField(
                    key: 'vocal_character',
                    label: 'Vocal Character',
                    emptyMessage:
                        'The vocal character has not yet been established.',
                ),

                $this->textField(
                    key: 'production_aesthetic',
                    label: 'Production Aesthetic',
                    emptyMessage:
                        'The production aesthetic has not yet been established.',
                ),

                $this->textField(
                    key: 'rhythmic_feel',
                    label: 'Rhythmic Feel',
                    emptyMessage:
                        'The rhythmic feel has not yet been established.',
                ),

                $this->textField(
                    key: 'harmonic_language',
                    label: 'Harmonic Language',
                    emptyMessage:
                        'The harmonic language has not yet been established.',
                ),

                $this->textField(
                    key: 'listening_environment',
                    label: 'Listening Environment',
                    emptyMessage:
                        'The intended listening environment has not yet been established.',
                ),
            ],

            extractionPromptPath:
                'memory/sound-extractor.md',
        );
    }

    public function progress(): ProgressDefinition
    {
        return new ProgressDefinition(
            title: 'Sound Readiness',

            promptPath:
                'progress/sound-evaluator.md',

            criteria: [
                $this->criterion(
                    key: 'sonic_identity',
                    label: 'Sonic Identity',
                    description:
                        'The overall sonic character of the Work is clearly established.',
                ),

                $this->criterion(
                    key: 'energy_profile',
                    label: 'Energy Profile',
                    description:
                        'The musical energy and dynamic movement are sufficiently understood.',
                ),

                $this->criterion(
                    key: 'instrumentation_direction',
                    label: 'Instrumentation Direction',
                    description:
                        'The broad instrumental direction is sufficiently defined to guide production decisions.',
                ),

                $this->criterion(
                    key: 'vocal_character',
                    label: 'Vocal Character',
                    description:
                        'The intended vocal style and expressive character are clearly established.',
                ),

                $this->criterion(
                    key: 'production_aesthetic',
                    label: 'Production Aesthetic',
                    description:
                        'The overall production philosophy and sonic texture are sufficiently understood.',
                ),

                $this->criterion(
                    key: 'rhythmic_feel',
                    label: 'Rhythmic Feel',
                    description:
                        'The rhythmic movement and groove are clearly established.',
                ),

                $this->criterion(
                    key: 'harmonic_language',
                    label: 'Harmonic Language',
                    description:
                        'The harmonic character and musical language are sufficiently understood.',
                ),

                $this->criterion(
                    key: 'listening_environment',
                    label: 'Listening Environment',
                    description:
                        'The intended listening experience or environment is clearly established.',
                ),
            ],

            completionThreshold: 80,
        );
    }
}