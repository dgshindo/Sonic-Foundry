<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Definitions;

use SonicFoundry\Pillars\Contracts\ConversationDefinition;
use SonicFoundry\Pillars\Contracts\MemoryDefinition;
use SonicFoundry\Pillars\Contracts\ProgressDefinition;
use SonicFoundry\Work\WorkPillar;

final class StoryDefinition
    extends AbstractPillarDefinition
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
                $this->textField(
                    key: 'summary',
                    label: 'Summary',
                    emptyMessage:
                        'No Story summary has been proposed yet.',
                ),

                $this->listField(
                    key: 'themes',
                    label: 'Themes',
                    emptyMessage:
                        'Themes will appear after they are discussed and proposed.',
                ),

                $this->textField(
                    key: 'perspective',
                    label: 'Perspective',
                    emptyMessage:
                        'The narrative perspective has not yet been established.',
                ),

                $this->textField(
                    key: 'core_tension',
                    label: 'Core Tension',
                    emptyMessage:
                        'The central tension has not yet been established.',
                ),

                $this->listField(
                    key: 'key_subjects',
                    label: 'Key Subjects',
                    emptyMessage:
                        'Key subjects will appear after they are identified.',
                ),

                $this->textField(
                    key: 'listener_takeaway',
                    label: 'Listener Takeaway',
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
                $this->criterion(
                    key: 'central_meaning',
                    label: 'Central Meaning',
                    description:
                        'The Work has a clear fundamental meaning or subject of exploration.',
                ),

                $this->criterion(
                    key: 'perspective',
                    label: 'Perspective',
                    description:
                        'The expressive or narrative viewpoint is sufficiently established.',
                ),

                $this->criterion(
                    key: 'core_tension',
                    label: 'Core Tension',
                    description:
                        'A meaningful pressure, contradiction, conflict, or transformation drives the Work.',
                ),

                $this->criterion(
                    key: 'themes',
                    label: 'Themes',
                    description:
                        'The central themes are specific, coherent, and creatively useful.',
                ),

                $this->criterion(
                    key: 'key_subjects',
                    label: 'Key Subjects',
                    description:
                        'The important people, relationships, places, events, symbols, or ideas are identified.',
                ),

                $this->criterion(
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