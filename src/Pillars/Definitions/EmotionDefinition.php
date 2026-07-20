<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Definitions;

use SonicFoundry\Pillars\Contracts\ConversationDefinition;
use SonicFoundry\Pillars\Contracts\MemoryDefinition;
use SonicFoundry\Pillars\Contracts\ProgressDefinition;
use SonicFoundry\Work\WorkPillar;

final class EmotionDefinition
    extends AbstractPillarDefinition
{
    public function pillar(): WorkPillar
    {
        return WorkPillar::Emotion;
    }

    public function conversation(): ConversationDefinition
    {
        return new ConversationDefinition(
            promptPath: 'pillars/emotion.md',
        );
    }

    public function memory(): MemoryDefinition
    {
        return new MemoryDefinition(
            fields: [
                $this->textField(
                    key: 'emotional_core',
                    label: 'Emotional Core',
                    emptyMessage:
                        'The emotional core will appear after it is discussed and proposed.',
                ),

                $this->textField(
                    key: 'starting_emotion',
                    label: 'Starting Emotion',
                    emptyMessage:
                        'The starting emotional state has not yet been established.',
                ),

                $this->textField(
                    key: 'ending_emotion',
                    label: 'Ending Emotion',
                    emptyMessage:
                        'The ending emotional state has not yet been established.',
                ),

                $this->textField(
                    key: 'emotional_arc',
                    label: 'Emotional Arc',
                    emptyMessage:
                        'The emotional journey has not yet been established.',
                ),

                $this->textField(
                    key: 'emotional_stakes',
                    label: 'Emotional Stakes',
                    emptyMessage:
                        'The emotional stakes have not yet been established.',
                ),

                $this->textField(
                    key: 'desired_listener_feeling',
                    label: 'Desired Listener Feeling',
                    emptyMessage:
                        'The intended listener feeling has not yet been established.',
                ),

                $this->listField(
                    key: 'emotional_contrasts',
                    label: 'Emotional Contrasts',
                    emptyMessage:
                        'Emotional contrasts will appear after they are identified.',
                ),

                $this->listField(
                    key: 'emotional_touchstones',
                    label: 'Emotional Touchstones',
                    emptyMessage:
                        'Emotional touchstones will appear after they are identified.',
                ),
            ],

            extractionPromptPath:
                'memory/emotion-extractor.md',
        );
    }

    public function progress(): ProgressDefinition
    {
        return new ProgressDefinition(
            title: 'Emotion Readiness',

            promptPath:
                'progress/emotion-evaluator.md',

            criteria: [
                $this->criterion(
                    key: 'emotional_core',
                    label: 'Emotional Core',
                    description:
                        'The central emotional truth of the Work is clearly established.',
                ),

                $this->criterion(
                    key: 'starting_emotion',
                    label: 'Starting Emotion',
                    description:
                        'The emotional condition from which the Work begins is understood.',
                ),

                $this->criterion(
                    key: 'ending_emotion',
                    label: 'Ending Emotion',
                    description:
                        'The emotional condition toward which the Work moves is understood.',
                ),

                $this->criterion(
                    key: 'emotional_arc',
                    label: 'Emotional Arc',
                    description:
                        'The emotional movement from beginning to end is coherent and useful.',
                ),

                $this->criterion(
                    key: 'emotional_stakes',
                    label: 'Emotional Stakes',
                    description:
                        'What may be emotionally gained, lost, confronted, or transformed is clear.',
                ),

                $this->criterion(
                    key: 'desired_listener_feeling',
                    label: 'Desired Listener Feeling',
                    description:
                        'The intended emotional experience for the listener is sufficiently established.',
                ),

                $this->criterion(
                    key: 'emotional_contrasts',
                    label: 'Emotional Contrasts',
                    description:
                        'Meaningful emotional oppositions or tensions have been identified.',
                ),

                $this->criterion(
                    key: 'emotional_touchstones',
                    label: 'Emotional Touchstones',
                    description:
                        'The moments, memories, images, relationships, or symbols carrying emotional weight are identified.',
                ),
            ],

            completionThreshold: 80,
        );
    }
}