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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

use mod_perform\constants;
use mod_perform\entity\activity\participant_instance;
use mod_perform\models\activity\element;
use mod_perform\testing\generator as perform_generator;
use performelement_linked_review\entity\linked_review_content_response;
use performelement_linked_review\models\linked_review_content;
use performelement_linked_review\testing\generator as linked_review_generator;

/**
 * @group perform
 * @group perform_element
 */
class performelement_linked_review_deletion_testcase extends advanced_testcase {

    public function test_activity_deletion(): void {
        global $DB;
        self::setAdminUser();
        [$activity1, $section1, $element1, $section_element1] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user1, $subject_instance1, $participant_instance1] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity1, 'section' => $section1,
        ]);
        $child_element1 = perform_generator::instance()->create_child_element(['parent_element' => $element1]);
        $competency_assignment1_id = linked_review_generator::instance()->create_competency_assignment(['user' => $user1])->id;
        $linked_content1 = linked_review_content::create(
            $competency_assignment1_id, $section_element1->id, $participant_instance1->id, false
        );
        $content_response1 = $this->create_content_response($participant_instance1, $linked_content1, $child_element1);

        [$activity2, $section2, $element2, $section_element2] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user2, $subject_instance2, $participant_instance2] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity2, 'section' => $section2,
        ]);
        $child_element2 = perform_generator::instance()->create_child_element(['parent_element' => $element2]);
        $competency_assignment2_id = linked_review_generator::instance()->create_competency_assignment(['user' => $user2])->id;
        $linked_content2 = linked_review_content::create(
            $competency_assignment2_id, $section_element2->id, $participant_instance2->id, false
        );
        $content_response2 = $this->create_content_response($participant_instance2, $linked_content2, $child_element2);

        $this->assertEquals(2, $DB->count_records('perform_element_linked_review_content'));
        $this->assertEquals(2, $DB->count_records('perform_element_linked_review_content_response'));

        $activity1->delete();

        $this->assertFalse($DB->record_exists('perform_element_linked_review_content', ['id' => $linked_content1->id]));
        $this->assertFalse($DB->record_exists('perform_element_linked_review_content_response', ['id' => $content_response1->id]));
        $this->assertTrue($DB->record_exists('perform_element_linked_review_content', ['id' => $linked_content2->id]));
        $this->assertTrue($DB->record_exists('perform_element_linked_review_content_response', ['id' => $content_response2->id]));

        $activity2->delete();

        $this->assertEquals(0, $DB->count_records('perform_element_linked_review_content'));
        $this->assertEquals(0, $DB->count_records('perform_element_linked_review_content_response'));
    }

    public function test_participant_instance_deletion(): void {
        global $DB;
        self::setAdminUser();
        [$activity, $section, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user1, $subject_instance, $participant_instance1] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity, 'section' => $section,
        ]);
        [$user2, $subject_instance, $participant_instance2] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity, 'section' => $section, 'subject_instance' => $subject_instance,
            'relationship' => constants::RELATIONSHIP_MANAGER,
        ]);
        $child_element1 = perform_generator::instance()->create_child_element(['parent_element' => $element]);
        $competency_assignment_id = linked_review_generator::instance()->create_competency_assignment(['user' => $user1])->id;
        $linked_content = linked_review_content::create(
            $competency_assignment_id, $section_element->id, $participant_instance1->id, false
        );

        $content_response1 = $this->create_content_response($participant_instance1, $linked_content, $child_element1);
        $content_response2 = $this->create_content_response($participant_instance2, $linked_content, $child_element1);

        $this->assertEquals(1, $DB->count_records('perform_element_linked_review_content'));
        $this->assertEquals(2, $DB->count_records('perform_element_linked_review_content_response'));

        $participant_instance1->delete();

        $this->assertTrue($DB->record_exists('perform_element_linked_review_content', ['id' => $linked_content->id]));
        $this->assertFalse($DB->record_exists('perform_element_linked_review_content_response', ['id' => $content_response1->id]));
        $this->assertTrue($DB->record_exists('perform_element_linked_review_content_response', ['id' => $content_response2->id]));

        $participant_instance2->delete();

        $this->assertEquals(1, $DB->count_records('perform_element_linked_review_content'));
        $this->assertEquals(0, $DB->count_records('perform_element_linked_review_content_response'));
    }

    public function test_linked_content_deletion(): void {
        global $DB;
        self::setAdminUser();
        [$activity, $section, $element, $section_element] = linked_review_generator::instance()
            ->create_activity_with_section_and_review_element();
        [$user, $subject_instance, $participant_instance] = linked_review_generator::instance()->create_participant_in_section([
            'activity' => $activity, 'section' => $section
        ]);
        $child_element1 = perform_generator::instance()->create_child_element(['parent_element' => $element]);
        $competency_assignment1_id = linked_review_generator::instance()->create_competency_assignment(['user' => $user->id])->id;
        $competency_assignment2_id = linked_review_generator::instance()->create_competency_assignment(['user' => $user->id])->id;

        $linked_content1 = linked_review_content::create(
            $competency_assignment1_id, $section_element->id, $participant_instance->id, false
        );
        $content_response1 = $this->create_content_response($participant_instance, $linked_content1, $child_element1);

        $linked_content2 = linked_review_content::create(
            $competency_assignment2_id, $section_element->id, $participant_instance->id, false
        );
        $content_response2 = $this->create_content_response($participant_instance, $linked_content2, $child_element1);

        $this->assertEquals(2, $DB->count_records('perform_element_linked_review_content'));
        $this->assertEquals(2, $DB->count_records('perform_element_linked_review_content_response'));

        $linked_content1->delete();

        $this->assertFalse($DB->record_exists('perform_element_linked_review_content', ['id' => $linked_content1->id]));
        $this->assertFalse($DB->record_exists('perform_element_linked_review_content_response', ['id' => $content_response1->id]));
        $this->assertTrue($DB->record_exists('perform_element_linked_review_content', ['id' => $linked_content2->id]));
        $this->assertTrue($DB->record_exists('perform_element_linked_review_content_response', ['id' => $content_response2->id]));

        $linked_content2->delete();

        $this->assertEquals(0, $DB->count_records('perform_element_linked_review_content'));
        $this->assertEquals(0, $DB->count_records('perform_element_linked_review_content_response'));
    }

    /**
     * @param participant_instance $participant_instance
     * @param linked_review_content $lined_review_content
     * @param element $child_element
     * @param string|null $response_data
     * @return linked_review_content_response
     */
    private function create_content_response(
        participant_instance $participant_instance,
        linked_review_content $lined_review_content,
        element $child_element,
        string $response_data = null
    ): linked_review_content_response {
        $response = new linked_review_content_response();
        $response->linked_review_content_id = $lined_review_content->id;
        $response->child_element_id = $child_element->id;
        $response->participant_instance_id = $participant_instance->id;
        $response->response_data = $response_data ?? json_encode("Response");
        $response->save();
        return $response;
    }

}
