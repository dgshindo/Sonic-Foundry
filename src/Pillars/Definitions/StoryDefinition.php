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

final class StoryDefinition implements PillarDefinition
{
    public function pillar(): WorkPillar
    {
        return WorkPillar::Story;
    }

    public function conversation(): ConversationDefinition
    {
        return new ConversationDefinition(
            promptPath: 'pillars/story.md',
        );
    }

    public function memory(): MemoryDefinition
    {
        return new MemoryDefinition(
            fields: [
                new MemoryFieldDefinition(
                    key: 'summary',
                    label: 'Summary',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'No Story summary has been proposed yet.',
                ),

                new MemoryFieldDefinition(
                    key: 'themes',
                    label: 'Themes',
                    type: MemoryFieldType::List,
                    emptyMessage:
                        'Themes will appear after they are discussed and proposed.',
                ),

                new MemoryFieldDefinition(
                    key: 'perspective',
                    label: 'Perspective',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'The narrative perspective has not yet been established.',
                ),

                new MemoryFieldDefinition(
                    key: 'core_tension',
                    label: 'Core Tension',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'The central tension has not yet been established.',
                ),

                new MemoryFieldDefinition(
                    key: 'key_subjects',
                    label: 'Key Subjects',
                    type: MemoryFieldType::List,
                    emptyMessage:
                        'Key subjects will appear after they are identified.',
                ),

                new MemoryFieldDefinition(
                    key: 'listener_takeaway',
                    label: 'Listener Takeaway',
                    type: MemoryFieldType::Text,
                    emptyMessage:
                        'The intended listener takeaway has not yet been established.',
                ),
            ],

            extractionPromptPath:
                'memory/story-extractor.md',
        );
    }

    public function progress(): ProgressDefinition
    {
        return new ProgressDefinition(
            title: 'Story Readiness',

            promptPath:
                'progress/story-evaluator.md',

            criteria: [
                new ProgressCriterion(
                    key: 'central_meaning',
                    label: 'Central Meaning',
                    description:
                        'The Work has a clear fundamental meaning or subject of exploration.',
                ),

                new ProgressCriterion(
                    key: 'perspective',
                    label: 'Perspective',
                    description:
                        'The expressive or narrative viewpoint is sufficiently established.',
                ),

                new ProgressCriterion(
                    key: 'core_tension',
                    label: 'Core Tension',
                    description:
                        'A meaningful pressure, contradiction, conflict, or transformation drives the Work.',
                ),

                new ProgressCriterion(
                    key: 'themes',
                    label: 'Themes',
                    description:
                        'The central themes are specific, coherent, and creatively useful.',
                ),

                new ProgressCriterion(
                    key: 'key_subjects',
                    label: 'Key Subjects',
                    description:
                        'The important people, relationships, places, events, symbols, or ideas are identified.',
                ),

                new ProgressCriterion(
                    key: 'listener_takeaway',
                    label: 'Listener Takeaway',
                    description:
                        'The intended understanding, feeling, or question left with the listener is clear.',
                ),
            ],

            completionThreshold: 80,
        );
    }
}