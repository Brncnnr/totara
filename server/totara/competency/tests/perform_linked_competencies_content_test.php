<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

use core\collection;
use core\orm\query\builder;
use mod_perform\constants;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\subject_instance;
use pathway_perform_rating\models\perform_rating;
use performelement_linked_review\models\linked_review_content;
use performelement_linked_review\testing\generator as linked_review_generator;
use totara_competency\expand_task;
use totara_competency\models\assignment as assignment_model;
use totara_competency\performelement_linked_review\competency_assignment;
use totara_competency\testing\generator as competency_generator;
use totara_core\advanced_feature;
use totara_core\relationship\relationship;
use totara_core\strftime;

/**
 * @group totara_competency
 */
class totara_competency_perform_linked_competencies_content_testcase extends advanced_testcase {

    protected function setUp(): void {
        if (!core_component::get_plugin_directory('mod', 'perform')
            || !core_component::get_plugin_directory('performelement', 'linked_review')
        ) {
            $this->markTestSkipped('Perform or the linked review element plugin is not installed');
        }
    }

    public function test_load_with_empty_content_items_collection() {
        $user = $this->getDataGenerator()->create_user();
        $dummy_subject_instance = subject_instance::load_by_entity(new subject_instance_entity([
            'id' => 123456,
            'subject_user_id' => $user->id,
        ]));

        $this->setUser($user);

        $content_type = new competency_assignment(context_system::instance());

        $result = $content_type->load_content_items(
            $dummy_subject_instance,
            collection::new([]),
            null,
            true,
            time()
        );

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_load_competency_items_which_do_not_exist() {
        $user = $this->getDataGenerator()->create_user();
        $dummy_subject_instance = subject_instance::load_by_entity(new subject_instance_entity([
            'id' => 123456,
            'subject_user_id' => $user->id,
        ]));
        $nonexistent_competency_content_items = collection::new([
            ['content_id' => -1],
            ['content_id' => -2],
        ]);

        $this->setUser($user);

        $content_type = new competency_assignment(context_system::instance());

        $result = $content_type->load_content_items(
            $dummy_subject_instance,
            $nonexistent_competency_content_items,
            null,
            true,
            time()
        );

        $this->assertEmpty($result);
    }

    public function test_load_competency_items() {
        $user1 = $this->getDataGenerator()->create_user();
        $dummy_subject_instance = subject_instance::load_by_entity(new subject_instance_entity([
            'id' => 123456,
            'subject_user_id' => $user1->id,
        ]));

        $scale_values = [
            ['name' => 'a', 'proficient' => 1, 'sortorder' => 1, 'default' => 0, 'description' => '<p>A</p>'],
            ['name' => 'b', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
            ['name' => 'c', 'proficient' => 0, 'sortorder' => 3, 'default' => 1],
        ];
        $scale1 = $this->generator()->create_scale('comp', null, $scale_values);
        $framework1 = $this->generator()->create_framework($scale1);

        $competency1 = $this->generator()->create_competency(null, $framework1);
        $competency2 = $this->generator()->create_competency();

        $assignment_generator = $this->generator()->assignment_generator();
        $assignment1 = $assignment_generator->create_user_assignment($competency1->id, $user1->id);
        $assignment2 = $assignment_generator->create_user_assignment($competency2->id, $user1->id);

        (new expand_task(builder::get_db()))->expand_all();

        $this->setUser($user1);

        $created_at = time();

        $content_items = collection::new([
            ['content_id' => $assignment1->id],
            ['content_id' => 666],
            ['content_id' => $assignment2->id],
        ]);

        $content_type = new competency_assignment(context_system::instance());

        $result = $content_type->load_content_items(
            $dummy_subject_instance,
            $content_items,
            null,
            true,
            $created_at
        );

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);

        $actual_first_item = array_filter($result, function (array $item) use ($assignment1) {
            return $item['id'] == $assignment1->id;
        });
        $actual_first_item = array_shift($actual_first_item);

        $expected_assignment1 = assignment_model::load_by_id($assignment1->id);

        $expected_scale_values1 = $expected_assignment1
            ->get_assignment_specific_scale()
            ->values
            ->sort('sortorder', 'asc', false);
        $expected_scale1 = [];
        foreach ($expected_scale_values1 as $expected_scale_value) {
            $expected_scale1[] = [
                'id' => $expected_scale_value->id,
                'name' => $expected_scale_value->name,
                'proficient' => (bool) $expected_scale_value->proficient,
                'sort_order' => $expected_scale_value->sortorder,
                'description_html' => $expected_scale_value->description,
            ];
        }

        $expected_content_first_item = [
            'id' => $expected_assignment1->get_id(),
            'competency' => [
                'id' => $expected_assignment1->get_competency()->id,
                'display_name' => $expected_assignment1->get_competency()->display_name,
                'description' => $expected_assignment1->get_competency()->description,
            ],
            'assignment' => [
                'reason_assigned' => $expected_assignment1->get_reason_assigned(),
            ],
            'achievement' => [
                'id' => 0,
                'name' => get_string('no_value_achieved', 'totara_competency'),
                'proficient' => false,
            ],
            'scale_values' => $expected_scale1,
            'can_rate' => false,
            'can_view_rating' => true,
            'rating' => null,
        ];

        $this->assertEquals($expected_content_first_item, $actual_first_item);

        $actual_third_item = array_filter($result, function (array $item) use ($assignment2) {
            return $item['id'] == $assignment2->id;
        });
        $actual_third_item = array_shift($actual_third_item);

        $expected_assignment2 = assignment_model::load_by_id($assignment2->id);

        $expected_scale_values2 = $expected_assignment2
            ->get_assignment_specific_scale()
            ->values
            ->sort('sortorder', 'asc', false);
        $expected_scale2 = [];
        foreach ($expected_scale_values2 as $expected_scale_value) {
            $expected_scale2[] = [
                'id' => $expected_scale_value->id,
                'name' => $expected_scale_value->name,
                'proficient' => (bool) $expected_scale_value->proficient,
                'sort_order' => $expected_scale_value->sortorder,
                'description_html' => $expected_scale_value->description,
            ];
        }

        $expected_content_third_item = [
            'id' => $expected_assignment2->get_id(),
            'competency' => [
                'id' => $expected_assignment2->get_competency()->id,
                'display_name' => $expected_assignment2->get_competency()->display_name,
                'description' => $expected_assignment2->get_competency()->description,
            ],
            'assignment' => [
                'reason_assigned' => $expected_assignment2->get_reason_assigned(),
            ],
            'achievement' => [
                'id' => 0,
                'name' => get_string('no_value_achieved', 'totara_competency'),
                'proficient' => false,
            ],
            'scale_values' => $expected_scale2,
            'can_rate' => false,
            'can_view_rating' => true,
            'rating' => null,
        ];

        $this->assertEquals($expected_content_third_item, $actual_third_item);
    }

    public function test_load_competency_items_with_rating_enabled() {
        self::setAdminUser();

        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $manager_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER);

        [$activity1, $section1, $element1, $section_element1] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element([
                'content_type_settings' => [
                    'enable_rating' => true,
                    'rating_relationship' => $subject_relationship->id
                ]
            ]);
        [$activity2, $section2, $element2, $section_element2] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user1, $subject_instance1, $participant_instance1] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity1,
            'section' => $section1,
        ]);
        [$user3, $subject_instance1, $participant_instance2] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity1,
            'section' => $section1,
            'subject_instance' => $subject_instance1,
            'relationship' => $manager_relationship
        ]);

        $assignment1 = linked_review_generator::instance()->create_competency_assignment(['user' => $user1]);
        $content_id1 = $assignment1->id;

        $content_items1 = linked_review_content::create_multiple(
            [$content_id1],
            $section_element1->id,
            $participant_instance1->id, false
        );

        $this->setUser($user1);

        $created_at = time();

        $content_type = new competency_assignment(context_system::instance());

        $participant_section1 = participant_section::repository()
            ->where('participant_instance_id', $participant_instance1->id)
            ->order_by('id')
            ->first();

        $result = $content_type->load_content_items(
            $subject_instance1,
            $content_items1,
            $participant_section1,
            true,
            $created_at
        );

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);

        $expected_assignment1 = assignment_model::load_by_id($assignment1->id);

        $expected_scale_values1 = $expected_assignment1
            ->get_assignment_specific_scale()
            ->values
            ->sort('sortorder', 'asc', false);
        $expected_scale1 = [];
        foreach ($expected_scale_values1 as $expected_scale_value) {
            $expected_scale1[] = [
                'id' => $expected_scale_value->id,
                'name' => $expected_scale_value->name,
                'proficient' => (bool) $expected_scale_value->proficient,
                'sort_order' => $expected_scale_value->sortorder,
                'description_html' => $expected_scale_value->description,
            ];
        }

        $expected_content = [
            'id' => $expected_assignment1->get_id(),
            'competency' => [
                'id' => $expected_assignment1->get_competency()->id,
                'display_name' => $expected_assignment1->get_competency()->display_name,
                'description' => $expected_assignment1->get_competency()->description,
            ],
            'assignment' => [
                'reason_assigned' => $expected_assignment1->get_reason_assigned(),
            ],
            'achievement' => [
                'id' => 0,
                'name' => get_string('no_value_achieved', 'totara_competency'),
                'proficient' => false,
            ],
            'scale_values' => $expected_scale1,
            'can_rate' => true,
            'can_view_rating' => true,
            'rating' => null,
        ];

        $this->assertEquals($expected_content, array_shift($result));

        // Now even without being able to view the ratings raters should always see it
        $result = $content_type->load_content_items(
            $subject_instance1,
            $content_items1,
            $participant_section1,
            false,
            $created_at
        );

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);

        $this->assertEquals($expected_content, array_shift($result));

        // Now as the manager
        $this->setUser($user3);

        $participant_section2 = participant_section::repository()
            ->where('participant_instance_id', $participant_instance2->id)
            ->order_by('id')
            ->first();

        $result = $content_type->load_content_items(
            $subject_instance1,
            $content_items1,
            $participant_section2,
            true,
            $created_at
        );

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);

        $expected_content = [
            'id' => $expected_assignment1->get_id(),
            'competency' => [
                'id' => $expected_assignment1->get_competency()->id,
                'display_name' => $expected_assignment1->get_competency()->display_name,
                'description' => $expected_assignment1->get_competency()->description,
            ],
            'assignment' => [
                'reason_assigned' => $expected_assignment1->get_reason_assigned(),
            ],
            'achievement' => [
                'id' => 0,
                'name' => get_string('no_value_achieved', 'totara_competency'),
                'proficient' => false,
            ],
            'scale_values' => $expected_scale1,
            'can_rate' => false,
            'can_view_rating' => true,
            'rating' => null,
        ];

        $this->assertEquals($expected_content, array_shift($result));

        // Without being able to view ratings non-raters should get can_view_ratings = false
        $result = $content_type->load_content_items(
            $subject_instance1,
            $content_items1,
            $participant_section2,
            false,
            $created_at
        );

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);

        $expected_content = [
            'id' => $expected_assignment1->get_id(),
            'competency' => [
                'id' => $expected_assignment1->get_competency()->id,
                'display_name' => $expected_assignment1->get_competency()->display_name,
                'description' => $expected_assignment1->get_competency()->description,
            ],
            'assignment' => [
                'reason_assigned' => $expected_assignment1->get_reason_assigned(),
            ],
            'achievement' => [
                'id' => 0,
                'name' => get_string('no_value_achieved', 'totara_competency'),
                'proficient' => false,
            ],
            'scale_values' => $expected_scale1,
            'can_rate' => false,
            'can_view_rating' => false,
            'rating' => null,
        ];

        $this->assertEquals($expected_content, array_shift($result));

        $this->setUser($user1);

        $rating_created_at = time();
        $rating_scale_value = $expected_scale_values1->first();

        // Now give a rating
        perform_rating::create(
            $assignment1->competency_id,
            $rating_scale_value->id,
            $participant_instance1->id,
            $section_element1->id,
            $rating_created_at
        );

        // Now the rating should be included
        $result = $content_type->load_content_items(
            $subject_instance1,
            $content_items1,
            $participant_section1,
            false,
            $created_at
        );

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);

        $expected_content = [
            'id' => $expected_assignment1->get_id(),
            'competency' => [
                'id' => $expected_assignment1->get_competency()->id,
                'display_name' => $expected_assignment1->get_competency()->display_name,
                'description' => $expected_assignment1->get_competency()->description,
            ],
            'assignment' => [
                'reason_assigned' => $expected_assignment1->get_reason_assigned(),
            ],
            'achievement' => [
                'id' => 0,
                'name' => get_string('no_value_achieved', 'totara_competency'),
                'proficient' => false,
            ],
            'scale_values' => $expected_scale1,
            'can_rate' => false,
            'can_view_rating' => true,
            'rating' => [
                'created_at' => trim(strftime::format('%e %B %Y', $rating_created_at)),
                'rater_user' => [
                    'fullname' => fullname($user1),
                ],
                'scale_value' => [
                    'name' => $rating_scale_value->name,
                    'id' => $rating_scale_value->id
                ]
            ],
        ];

        $this->assertEquals($expected_content, array_shift($result));
    }

    public function test_feature_disabled() {
        $user1 = $this->getDataGenerator()->create_user();
        $dummy_subject_instance = subject_instance::load_by_entity(new subject_instance_entity([
            'id' => 123456,
            'subject_user_id' => $user1->id,
        ]));

        $competency1 = $this->generator()->create_competency();
        $competency2 = $this->generator()->create_competency();

        $assignment_generator = $this->generator()->assignment_generator();
        $assignment1 = $assignment_generator->create_user_assignment($competency1->id, $user1->id);
        $assignment2 = $assignment_generator->create_user_assignment($competency2->id, $user1->id);

        (new expand_task(builder::get_db()))->expand_all();

        $this->setUser($user1);

        $content_items = collection::new([
            ['content_id' => $assignment1->id],
            ['content_id' => 666],
            ['content_id' => $assignment2->id],
        ]);

        advanced_feature::disable('competency_assignment');

        $content_type = new competency_assignment(context_system::instance());
        $result = $content_type->load_content_items(
            $dummy_subject_instance,
            $content_items,
            null, true,
            time()
        );

        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
    }

    public function test_get_display_settings() {
        $display_settings = competency_assignment::get_display_settings([]);
        $subject_relationship = relationship::load_by_idnumber('subject');

        $this->assertEquals(
            [get_string('enable_performance_rating', 'totara_competency') => get_string('no')],
            $display_settings
        );

        $display_settings = competency_assignment::get_display_settings([
            'enable_rating' => false
        ]);

        $this->assertEquals(
            [get_string('enable_performance_rating', 'totara_competency') => get_string('no')],
            $display_settings
        );

        $display_settings = competency_assignment::get_display_settings([
            'enable_rating' => true
        ]);

        $this->assertEquals(
            [get_string('enable_performance_rating', 'totara_competency') => get_string('yes')],
            $display_settings
        );

        $display_settings = competency_assignment::get_display_settings([
            'enable_rating' => true,
            'rating_relationship' => $subject_relationship->id,
        ]);

        $this->assertEquals(
            [
                get_string('enable_performance_rating', 'totara_competency') => get_string('yes'),
                get_string('enable_performance_rating_participant', 'totara_competency') => $subject_relationship->get_name(),
            ],
            $display_settings
        );
    }

    /**
     * Get competeny specific generator
     *
     * @return competency_generator
     */
    protected function generator() {
        return competency_generator::instance();
    }

}