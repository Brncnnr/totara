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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_core
 */

use core\collection;
use core_phpunit\testcase;
use mod_perform\models\activity\subject_instance;
use totara_core\performelement_linked_review\learning;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\constants;
use mod_perform\models\activity\element;
use mod_perform\testing\generator as perform_generator;
use performelement_linked_review\models\linked_review_content;

class totara_core_perform_linked_review_user_learning_content_testcase extends testcase {

    /**
     * Create some users for testing.
     *
     * @return array
     */
    private function create_users() {
        $users = [];
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        return $users;
    }

    /**
     * Create some courses and assign some users for testing.
     *
     * @return array
     */
    private function create_courses(array $users = []) {
        if (empty($users)) {
            $users = $this->create_users();
        }

        $courses = [];
        $courses[] = $this->getDataGenerator()->create_course([
            'shortname' => 'c1',
            'fullname' => 'course1',
            'summary' => 'first course',
            'summaryformat' => FORMAT_HTML,
        ]);
        $courses[] = $this->getDataGenerator()->create_course([
            'shortname' => 'c2',
            'fullname' => 'course2',
            'summary' => 'second course',
            'summaryformat' => FORMAT_HTML,
        ]);
        $courses[] = $this->getDataGenerator()->create_course([
            'shortname' => 'c3',
            'fullname' => 'course3',
            'summary' => 'third course',
            'summaryformat' => FORMAT_HTML,
        ]);

        $this->getDataGenerator()->enrol_user($users[0]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[1]->id, 'student', 'manual');

        return $courses;
    }

    /**
     *
     * Create some programs and assign some users for testing.
     *
     * @return array
     */
    private function create_programs(array $users = []) {
        if (empty($users)) {
            $users = $this->create_users();
        }

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();
        $c4 = $this->getDataGenerator()->create_course();

        $programs = [];
        $programs[] = $prog_gen->create_program(['shortname' => 'p1', 'fullname' => 'prog1', 'summary' => 'first prog']);
        $programs[] = $prog_gen->create_program(['shortname' => 'p2', 'fullname' => 'prog2', 'summary' => 'second prog']);
        $programs[] = $prog_gen->create_program(['shortname' => 'p3', 'fullname' => 'prog3', 'summary' => 'third prog']);

        $prog_gen->add_courses_and_courseset_to_program($programs[0], [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->add_courses_and_courseset_to_program($programs[1], [[$c3], [$c4]], CERTIFPATH_STD);

        $prog_gen->assign_program($programs[0]->id, [$users[0]->id, $users[1]->id]);
        $prog_gen->assign_program($programs[1]->id, [$users[1]->id]);

        return $programs;
    }

    /**
     *
     * Create some certifications and assign some users for testing.
     *
     * @return array
     */
    private function create_certifications(array $users = []) {
        if (empty($users)) {
            $users = $this->create_users();
        }

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();
        $c4 = $this->getDataGenerator()->create_course();
        $c5 = $this->getDataGenerator()->create_course();
        $c6 = $this->getDataGenerator()->create_course();

        $certifications = [];
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c1', 'fullname' => 'cert1', 'summary' => 'first cert']);
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c2', 'fullname' => 'cert2', 'summary' => 'second cert']);
        $certifications[] = $prog_gen->create_certification(['shortname' => 'c3', 'fullname' => 'cert3', 'summary' => 'third cert']);

        $prog_gen->add_courses_and_courseset_to_program($certifications[0], [[$c1, $c2], [$c3]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certifications[0], [[$c1], [$c3]], CERTIFPATH_RECERT);

        $prog_gen->add_courses_and_courseset_to_program($certifications[1], [[$c4, $c5], [$c6]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certifications[1], [[$c4], [$c6]], CERTIFPATH_RECERT);

        $prog_gen->assign_program($certifications[0]->id, [$users[0]->id, $users[1]->id]);
        $prog_gen->assign_program($certifications[1]->id, [$users[1]->id]);

        return $certifications;
    }

    /**
     * Create some courses and assign some users for testing.
     *
     * @return array
     */
    private function create_learning_items(array $users = []) {
        if (empty($users)) {
            $users = $this->create_users();
        }

        $items = [];
        $items['courses'] = $this->create_courses($users);
        $items['programs'] = $this->create_programs($users);
        $items['certifications'] = $this->create_certifications($users);

        return $items;
    }

    /**
     * @return void
     */
    public function test_query_linked_learning_successful(): void {
        $user = $this->getDataGenerator()->create_user();
        $items = $this->create_learning_items();
        $dummy_subject_instance = subject_instance::load_by_entity(new subject_instance_entity([
            'id' => 123456,
            'subject_user_id' => $user->id,
        ]));

        $content_type = new learning(context_system::instance());
        $result = $content_type->load_content_items(
            $dummy_subject_instance,
            collection::new([
                (object)['content_id' => $items['courses'][0]->id, 'content_type' => "learning_course"],
                (object)['content_id' => $items['certifications'][0]->id, 'content_type' => "learning_certification"],
            ]),
            null,
            true,
            time()
        );

        $this->assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_load_learning_items(): void {
        // Create activity with the learning attached.
        $data = $this->create_activity_data();
        $user = $data->subject_user1;
        $learning_items = $data->learning_items;
        $created_at = time();

        // Set user to activity subject.
        self::setUser($user);

        // Get content items.
        $content_items = collection::new([
            (object)['content_id' => $learning_items['courses'][0]->id, 'content_type' => 'learning_course'],
            (object)['content_id' => - 123, 'content_type' => 'learning_certification'],
            (object)['content_id' => $learning_items['programs'][0]->id, 'content_type' => 'learning_program'],
        ]);

        // Create linked review learning instance.
        $content_type = new learning(context_system::instance());

        // Get content items for subject instance.
        $subject_instance_model = subject_instance::load_by_entity($data->subject_instance1);
        $result = $content_type->load_content_items(
            $subject_instance_model,
            $content_items,
            null,
            true,
            $created_at
        );

        self::assertIsArray($result);
        self::assertCount(2, $result);

        // Filter result to get learning_item 1.
        $learning_result_item1 = array_filter(
            $result,
            static function (array $item) use ($learning_items) {
                return (int)$item['id'] === (int)$learning_items['courses'][0]->id;
            }
        );
        $learning_result_item1 = array_shift($learning_result_item1);

        // Confirm that the basic values for evidence item 1 matches the expected result.
        $expected_content_evidence1 = [
            'id' => $learning_items['courses'][0]->id,
            'fullname' => $learning_items['courses'][0]->fullname,
            'description' => $learning_items['courses'][0]->summary,
            'image_src' => 'https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage',
            'url_view' => "https://www.example.com/moodle/course/view.php?id={$learning_items['courses'][0]->id}",
            'itemtype' => 'course',
            'progress' => null
        ];
        self::assertEquals($expected_content_evidence1, $learning_result_item1);
    }

    /**
     * @return stdClass
     */
    protected function create_activity_data(): stdClass {
        self::setAdminUser();
        $perform_generator = perform_generator::instance();
        $activity = $perform_generator->create_activity_in_container(['activity_name' => 'Test activity']);
        $section = $perform_generator->create_section($activity);

        // Create section relationships.
        $subject_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );
        $external_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_EXTERNAL]
        );

        // Assign subject_user to activity.
        $subject_user1 = self::getDataGenerator()->create_user(['firstname' => 'SubjectUser', 'One' => 'User']);
        $subject_instance1 = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $subject_user1->id
        ]);

        // Create subject participant instance.
        $subject_participant_section1 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user1,
            $subject_instance1->id,
            $section,
            $subject_section_relationship->core_relationship->id
        );

