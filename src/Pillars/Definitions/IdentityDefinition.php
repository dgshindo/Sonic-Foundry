<?php
declare(strict_types=1);

namespace SonicFoundry\Pillars\Definitions;

use SonicFoundry\Pillars\Contracts\ConversationDefinition;
use SonicFoundry\Pillars\Contracts\MemoryDefinition;
use SonicFoundry\Pillars\Contracts\ProgressDefinition;
use SonicFoundry\Work\WorkPillar;

final class IdentityDefinition
    extends AbstractPillarDefinition
{
    public function pillar(): WorkPillar
    {
        return WorkPillar::Identity;
    }

    public function conversation(): ConversationDefinition
    {
        return new ConversationDefinition(
            promptPath: 'pillars/identity.md',
        );
    }

    public function memory(): MemoryDefinition
    {
        return new MemoryDefinition(
            fields: [
                $this->textField(
                    key: 'core_identity',
                    label: 'Core Identity',
                    emptyMessage:
                        'The essential identity of the Work has not yet been established.',
                ),

                $this->textField(
                    key: 'creative_voice',
                    label: 'Creative Voice',
                    emptyMessage:
                        'The creative voice has not yet been established.',
                ),

                $this->textField(
                    key: 'audience_promise',
                    label: 'Audience Promise',
                    emptyMessage:
                        'The promise made to the listener has not yet been established.',
                ),

                $this->textField(
                    key: 'authenticity_anchor',
                    label: 'Authenticity Anchor',
                    emptyMessage:
                        'The truth anchoring the Work has not yet been established.',
                ),

                $this->listField(
                    key: 'distinctive_qualities',
                    label: 'Distinctive Qualities',
                    emptyMessage:
                        'Distinctive qualities will appear after they are identified.',
                ),

                $this->listField(
                    key: 'core_values',
                    label: 'Core Values',
                    emptyMessage:
                        'Core values will appear after they are identified.',
                ),

                $this->listField(
                    key: 'identity_boundaries',
                    label: 'Identity Boundaries',
                    emptyMessage:
                        'Identity boundaries will appear after they are identified.',
                ),

                $this->textField(
                    key: 'creator_relationship',
                    label: 'Creator Relationship',
                    emptyMessage:
                        'The creator’s relationship to the Work has not yet been established.',
                ),
            ],

            extractionPromptPath:
                'memory/identity-extractor.md',
        );
    }

    public function progress(): ProgressDefinition
    {
        return new ProgressDefinition(
            title: 'Identity Readiness',

            promptPath:
                'progress/identity-evaluator.md',

            criteria: [
                $this->criterion(
                    key: 'core_identity',
                    label: 'Core Identity',
                    description:
                        'The Work has a clear and recognizable essential character.',
                ),

                $this->criterion(
                    key: 'creative_voice',
                    label: 'Creative Voice',
                    description:
                        'The expressive personality and manner of the Work are sufficiently established.',
                ),

                $this->criterion(
                    key: 'audience_promise',
                    label: 'Audience Promise',
                    description:
                        'The experience promised to the listener is clear.',
                ),

                $this->criterion(
                    key: 'authenticity_anchor',
                    label: 'Authenticity Anchor',
                    description:
                        'The truth or conviction keeping the Work honest is understood.',
                ),

                $this->criterion(
                    key: 'distinctive_qualities',
                    label: 'Distinctive Qualities',
                    description:
                        'The qualities making the Work recognizable and non-generic are identified.',
                ),

                $this->criterion(
                    key: 'core_values',
                    label: 'Core Values',
                    description:
                        'The principles or convictions embodied by the Work are clear.',
                ),

                $this->criterion(
                    key: 'identity_boundaries',
                    label: 'Identity Boundaries',
                    description:
                        'The directions or compromises that would make the Work feel false are understood.',
                ),

                $this->criterion(
                    key: 'creator_relationship',
                    label: 'Creator Relationship',
                    description:
                        'The creator’s personal relationship to the Work is sufficiently established.',
                ),
            ],

            completionThreshold: 80,
        );
    }
}