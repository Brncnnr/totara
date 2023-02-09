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
 * @package totara_criteria
 */

use core\entity\course as course_entity;
use core_phpunit\testcase;
use totara_criteria\course_item_helper;
use totara_criteria\entity\criteria_item;
use totara_criteria\entity\criteria_item_record;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\hook\criteria_achievement_changed;
use totara_criteria\hook\criteria_validity_changed;

/**
 * @group totara_competency
 */
class course_item_helper_testcase extends testcase {

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

    public function test_course_completions_updated_no_item() {
        $data = $this->setup_data();

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        course_item_helper::course_completions_updated([$data->users[1]->id => [$data->courses[1]->id]]);
        $this->assertSame(0, $hook_sink->count());

        $hook_sink->close();
    }

    public function test_course_completions_updated_single_item() {
        $data = $this->setup_data();
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $criterion = $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]);

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        course_item_helper::course_completions_updated([$data->users[1]->id => [$data->courses[1]->id]]);
        $this->verify_achievement_changed_hook($hook_sink, [$data->users[1]->id => [$criterion->get_id()]]);
        $hook_sink->close();
    }

    public function test_course_completions_updated_multiple_items() {
        $data = $this->setup_data();
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $criteria = [
            1 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]),
            2 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id, $data->courses[2]->id]]),
            3 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id, $data->courses[3]->id]])
        ];

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        course_item_helper::course_completions_updated([$data->users[1]->id => [$data->courses[1]->id]]);
        $this->verify_achievement_changed_hook($hook_sink, [$data->users[1]->id => [$criteria[1]->get_id(), $criteria[2]->get_id()]]);

        $hook_sink->clear();
        /**
         * user1 - completes course1
         * user2 - completes courses 1 and 2
         * user3 - completes course 3
         * user4 - completes courses 3 and 4
         * user5 - completes course 4
         */
        course_item_helper::course_completions_updated([
            $data->users[1]->id => [$data->courses[1]->id],
            $data->users[2]->id => [$data->courses[1]->id, $data->courses[2]->id],
            $data->users[3]->id => [$data->courses[3]->id],
            $data->users[4]->id => [$data->courses[3]->id, $data->courses[4]->id],
            $data->users[5]->id => [$data->courses[4]->id],
        ]);
        $this->verify_achievement_changed_hook($hook_sink, [
            $data->users[1]->id => [$criteria[1]->get_id(), $criteria[2]->get_id()],
            $data->users[2]->id => [$criteria[1]->get_id(), $criteria[2]->get_id(), $criteria[3]->get_id()],
            $data->users[3]->id => [$criteria[3]->get_id()],
            $data->users[4]->id => [$criteria[3]->get_id()],
        ]);


        $hook_sink->close();
    }

    public function test_course_deleted_not_used() {
        /** @var \core_phpunit\event_sink $hook_sink */
        $event_sink = $this->redirectEvents();
        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        $data = $this->setup_data();
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

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

        course_item_helper::course_deleted($data->courses[4]->id);
        $this->assertEmpty($hook_sink->get_hooks());

        // Verify that the status didn't changed on disk
        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(3, $nvalid);

        $event_sink->close();
        $hook_sink->close();
    }

    public function test_course_deleted_with_items() {
        /** @var \core_phpunit\event_sink $hook_sink */
        $event_sink = $this->redirectEvents();
        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        $data = $this->setup_data();
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

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

        course_item_helper::course_deleted($data->courses[1]->id);
        $this->verify_validity_changed_hook($hook_sink, [$criteria[1]->get_id(), $criteria[2]->get_id()]);

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

        $event_sink->close();
        $hook_sink->close();
    }

    public function test_course_restored() {
        global $CFG;

        require_once($CFG->dirroot . '/backup/util/interfaces/checksumable.class.php');
        require_once($CFG->dirroot . '/backup/backup.class.php');

        /** @var \core_phpunit\event_sink $hook_sink */
        $event_sink = $this->redirectEvents();
        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        $data = $this->setup_data();
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

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

        course_item_helper::course_restored($deleted_course_id, $restored_course->id);
        $this->verify_validity_changed_hook($hook_sink, [$criteria[3]->get_id()]);

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

        $event_sink->close();
        $hook_sink->close();
    }

    public function test_settings_changed() {
        /** @var \core_phpunit\event_sink $hook_sink */
        $event_sink = $this->redirectEvents();
        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        $data = $this->setup_data();
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

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

        // Test with no changes to completion tracking
        course_item_helper::course_settings_changed($data->courses[1]->id);
        $this->assertSame(0, $hook_sink->count());

        // Verify that no statuses were changed on disk
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

        // Now update the course's enablecompletion
        $course1 = new course_entity($data->courses[1]->id);
        $course1->enablecompletion = 0;
        $course1->update();

        course_item_helper::course_settings_changed($data->courses[1]->id);
        $this->verify_validity_changed_hook($hook_sink, [$criteria[1]->get_id(), $criteria[2]->get_id()]);

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

        $event_sink->close();
        $hook_sink->close();
    }

    public function test_global_setting_changed() {
        /** @var \core_phpunit\event_sink $hook_sink */
        $event_sink = $this->redirectEvents();
        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        set_config('enablecompletion', 1);
        $data = $this->setup_data();

        // Disable coursecompletion for courses 3 and 4
        $course3 = new course_entity($data->courses[3]->id);
        $course3->enablecompletion = 0;
        $course3->update();

        $course4 = new course_entity($data->courses[4]->id);
        $course4->enablecompletion = 0;
        $course4->update();

        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $criteria = [
            1 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]),
            2 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id, $data->courses[2]->id]]),
            3 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id, $data->courses[3]->id]]),
            4 => $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[4]->id]]),
        ];
        $criteria_ids = [$criteria[1]->get_id(), $criteria[2]->get_id(), $criteria[3]->get_id(), $criteria[4]->get_id()];

        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(2, $nvalid);

        $ninvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 0)
            ->count();
        $this->assertEquals(2, $ninvalid);

        $hook_sink->clear();

        // Test with no change
        course_item_helper::global_setting_changed();
        $this->assertSame(0, $hook_sink->count());

        // Verify that no statuses were changed on disk
        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(2, $nvalid);

        $ninvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 0)
            ->count();
        $this->assertEquals(2, $ninvalid);

        // Now disable completion
        set_config('enablecompletion', 0);
        course_item_helper::global_setting_changed();

        $hooks = $hook_sink->get_hooks();
        $this->assertSame(1, count($hooks));
        /** @var criteria_validity_changed $hook */
        $hook = reset($hooks);

        $this->assertTrue($hook instanceof criteria_validity_changed);
        $this->assertEqualsCanonicalizing([$criteria[1]->get_id(), $criteria[2]->get_id()], $hook->get_criteria_ids());

        // Verify that the status changed on disk
        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(0, $nvalid);

        $ninvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 0)
            ->count();
        $this->assertEquals(4, $ninvalid);
        $hook_sink->clear();

        // Enable completion again
        set_config('enablecompletion', 1);
        course_item_helper::global_setting_changed();

        $hooks = $hook_sink->get_hooks();
        $this->assertSame(1, count($hooks));
        /** @var criteria_validity_changed $hook */
        $hook = reset($hooks);

        $this->assertTrue($hook instanceof criteria_validity_changed);
        $this->assertEqualsCanonicalizing([$criteria[1]->get_id(), $criteria[2]->get_id()], $hook->get_criteria_ids());

        // Verify that the status changed on disk
        $nvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 1)
            ->count();
        $this->assertEquals(2, $nvalid);

        $ninvalid = criterion_entity::repository()
            ->where('id', $criteria_ids)
            ->where('valid', 0)
            ->count();
        $this->assertEquals(2, $ninvalid);

        $event_sink->close();
        $hook_sink->close();
    }

    public function test_course_completions_reset() {
        $data = $this->setup_data();
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $criterion1 = $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]);
        $criterion2 = $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id]]);

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        // No records
        course_item_helper::course_completions_reset($data->courses[1]->id);
        $this->assertSame(0, $hook_sink->count());

        // Records for another course
        // User1 completed course2
        $criterion2_itemid = criteria_item::repository()
            ->select('id')
            ->where('criterion_id', $criterion2->get_id())
            ->one();

        $this->create_item_record($criterion2_itemid->id, $data->users[1]->id, 1);

        course_item_helper::course_completions_reset($data->courses[1]->id);
        $this->assertSame(0, $hook_sink->count());

        // With one record
        course_item_helper::course_completions_reset($data->courses[2]->id);
        $this->verify_achievement_changed_hook($hook_sink, [$data->users[1]->id => [$criterion2->get_id()]]);
        $hook_sink->close();
    }

    public function test_course_completions_reset_multiple() {
        $data = $this->setup_data();
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $criterion1 = $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[1]->id]]);
        $criterion2 = $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[2]->id]]);
        $criterion3 = $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[3]->id]]);
        $criterion34 = $criteria_generator->create_coursecompletion(['courseids' => [$data->courses[3]->id, $data->courses[4]->id]]);

        // User1 completed course1 and course2
        // User2 completed course3
        // User3 completes course4
        // User4 completes course3 and course4
        $criterion_items = criteria_item::repository()
            ->get();
        foreach ($criterion_items as $item) {
            if ($item->item_id == $data->courses[1]->id || $item->item_id == $data->courses[2]->id) {
                $this->create_item_record($item->id, $data->users[1]->id, 1);
            }

            if ($item->item_id == $data->courses[3]->id) {
                $this->create_item_record($item->id, $data->users[2]->id, 1);
                $this->create_item_record($item->id, $data->users[4]->id, 1);
            }

            if ($item->item_id == $data->courses[4]->id) {
                $this->create_item_record($item->id, $data->users[3]->id, 1);
                $this->create_item_record($item->id, $data->users[4]->id, 1);
            }
        }

        /** @var \core_phpunit\hook_sink $hook_sink */
        $hook_sink = $this->redirectHooks();

        course_item_helper::course_completions_reset($data->courses[2]->id);
        $this->verify_achievement_changed_hook($hook_sink,
            [
                $data->users[1]->id => [$criterion2->get_id()],
            ]
        );
        $hook_sink->clear();

        course_item_helper::course_completions_reset($data->courses[3]->id);
        $this->verify_achievement_changed_hook($hook_sink,
            [
                $data->users[2]->id => [$criterion3->get_id(), $criterion34->get_id()],
                $data->users[4]->id => [$criterion3->get_id(), $criterion34->get_id()],
            ]
        );
        $hook_sink->clear();

        course_item_helper::course_completions_reset($data->courses[4]->id);
        $this->verify_achievement_changed_hook($hook_sink,
            [
                $data->users[3]->id => [$criterion34->get_id()],
                $data->users[4]->id => [$criterion34->get_id()],
            ]
        );

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
     * @param int $item_id
     * @param int $user_id
     * @param int $criterion_met
     */
    private function create_item_record(int $item_id, int $user_id, int $criterion_met = 0) {
        $record = new criteria_item_record();
        $record->criterion_item_id = $item_id;
        $record->user_id = $user_id;
        $record->criterion_met = $criterion_met;
        $record->timeevaluated = time();
        $record->save();
    }
}