        // Create external participant instance.
        $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user1,
            $subject_instance1->id,
            $section,
            $external_section_relationship->core_relationship->id
        );

        // Assign subject_user to activity.
        $subject_user2 = self::getDataGenerator()->create_user(['firstname' => 'SubjectUser', 'Two' => 'User']);
        $subject_instance2 = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $subject_user2->id
        ]);

        $subject_user3 = self::getDataGenerator()->create_user(['firstname' => 'SubjectUser', 'Three' => 'User']);

        // Create subject participant instance.
        $subject_participant_section2 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user2,
            $subject_instance2->id,
            $section,
            $subject_section_relationship->core_relationship->id
        );

        // Create external participant instance.
        $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user2,
            $subject_instance2->id,
            $section,
            $external_section_relationship->core_relationship->id
        );

        // Create linked review element and attach to activity section.
        $element = element::create(
            $activity->get_context(),
            'linked_review',
            'title',
            '',
            json_encode([
                'content_type' => 'learning',
                'content_type_settings' => [],
                'selection_relationships' => [$subject_section_relationship->core_relationship_id],
            ])
        );
        $section_element = $perform_generator->create_section_element($section, $element);

        // Create learning items.
        $items = $this->create_learning_items([$subject_user1, $subject_user2, $subject_user3]);

        // Link learning items to activity.
        $linked_assignment1 = linked_review_content::create(
            $items['courses'][0]->id,
            $section_element->id,
            $subject_participant_section1->participant_instance_id,
            false,
            'course'
        );
        $linked_assignment2 = linked_review_content::create(
            $items['programs'][0]->id,
            $section_element->id,
            $subject_participant_section1->participant_instance_id,
            false,
            'program'
        );
        $linked_assignment3 = linked_review_content::create(
            $items['certifications'][0]->id,
            $section_element->id,
            $subject_participant_section2->participant_instance_id,
            false,
            'certification'
        );

        $data = new stdClass();
        $data->subject_user1 = $subject_user1;
        $data->subject_user2 = $subject_user2;
        $data->activity = $activity;
        $data->subject_instance1 = $subject_instance1;
        $data->subject_instance2 = $subject_instance2;
        $data->subject_participant_instance1 = $subject_participant_section1->participant_instance;
        $data->subject_participant_instance2 = $subject_participant_section2->participant_instance;
        $data->section_element = $section_element;
        $data->section = $section;
        $data->linked_assignment1 = $linked_assignment1;
        $data->linked_assignment2 = $linked_assignment2;
        $data->linked_assignment3 = $linked_assignment3;
        $data->learning_items = $items;

        return $data;
    }

}