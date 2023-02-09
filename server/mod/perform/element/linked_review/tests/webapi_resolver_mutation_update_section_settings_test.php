<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_linked_review
 */

use mod_perform\constants;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section;
use mod_perform\state\activity\draft;
use mod_perform\testing\generator as perform_generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\update_section_settings
 *
 * @group perform
 */
class performelement_linked_review_webapi_resolver_mutation_update_section_settings_testcase extends advanced_testcase {

    private const MUTATION = 'mod_perform_update_section_settings';
    private const TYPE = 'mod_perform_section';

    use webapi_phpunit_helper;

    public function test_remove_relationship_used_in_review_question(): void {
        self::setAdminUser();
        $activity = perform_generator::instance()->create_activity_in_container(['activity_status' => draft::get_code()]);
        $section = perform_generator::instance()->create_section($activity);
        perform_generator::instance()->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );
        perform_generator::instance()->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        $subject_relationship_id = perform_generator::instance()->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;
        $manager_relationship_id = perform_generator::instance()->get_core_relationship(constants::RELATIONSHIP_MANAGER)->id;

        $element = element::create($activity->get_context(), 'linked_review', 'title', '', json_encode([
            'content_type' => 'totara_competency',
            'content_type_settings' => [],
            'selection_relationships' => [$subject_relationship_id],
        ]));

        $section_element = perform_generator::instance()->create_section_element($section, $element);

        // try to remove the subject relationship should fail
        $result = $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'section_id' => $section->id,
                'relationships' => [
                    [
                        'core_relationship_id' => $manager_relationship_id,
                        'can_view' => true,
                        'can_answer' => false,
                    ],
                ],
                'title' => 'my new title',
            ]
        ]);

        $this->assertArrayHasKey('section', $result);
        $this->assertArrayHasKey('validation_info', $result);

        $this->assertInstanceOf(section::class, $result['section']);
        /** @var section $actual_section */
        $actual_section = $result['section'];

        // Title changed but not the relationships
        $this->assertEquals('my new title', $actual_section->title);
        $this->assertEquals(
            $section->get_section_relationships()->count(),
            $actual_section->get_section_relationships()->count()
        );

        $this->assertEquals(
            [
                'title' => get_string('modal_can_not_delete_relationship_title', 'mod_perform'),
                'can_delete' => false,
                'reason' => [
                    'description' => get_string('section_relationship_used_in_linked_review', 'performelement_linked_review'),
                    'data' => [
                        $element->title
                    ]
                ]
            ],
            $result['validation_info']
        );

        // Now try to remove the other one which is not used

        $result = $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'section_id' => $section->id,
                'relationships' => [
                    [
                        'core_relationship_id' => $subject_relationship_id,
                        'can_view' => true,
                        'can_answer' => false,
                    ],
                ],
                'title' => 'my new title',
            ]
        ]);

        $this->assertArrayHasKey('section', $result);
        $this->assertArrayNotHasKey('validation_info', $result);

        $this->assertInstanceOf(section::class, $result['section']);
        /** @var section $actual_section */
        $actual_section = $result['section'];

        // Nothing changed
        $this->assertEquals('my new title', $actual_section->title);
        $this->assertCount(1, $actual_section->get_section_relationships());
        $this->assertEquals($subject_relationship_id, $actual_section->get_section_relationships()->first()->core_relationship_id);
    }


}
