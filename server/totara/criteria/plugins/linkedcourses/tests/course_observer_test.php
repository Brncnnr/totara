<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package criteria_linkedcourses
 */

use core_phpunit\testcase;
use totara_competency\linked_courses;
use totara_completionimport\event\bulk_course_completionimport;
use totara_core\event\course_completion_reset;
use totara_criteria\entity\criteria_item as item_entity;
use totara_criteria\entity\criteria_item_record as item_record_entity;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\entity\criterion_item;
use totara_criteria\hook\criteria_achievement_changed;

/**
 * @group totara_competency
 */
class criteria_linkedcourses_course_observer_testcase extends testcase {

    const NUM_USERS = 5;
    const NUM_COURSES = 5;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');
    }

    private function setup_data(int $num_criteria = 0) {
        global $CFG;
        $data = new class() {
            /** @var \totara_hierarchy\entity\competency $competency */
            public $competency;
            /** @var criterion_entity[] $criterion */
            public $criteria = [];
            /** @var array int[] $criteria_ids */
            public $criteria_ids = [];
            public $courses =  [];
            public $users = [];
        };

        $CFG->enablecompletion = true;

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $data->competency = $competency_generator->create_competency('Comp A');

        for ($user_idx = 1; $user_idx <= self::NUM_USERS; $user_idx++) {
            $data->users[$user_idx] = $this->getDataGenerator()->create_user();
        }

        for ($course_idx = 1; $course_idx <= self::NUM_COURSES; $course_idx++) {
            $data->courses[$course_idx] = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

            for ($user_idx = 1; $user_idx <= self::NUM_USERS; $user_idx++) {
                $this->getDataGenerator()->enrol_user($data->users[$user_idx]->id, $data->courses[$course_idx]->id);
            }
        }

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        for ($i = 1; $i <= $num_criteria; $i++) {
            $data->criteria[$i] = $criteria_generator->create_linkedcourses(['competency' => $data->competency->id]);
            $data->criteria_ids[$i] = $data->criteria[$i]->get_id();
        }

        return $data;
    }

    public function test_course_completed_no_criterion() {
        $data = $this->setup_data(0);

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->assertEquals(0, $hook_sink->count());
        $hook_sink->close();
    }

    public function test_course_completed_single_item() {
        $data = $this->setup_data(1);

        linked_courses::set_linked_courses($data->competency->id, [
            ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->verify_achievement_changed_hook($hook_sink, [$data->users[1]->id => [$data->criteria[1]->get_id()]]);

        $hook_sink->close();
    }

    public function test_course_completed_multiple_items() {
        $data = $this->setup_data(2);

        linked_courses::set_linked_courses($data->competency->id, [
            ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => $data->courses[2]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->verify_achievement_changed_hook($hook_sink,
            [$data->users[1]->id => [$data->criteria[1]->get_id(), $data->criteria[2]->get_id()]]
        );

        $hook_sink->close();
    }

    public function test_bulk_course_completions_imported() {
        $data = $this->setup_data(1);

        linked_courses::set_linked_courses($data->competency->id, [
            ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => $data->courses[2]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        /**
         * Create event
         * user1 - completes course1
         * user2 - completes courses 1 and 2
         * user3 - completes course 3
         * user4 - completes courses 3 and 4
         * user5 - completes course 4
         */
        $course_completions = [
            ['userid' => $data->users[1]->id, 'courseid' => $data->courses[1]->id],
            ['userid' => $data->users[2]->id, 'courseid' => $data->courses[1]->id],
            ['userid' => $data->users[2]->id, 'courseid' => $data->courses[2]->id],
            ['userid' => $data->users[3]->id, 'courseid' => $data->courses[3]->id],
            ['userid' => $data->users[4]->id, 'courseid' => $data->courses[3]->id],
            ['userid' => $data->users[4]->id, 'courseid' => $data->courses[4]->id],
            ['userid' => $data->users[5]->id, 'courseid' => $data->courses[4]->id],
        ];

        $import_event = bulk_course_completionimport::create_from_list($course_completions);
        $import_event->trigger();

        $this->verify_achievement_changed_hook($hook_sink, [
            $data->users[1]->id => [$data->criteria[1]->get_id()],
            $data->users[2]->id => [$data->criteria[1]->get_id()],
        ]);

        $hook_sink->close();
    }

    public function test_course_completion_reset() {
        $data = $this->setup_data(2);

        linked_courses::set_linked_courses($data->competency->id, [
            ['id' => $data->courses[1]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => $data->courses[2]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ['id' => $data->courses[3]->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
        ]);

        // Create item_records
        $this->create_item_records($data->courses[1]->id, $data->users[1]->id, 1);
        $this->create_item_records($data->courses[2]->id, $data->users[1]->id, 0);
        $this->create_item_records($data->courses[2]->id, $data->users[2]->id, 1);


        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        course_completion_reset::create_from_course($data->courses[1])->trigger();
        $this->verify_achievement_changed_hook($hook_sink, [
            $data->users[1]->id => [$data->criteria[1]->get_id(), $data->criteria[2]->get_id()],
        ]);
        $hook_sink->clear();

        course_completion_reset::create_from_course($data->courses[2])->trigger();
        $this->verify_achievement_changed_hook($hook_sink, [
            $data->users[1]->id => [$data->criteria[1]->get_id(), $data->criteria[2]->get_id()],
            $data->users[2]->id => [$data->criteria[1]->get_id(), $data->criteria[2]->get_id()],
        ]);
        $hook_sink->clear();

        course_completion_reset::create_from_course($data->courses[3])->trigger();
        $this->assertSame(0, $hook_sink->count());

        $hook_sink->close();
    }


    /**
     * @param \core_phpunit\hook_sink $sink
     * @param array $expected_user_criteria_ids
     */
    private function verify_achievement_changed_hook(\core_phpunit\hook_sink $sink, array $expected_user_criteria_ids) {
        $hooks = $sink->get_hooks();
        $this->assertEquals(1, count($hooks));
        /** @var criteria_achievement_changed $hook */
        $hook = reset($hooks);
        $this->assertEquals(criteria_achievement_changed::class, get_class($hook));

        $this->assertEqualsCanonicalizing($expected_user_criteria_ids, $hook->get_user_criteria_ids());
        $sink->clear();
    }

    /**
     * @param int $course_id
     * @param int $user_id
     * @param int $criterion_met
     */
    private function create_item_records(int $course_id, int $user_id, int $criterion_met = 0) {
        /** @var criterion_item $items */
        $items = item_entity::repository()
            ->as('item')
            ->join([criterion_entity::TABLE, 'criterion'], 'item.criterion_id', 'criterion.id')
            ->where('criterion.plugin_type', 'linkedcourses')
            ->where('item.item_type', 'course')
            ->where('item.item_id', $course_id)
            ->get();

        foreach ($items as $item) {
            $record = new item_record_entity();
            $record->user_id = $user_id;
            $record->criterion_item_id = $item->id;
            $record->criterion_met = $criterion_met;
            $record->timeevaluated = time();
            $record->save();
        }
    }

}
