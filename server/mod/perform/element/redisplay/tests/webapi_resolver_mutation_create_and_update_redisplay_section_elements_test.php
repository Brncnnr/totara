<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\section_element_reference;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\testing\generator;
use performelement_custom_rating_scale\custom_rating_scale;
use performelement_redisplay\redisplay;
use performelement_numeric_rating_scale\numeric_rating_scale;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * @group perform_element
 */
class mod_perform_webapi_resolver_mutation_create_and_update_redisplay_section_elements_testcase extends advanced_testcase {
    private const CREATE_MUTATION = 'mod_perform_create_element_in_section';
    private const UPDATE_MUTATION = 'mod_perform_update_element_in_section';

    use webapi_phpunit_helper;

    public function test_create_and_update_redisplay_section_elements(): void {
        self::setAdminUser();

        $perform_generator = generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'element' => [
                    'plugin_name' => numeric_rating_scale::get_plugin_name(),
                    'element_details' => [
                        'title' => 'Original source numeric rating scale',
                        'identifier' => 'num-rating-scale',
                        'data' => '{}',
                        'is_required' => true,
                    ]
                ],
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::CREATE_MUTATION, $args);
        [$source_section_element] = $this->assert_correct_elements_returned($result, 1);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'element' => [
                    'plugin_name' => custom_rating_scale::get_plugin_name(),
                    'element_details' => [
                        'title' => 'Other source, custom rating scale',
                        'identifier' => 'custom-rating-scale',
                        'data' => '{}',
                        'is_required' => true,
                    ],
                ],
                'after_section_element_id' => $source_section_element->get_id(),
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::CREATE_MUTATION, $args);
        [$source_section_element, $other_source_section_element] = $this->assert_correct_elements_returned($result, 2);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'element' => [
                    'plugin_name' => redisplay::get_plugin_name(),
                    'element_details' => [
                        'title' => 'Redisplay element',
                        'identifier' => 're-element',
                        'data' => json_encode([
                            redisplay::SOURCE_SECTION_ELEMENT_ID => $source_section_element->id,
                        ], JSON_THROW_ON_ERROR),
                        'is_required' => false,
                    ],
                ],
                'after_section_element_id' => $other_source_section_element->get_id(),
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::CREATE_MUTATION, $args);
        [
            $source_section_element,
            $other_source_section_element,
            $redisplay_section_element
        ] = $this->assert_correct_elements_returned($result, 3);

        /** @var respondable_element_plugin $source_plugin */
        $source_plugin = $source_section_element->get_element()->get_element_plugin();

        self::assertEquals(
            [
                redisplay::SOURCE_SECTION_ELEMENT_ID => $source_section_element->id,
                'activityId' => $activity->id,
                'activityName' => $activity->name,
                'activityStatus' => 'Active',
                'elementTitle' => 'Original source numeric rating scale',
                'elementPluginName' => 'Rating scale: Numeric',
                'elementPluginDisplayComponent' => $source_plugin->get_participant_response_component(),
                'relationships' => '{No responding relationships added yet}'
            ],
            json_decode($redisplay_section_element->get_element()->get_data(), true, 512, JSON_THROW_ON_ERROR)
        );

        $args = [
            'input' => [
                'section_element_id' => $redisplay_section_element->id,
                'element_details' => [
                    'title' => 'Redisplay element',
                    'identifier' => 're-element',
                    'data' => json_encode([
                        redisplay::SOURCE_SECTION_ELEMENT_ID => $other_source_section_element->id,
                    ], JSON_THROW_ON_ERROR),
                    'is_required' => false,
                ],
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::UPDATE_MUTATION, $args);

        [
            ,
            $other_source_section_element,
            $redisplay_section_element
        ] = $this->assert_correct_elements_returned($result, 3);

        /** @var respondable_element_plugin $other_source_plugin */
        $other_source_plugin = $other_source_section_element->get_element()->get_element_plugin();

        self::assertEquals(
            [
                redisplay::SOURCE_SECTION_ELEMENT_ID => $other_source_section_element->id,
                'activityId' => $activity->id,
                'activityName' => $activity->name,
                'activityStatus' => 'Active',
                'elementTitle' => 'Other source, custom rating scale',
                'elementPluginName' => 'Rating scale: Custom',
                'elementPluginDisplayComponent' => $other_source_plugin->get_participant_response_component(),
                'relationships' => '{No responding relationships added yet}'
            ],
            json_decode($redisplay_section_element->get_element()->get_data(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function test_update_redisplay_section_elements_with_deleted_section_element_reference(): void {
        self::setAdminUser();

        $perform_generator = generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);

        // Create two respondable question elements.
        $args = [
            'input' => [
                'section_id' => $section->id,
                'element' => [
                    'plugin_name' => numeric_rating_scale::get_plugin_name(),
                    'element_details' => [
                        'title' => 'Original source numeric rating scale',
                        'identifier' => 'num-rating-scale',
                        'data' => '{}',
                        'is_required' => true,
                    ]
                ],
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::CREATE_MUTATION, $args);
        [$source_section_element] = $this->assert_correct_elements_returned($result, 1);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'element' => [
                    'plugin_name' => custom_rating_scale::get_plugin_name(),
                    'element_details' => [
                        'title' => 'Other source, custom rating scale',
                        'identifier' => 'custom-rating-scale',
                        'data' => '{}',
                        'is_required' => true,
                    ],
                ],
                'after_section_element_id' => $source_section_element->get_id(),
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::CREATE_MUTATION, $args);
        [$source_section_element, $other_source_section_element] = $this->assert_correct_elements_returned($result, 2);

        // Create a redisplay element pointing to the first question.
        $args = [
            'input' => [
                'section_id' => $section->id,
                'element' => [
                    'plugin_name' => redisplay::get_plugin_name(),
                    'element_details' => [
                        'title' => 'Redisplay element',
                        'identifier' => 're-element',
                        'data' => json_encode([
                            redisplay::SOURCE_SECTION_ELEMENT_ID => $source_section_element->id,
                        ], JSON_THROW_ON_ERROR),
                        'is_required' => false,
                    ],
                ],
                'after_section_element_id' => $other_source_section_element->get_id(),
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::CREATE_MUTATION, $args);
        [
            $source_section_element,
            $other_source_section_element,
            $redisplay_section_element
        ] = $this->assert_correct_elements_returned($result, 3);

        // Make sure the section_element_reference records are as expected.
        self::assertEquals(1,
            section_element_reference::repository()
                ->where('source_section_element_id', $source_section_element->id)
                ->where('referencing_element_id', $redisplay_section_element->element_id)
                ->count()
        );
        self::assertEquals(0,
            section_element_reference::repository()
                ->where('source_section_element_id', $other_source_section_element->id)
                ->where('referencing_element_id', $redisplay_section_element->element_id)
                ->count()
        );

        // Delete the existing section_element_reference record pointing to the first question.
        // This can actually happen when the source activity gets deleted (it's the same activity here, but that's
        // not relevant to this test).
        section_element_reference::repository()
            ->where('source_section_element_id', $source_section_element->id)
            ->where('referencing_element_id', $redisplay_section_element->element_id)
            ->delete();

        // Updating to a different question must still be possible.
        $args = [
            'input' => [
                'section_element_id' => $redisplay_section_element->id,
                'element_details' => [
                    'title' => 'Redisplay element',
                    'identifier' => 're-element',
                    'data' => json_encode([
                        redisplay::SOURCE_SECTION_ELEMENT_ID => $other_source_section_element->id,
                    ], JSON_THROW_ON_ERROR),
                    'is_required' => false,
                ],
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::UPDATE_MUTATION, $args);
        $this->assert_correct_elements_returned($result, 3);

        self::assertEquals(0,
            section_element_reference::repository()
                ->where('source_section_element_id', $source_section_element->id)
                ->where('referencing_element_id', $redisplay_section_element->element_id)
                ->count()
        );
        self::assertEquals(1,
            section_element_reference::repository()
                ->where('source_section_element_id', $other_source_section_element->id)
                ->where('referencing_element_id', $redisplay_section_element->element_id)
                ->count()
        );
    }

    /**
     * @param array $result
     * @param int $element_count
     * @return section_element[]
     */
    private function assert_correct_elements_returned(array $result, int $element_count): array {
        /** @var section $section */
        $section = $result['section'];

        /** @var section_element[] $section_elements */
        $section_elements = $section->get_section_elements()->all(false);
        
        $source_section_element = $section_elements[0] ?? null;
        $other_source_section_element = $section_elements[1] ?? null;
        $redisplay_section_element = $section_elements[2] ?? null;

        self::assertCount($element_count, $section_elements);

        if ($element_count >= 1) {
            self::assertNotNull($source_section_element);
            self::assertEquals('Original source numeric rating scale', $source_section_element->get_element()->title);
        }

        if ($element_count >= 2) {
            self::assertNotNull($other_source_section_element);
            self::assertEquals('Other source, custom rating scale', $other_source_section_element->get_element()->title);
        }

        if ($element_count >= 3) {
            self::assertNotNull($redisplay_section_element);
            self::assertEquals('Redisplay element', $redisplay_section_element->get_element()->title);
        }

        return [$source_section_element, $other_source_section_element ?? null, $redisplay_section_element ?? null];
    }

}
