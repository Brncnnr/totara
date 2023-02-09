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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

use core\collection;
use mod_perform\constants;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section_element;
use performelement_linked_review\entity\linked_review_content as linked_review_content_entity;
use performelement_linked_review\testing\generator as linked_review_generator;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * @group perform_element
 */
class performelement_linked_review_webapi_resolver_mutation_update_linked_review_content_testcase extends advanced_testcase {

    private const MUTATION = 'performelement_linked_review_update_linked_review_content';

    use webapi_phpunit_helper;

    public function test_resolve_mutation_successful(): void {
        self::setAdminUser();
        [$activity, $section, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user1, $subject_instance, $participant_instance1] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity, 'section' => $section
        ]);
        [$user2, $subject_instance, $participant_instance2] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity, 'section' => $section, 'subject_instance' => $subject_instance,
            'relationship' => constants::RELATIONSHIP_MANAGER,
        ]);
        $content_id1 = linked_review_generator::instance()->create_competency_assignment(['user' => $user1])->id;
        $content_id2 = linked_review_generator::instance()->create_competency_assignment(['user' => $user1])->id;
        $content_id3 = linked_review_generator::instance()->create_competency_assignment(['user' => $user1])->id;
        $content = [
            ['id' => $content_id1],
            ['id' => $content_id2],
            ['id' => $content_id3]
        ];

        $content_type = json_decode($element->data, true)['content_type'] ?? null;
        $this->assertNotEmpty(trim($content_type));
        self::setUser($user1);

        $args = [
            'input' => [
                'content' => json_encode($content),
                'section_element_id' => $section_element->id,
                'participant_instance_id' => $participant_instance1->id,
            ],
        ];

        $this->assertEquals(0, linked_review_content_entity::repository()->count());

        $this->resolve_graphql_mutation(self::MUTATION, $args);

        /** @var linked_review_content_entity[]|collection $linked_content */
        $linked_content = linked_review_content_entity::repository()->get();
        $this->assertEquals(3, $linked_content->count());
        $this->assertEquals(count($content), $linked_content->count());
        foreach ($linked_content as $actual_content) {
            $this->assertEquals($section_element->id, $actual_content->section_element_id);
            $this->assertEquals($participant_instance1->subject_instance_id, $actual_content->subject_instance_id);
            $this->assertContainsEquals($actual_content->content_id, array_column($content, 'id'));
            $this->assertEquals($content_type, $actual_content->content_type);
            $this->assertGreaterThan(0, $actual_content->created_at);
        }
    }

    public function test_element_is_not_a_linked_review_element(): void {
        self::setAdminUser();
        [$activity, $section, $element, $review_section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user, $subject_instance, $participant_instance] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity, 'section' => $section,
        ]);
        $other_user = self::getDataGenerator()->create_user();
        $short_text_element = element::create($activity->get_context(), 'short_text', 'A');
        $short_text_section_element = section_element::create($section, $short_text_element, 4);
        self::setUser($other_user);

        $args = [
            'input' => [
                'content' => json_encode([]),
                'section_element_id' => $short_text_section_element->id,
                'participant_instance_id' => $participant_instance->id,
            ],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('not a linked review element');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_element_is_not_in_participant_section(): void {
        self::setAdminUser();
        [$activity, $section] = linked_review_generator::instance()->create_activity_with_section_and_review_element();
        [,, $element, $section_element] = linked_review_generator::instance()->create_activity_with_section_and_review_element();
        [$user, $subject_instance, $participant_instance] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity, 'section' => $section
        ]);
        $other_user = self::getDataGenerator()->create_user();
        self::setUser($other_user);

        $args = [
            'input' => [
                'content' => json_encode([]),
                'section_element_id' => $section_element->id,
                'participant_instance_id' => $participant_instance->id,
            ],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('does not share the same section');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_user_is_not_a_participant(): void {
        self::setAdminUser();
        [$activity, $section, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user, $subject_instance, $participant_instance] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity, 'section' => $section,
        ]);
        $other_user = self::getDataGenerator()->create_user();
        self::setUser($other_user);

        $args = [
            'input' => [
                'content' => json_encode([]),
                'section_element_id' => $section_element->id,
                'participant_instance_id' => $participant_instance->id,
            ],
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('you do not currently have permissions');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_invalid_content_ids_specified(): void {
        self::setAdminUser();
        [$activity, $section, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user, $subject_instance, $participant_instance] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity, 'section' => $section,
        ]);
        self::setUser($user);

        $args = [
            'input' => [
                'content' => json_encode([
                    ['id' => -1],
                    ['id' => -2],
                    ['id' => -3],
                    ['id' => -4]
                ]),
                'section_element_id' => $section_element->id,
                'participant_instance_id' => $participant_instance->id,
            ],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Not all the specified content IDs actually exist');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_content_can_not_be_overridden() {
        self::setAdminUser();

        [$activity, $section, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user1, $subject_instance, $participant_instance1] = linked_review_generator::instance()->create_participant_in_section(
            [
                'activity' => $activity,
                'section' => $section,
            ]
        );
        [$user2, $subject_instance2, $participant_instance2] = linked_review_generator::instance()->create_participant_in_section(
            [
                'activity' => $activity,
                'section' => $section,
                'subject_instance' => $subject_instance,
            ]
        );
        $first_content_id = linked_review_generator::instance()->create_competency_assignment(['user' => $user1])->id;
        $last_content_id = linked_review_generator::instance()->create_competency_assignment(['user' => $user1])->id;

        // User1 selects contents
        self::setUser($user1);

        $args = [
            'input' => [
                'content' => json_encode([['id' => $first_content_id]]),
                'section_element_id' => $section_element->id,
                'participant_instance_id' => $participant_instance1->id,
            ],
        ];

        $this->assertEquals(0, linked_review_content_entity::repository()->count());

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        /** @var linked_review_content_entity[]|collection $linked_content */
        $linked_content = linked_review_content_entity::repository()->get();
        foreach ($linked_content as $content) {
            $this->assertEquals($section_element->id, $content->section_element_id);
            $this->assertEquals($participant_instance1->subject_instance_id, $content->subject_instance_id);
            $this->assertEquals($content->content_id, $first_content_id);
        }

        // User2 selects contents for the same section element and same subject instance
        self::setUser($user2);
        $args = [
            'input' => [
                'content' => json_encode([['id' => $last_content_id]]),
                'section_element_id' => $section_element->id,
                'participant_instance_id' => $participant_instance2->id,
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertFalse($result['validation_info']['can_update']);

        $expected_relationship_name = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT)->get_name();
        $expected_message =  get_string(
            'can_not_select_content_message', 'performelement_linked_review',
            ['selector' => fullname($user1), 'relationship' => $expected_relationship_name]
        );

        $this->assertEquals($expected_message, $result['validation_info']['description']);

        foreach ($linked_content as $content) {
            $this->assertEquals($section_element->id, $content->section_element_id);
            $this->assertEquals($participant_instance1->subject_instance_id, $content->subject_instance_id);
            $this->assertEquals($content->content_id, $first_content_id);
        }
    }

    public function test_feature_disabled(): void {
        advanced_feature::disable('performance_activities');
        self::setAdminUser();

        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature performance_activities is not available.');

        $this->resolve_graphql_mutation(self::MUTATION, []);
    }

    public function test_require_login(): void {
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, []);
    }

}
