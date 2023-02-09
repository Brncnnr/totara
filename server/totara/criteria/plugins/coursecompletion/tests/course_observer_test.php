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
 * @package criteria_coursecompletion
 */

use core\event\course_restored;
use core\entity\course as course_entity;
use core_phpunit\testcase;
use totara_completionimport\event\bulk_course_completionimport;
use criteria_coursecompletion\observer\course as course_observer;
use totara_core\event\course_completion_reset;
use totara_criteria\entity\criteria_item as item_entity;
use totara_criteria\entity\criteria_item_record as item_record_entity;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\entity\criterion_item;
use totara_criteria\hook\criteria_achievement_changed;
use totara_criteria\hook\criteria_validity_changed;

/**
 * @group totara_competency
 */
class criteria_coursecompletion_course_observer_testcase extends testcase {

    const NUM_USERS = 5;
    const NUM_COURSES = 5;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');
    }

    private function setup_data() {
        global $CFG;
        $data = new class() {
            public $courses =  [];
            public $users = [];
        };

        $CFG->enablecompletion = true;

        for ($user_idx = 1; $user_idx <= self::NUM_USERS; $user_idx++) {
            $data->users[$user_idx] = $this->getDataGenerator()->create_user();
        }

        for ($course_idx = 1; $course_idx <= self::NUM_COURSES; $course_idx++) {
            $data->courses[$course_idx] = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

            for ($user_idx = 1; $user_idx <= self::NUM_USERS; $user_idx++) {
                $this->getDataGenerator()->enrol_user($data->users[$user_idx]->id, $data->courses[$course_idx]->id);
            }
        }

        return $data;
    }

    public function test_course_completed_no_item() {
        $data = $this->setup_data();

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->assertEquals(0, $hook_sink->count());

        // We now generate a coursecompletion criterion but not for this course
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]);

        $hook_sink->clear();
        $completion = new completion_completion(['course' => $data->courses[2]->id, 'userid' => $data->users[2]->id]);
        $completion->mark_complete();

        $this->assertEquals(0, $hook_sink->count());
        $hook_sink->close();
    }

    public function test_course_completed_single_item() {
        $data = $this->setup_data();
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $criterion = $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]);

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->verify_achievement_changed_hook($hook_sink, [$data->users[1]->id => [$criterion->get_id()]]);
        $hook_sink->close();
    }

    public function test_course_completed_multiple_items() {
        $data = $this->setup_data();

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $criteria = [
            1 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]),
            2 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id, $data->courses[2]->id]]),
            3 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id, $data->courses[3]->id]])
        ];

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $data->courses[1]->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        $this->verify_achievement_changed_hook($hook_sink, [$data->users[1]->id => [$criteria[1]->get_id(), $criteria[2]->get_id()]]);
        $hook_sink->close();
    }

    public function test_bulk_course_completions_imported() {
        $data = $this->setup_data();

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $criteria = [
            1 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]),
            2 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id, $data->courses[2]->id]]),
            3 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[3]->id]]),
        ];

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

        course_observer::bulk_course_completions_imported($import_event);
        $this->verify_achievement_changed_hook($hook_sink, [
            $data->users[1]->id => [$criteria[1]->get_id(), $criteria[2]->get_id()],
            $data->users[2]->id => [$criteria[1]->get_id(), $criteria[2]->get_id()],
            $data->users[3]->id => [$criteria[3]->get_id()],
            $data->users[4]->id => [$criteria[3]->get_id()],
        ]);

        $hook_sink->close();
    }


    public function test_course_deleted_not_used() {
        $data = $this->setup_data();
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        $criteria = [
            1 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]),
            2 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id, $data->courses[2]->id]]),
            3 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id, $data->courses[3]->id]])
        ];
        $criteria_ids = [$criteria[1]->get_id(), $criteria[2]->get_id(), $criteria[3]->get_id()];

        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(3, $nvalid);

        delete_course($data->courses[4], false);

        $this->assertEmpty($hook_sink->get_hooks());
        $hook_sink->close();

        // Verify that the status didn't changed on disk
        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(3, $nvalid);
    }

    public function test_course_deleted() {
        $data = $this->setup_data();
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        $criteria = [
            1 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]),
            2 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id, $data->courses[2]->id]]),
            3 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id, $data->courses[3]->id]])
        ];
        $criteria_ids = [$criteria[1]->get_id(), $criteria[2]->get_id(), $criteria[3]->get_id()];

        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(3, $nvalid);

        delete_course($data->courses[1], false);

        $this->verify_validity_changed_hook($hook_sink, [$criteria[1]->get_id(), $criteria[2]->get_id()]);
        $hook_sink->close();

        // Verify that the status changed on disk
        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(1, $nvalid);

        $ninvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 0)
            ->count();
        $this->assertEquals(2, $ninvalid);
    }

    public function test_course_restored() {
        global $CFG;

        require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
        require_once($CFG->dirroot . '/backup/backup.class.php');

        $data = $this->setup_data();
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        // Delete the course first to ensure the criteria's valid attribute is set correctly
        $deleted_course_id = $data->courses[3]->id;
        $deleted_course = new course_entity($deleted_course_id);
        $deleted_course->delete();

        $criteria = [
            1 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]),
            2 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id, $data->courses[2]->id]]),
            3 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id, $data->courses[3]->id]])
        ];
        $criteria_ids = [$criteria[1]->get_id(), $criteria[2]->get_id(), $criteria[3]->get_id()];

        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(2, $nvalid);

        $ninvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 0)
            ->count();
        $this->assertEquals(1, $ninvalid);

        $restored_course = $this->getDataGenerator()->create_course(['enablecompletion' => true]);

        $cc_event = course_restored::create([
            'objectid' => $restored_course->id,
            'context' => context_course::instance($restored_course->id),
            'other' => [
                'type' => backup::TYPE_1COURSE,
                'target' => backup::TARGET_NEW_COURSE,
                'mode' => backup::MODE_GENERAL,
                'operation' => backup::OPERATION_RESTORE,
                'samesite' => true,
                'originalcourseid' => $deleted_course_id,
            ]
        ]);

        $cc_event->trigger();
        $this->verify_validity_changed_hook($hook_sink, [$criteria[3]->get_id()]);
        $hook_sink->close();

        // Verify that the status changed on disk
        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(3, $nvalid);

        $ninvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 0)
            ->count();
        $this->assertEquals(0, $ninvalid);
    }

    public function test_course_settings_changed() {
        $data = $this->setup_data();
        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        $criteria = [
            1 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]),
            2 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id, $data->courses[2]->id]]),
            3 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id, $data->courses[3]->id]])
        ];
        $criteria_ids = [$criteria[1]->get_id(), $criteria[2]->get_id(), $criteria[3]->get_id()];

        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(3, $nvalid);

        $course1 = new course_entity($data->courses[1]->id);
        $course1->enablecompletion = 0;
        $course1->save();

        $hook_sink->clear();
        $event = \core\event\course_updated::create([
            'objectid' => $course1->id,
            'context' => context_course::instance($course1->id),
            'other'    => ['fulname' => $course1->fullname],
        ]);
        $event->trigger();
        $this->verify_validity_changed_hook($hook_sink, [$criteria[1]->get_id(), $criteria[2]->get_id()]);
        $hook_sink->clear();

        // Verify that the status changed on disk
        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(1, $nvalid);

        $ninvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 0)
            ->count();
        $this->assertEquals(2, $ninvalid);
    }

    public function test_course_completion_reset() {
        $data = $this->setup_data();

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $criteria = [
            1 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]),
            2 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id, $data->courses[3]->id]]),
            3 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[3]->id, $data->courses[4]->id]])
        ];

        // Create item_records
        $this->create_item_records($data->courses[1]->id, $data->users[1]->id, 1);
        $this->create_item_records($data->courses[3]->id, $data->users[3]->id, 1);
        $this->create_item_records($data->courses[4]->id, $data->users[3]->id, 0);
        $this->create_item_records($data->courses[4]->id, $data->users[4]->id, 1);

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        course_completion_reset::create_from_course($data->courses[1])->trigger();
        $this->verify_achievement_changed_hook($hook_sink, [
            $data->users[1]->id => [$criteria[1]->get_id()]
        ]);
        $hook_sink->clear();

        course_completion_reset::create_from_course($data->courses[2])->trigger();
        $this->assertSame(0, $hook_sink->count());

        course_completion_reset::create_from_course($data->courses[3])->trigger();
        $this->verify_achievement_changed_hook($hook_sink, [
            $data->users[3]->id => [$criteria[2]->get_id(), $criteria[3]->get_id()]
        ]);
        $hook_sink->clear();

        course_completion_reset::create_from_course($data->courses[4])->trigger();
        $this->verify_achievement_changed_hook($hook_sink, [
            $data->users[3]->id => [$criteria[3]->get_id()],
            $data->users[4]->id => [$criteria[3]->get_id()],
        ]);

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
     * @param \core_phpunit\hook_sink $sink
     * @param array $expected_criteria_ids
     */
    private function verify_validity_changed_hook(\core_phpunit\hook_sink $sink, array $expected_criteria_ids) {
        $hooks = $sink->get_hooks();
        $this->assertEquals(1, count($hooks));
        /** @var criteria_validity_changed $hook */
        $hook = reset($hooks);
        $this->assertEquals(criteria_validity_changed::class, get_class($hook));

        $this->assertEqualsCanonicalizing($expected_criteria_ids, $hook->get_criteria_ids());
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
            ->where('criterion.plugin_type', 'coursecompletion')
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
