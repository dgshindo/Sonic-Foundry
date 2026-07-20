<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Definitions;

use SonicFoundry\Pillars\Contracts\ConversationDefinition;
use SonicFoundry\Pillars\Contracts\MemoryDefinition;
use SonicFoundry\Pillars\Contracts\PillarDefinition;
use SonicFoundry\Pillars\Contracts\ProgressDefinition;
use SonicFoundry\Pillars\Support\MemoryFieldDefinition;
use SonicFoundry\Pillars\Support\MemoryFieldType;
use SonicFoundry\Pillars\Support\ProgressCriterion;
use SonicFoundry\Work\WorkPillar;

final class EmotionDefinition implements PillarDefinition
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
                new MemoryFieldDefinition(
                    key: 'emotional_core',
                    label: 'Emotional Core',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'The emotional core will appear after it is discussed and proposed.',
                ),

                new MemoryFieldDefinition(
                    key: 'starting_emotion',
                    label: 'Starting Emotion',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'The starting emotional state has not yet been established.',
                ),

                new MemoryFieldDefinition(
                    key: 'ending_emotion',
                    label: 'Ending Emotion',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'The ending emotional state has not yet been established.',
                ),

                new MemoryFieldDefinition(
                    key: 'emotional_arc',
                    label: 'Emotional Arc',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'The emotional journey has not yet been established.',
                ),

                new MemoryFieldDefinition(
                    key: 'emotional_stakes',
                    label: 'Emotional Stakes',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'The emotional stakes have not yet been established.',
                ),

                new MemoryFieldDefinition(
                    key: 'desired_listener_feeling',
                    label: 'Desired Listener Feeling',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'The intended listener feeling has not yet been established.',
                ),

                new MemoryFieldDefinition(
                    key: 'emotional_contrasts',
                    label: 'Emotional Contrasts',
                    type: MemoryFieldType::List,
                    emptyMessage:
                        'Emotional contrasts will appear after they are identified.',
                ),

                new MemoryFieldDefinition(
                    key: 'emotional_touchstones',
                    label: 'Emotional Touchstones',
                    type: MemoryFieldType::List,
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
        /*
         * The prompt file will be created when Emotion Progress
         * is implemented. D1B only declares its intended location.
         */
        return new ProgressDefinition(
            title: 'Emotion Readiness',

            promptPath:
                'progress/emotion-evaluator.md',

            criteria: [
                new ProgressCriterion(
                    key: 'emotional_core',
                    label: 'Emotional Core',
                    description:
                        'The central emotional truth of the Work is clearly established.',
                ),

                new ProgressCriterion(
                    key: 'starting_emotion',
                    label: 'Starting Emotion',
                    description:
                        'The emotional condition from which the Work begins is understood.',
                ),

                new ProgressCriterion(
                    key: 'ending_emotion',
                    label: 'Ending Emotion',
                    description:
                        'The emotional condition toward which the Work moves is understood.',
                ),

                new ProgressCriterion(
                    key: 'emotional_arc',
                    label: 'Emotional Arc',
                    description:
                        'The emotional movement from beginning to end is coherent and useful.',
                ),

                new ProgressCriterion(
                    key: 'emotional_stakes',
                    label: 'Emotional Stakes',
                    description:
                        'What may be emotionally gained, lost, confronted, or transformed is clear.',
                ),

                new ProgressCriterion(
                    key: 'desired_listener_feeling',
                    label: 'Desired Listener Feeling',
                    description:
                        'The intended emotional experience for the listener is sufficiently established.',
                ),

                new ProgressCriterion(
                    key: 'emotional_contrasts',
                    label: 'Emotional Contrasts',
                    description:
                        'Meaningful emotional oppositions or tensions have been identified.',
                ),

                new ProgressCriterion(
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