<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Definitions;

use SonicFoundry\Pillars\Contracts\ConversationDefinition;
use SonicFoundry\Pillars\Contracts\MemoryDefinition;
use SonicFoundry\Pillars\Contracts\ProgressDefinition;
use SonicFoundry\Work\WorkPillar;

final class ImpactDefinition
    extends AbstractPillarDefinition
{
    public function pillar(): WorkPillar
    {
        return WorkPillar::Impact;
    }

    public function conversation(): ConversationDefinition
    {
        return new ConversationDefinition(
            promptPath: 'pillars/impact.md',
        );
    }

    public function memory(): MemoryDefinition
    {
        return new MemoryDefinition(
            fields: [
                $this->textField(
                    key: 'lasting_impression',
                    label: 'Lasting Impression',
                    emptyMessage:
                        'The lasting impression has not yet been established.',
                ),

                $this->textField(
                    key: 'desired_listener_response',
                    label: 'Desired Listener Response',
                    emptyMessage:
                        'The desired listener response has not yet been established.',
                ),

                $this->textField(
                    key: 'central_resonance',
                    label: 'Central Resonance',
                    emptyMessage:
                        'The deeper truth, idea, or question intended to endure has not yet been established.',
                ),

                $this->textField(
                    key: 'memorable_moment',
                    label: 'Memorable Moment',
                    emptyMessage:
                        'The defining memorable moment has not yet been established.',
                ),

                $this->textField(
                    key: 'emotional_resolution',
                    label: 'Emotional Resolution',
                    emptyMessage:
                        'The emotional condition in which the Work leaves the listener has not yet been established.',
                ),

                $this->textField(
                    key: 'call_to_reflection',
                    label: 'Call to Reflection',
                    emptyMessage:
                        'The intended invitation to reflection has not yet been established.',
                ),

                $this->listField(
                    key: 'desired_transformations',
                    label: 'Desired Transformations',
                    emptyMessage:
                        'Desired transformations will appear after they are identified.',
                ),

                $this->listField(
                    key: 'legacy_markers',
                    label: 'Legacy Markers',
                    emptyMessage:
                        'Legacy markers will appear after they are identified.',
                ),
            ],

            extractionPromptPath:
                'memory/impact-extractor.md',
        );
    }

    public function progress(): ProgressDefinition
    {
        return new ProgressDefinition(
            title: 'Impact Readiness',

            promptPath:
                'progress/impact-evaluator.md',

            criteria: [
                $this->criterion(
                    key: 'lasting_impression',
                    label: 'Lasting Impression',
                    description:
                        'The lasting impression the Work should leave with the listener is clearly established.',
                ),

                $this->criterion(
                    key: 'desired_listener_response',
                    label: 'Desired Listener Response',
                    description:
                        'The intended emotional, intellectual, physical, relational, or behavioral response is sufficiently understood.',
                ),

                $this->criterion(
                    key: 'central_resonance',
                    label: 'Central Resonance',
                    description:
                        'The deeper idea, truth, tension, conviction, or question that should continue echoing is clearly established.',
                ),

                $this->criterion(
                    key: 'memorable_moment',
                    label: 'Memorable Moment',
                    description:
                        'The defining moment, image, phrase, shift, silence, or musical event the listener should remember is sufficiently established.',
                ),

                $this->criterion(
                    key: 'emotional_resolution',
                    label: 'Emotional Resolution',
                    description:
                        'The emotional condition in which the Work leaves the listener is clearly understood.',
                ),

                $this->criterion(
                    key: 'call_to_reflection',
                    label: 'Call to Reflection',
                    description:
                        'The reflection, question, remembrance, or realization invited by the Work is sufficiently established.',
                ),

                $this->criterion(
                    key: 'desired_transformations',
                    label: 'Desired Transformations',
                    description:
                        'The intended changes or lasting effects the Work hopes to produce are clearly identified.',
                ),

                $this->criterion(
                    key: 'legacy_markers',
                    label: 'Legacy Markers',
                    description:
                        'The defining images, ideas, values, symbols, or experiences by which the Work should be remembered are sufficiently established.',
                ),
            ],

            completionThreshold: 80,
        );
    }
}