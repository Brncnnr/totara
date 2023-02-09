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

use criteria_coursecompletion\coursecompletion;
use totara_competency\aggregation_users_table;
use totara_criteria\evaluators\course_item_evaluator;
use totara_criteria\evaluators\item_evaluator_user_source;

/**
 * @group totara_competency
 */
class totara_criteria_course_item_evaluator_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');
    }

    private function setup_data() {
        $data = new class() {
            /** @var array $users */
            public $users;
            /** @var stdClass $course */
            public $course;
            /** @var coursecompletion $coursecompletion */
            public $coursecompletion;
            /** @var aggregation_users_table $source_table */
            public $source_table;
        };

        $this->setAdminUser();
        $GLOBALS['USER']->ignoresesskey = true;

        $data->users = [];
        for ($i = 1; $i <= 2; $i++) {
            $data->users[$i] = $this->getDataGenerator()->create_user();
        }

        $record = [
            'shortname' => "course1",
            'fullname' => "Course 1",
            'enablecompletion' => true,
        ];
        $data->course = $this->getDataGenerator()->create_course($record);

        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = ['courseids' => [$data->course->id]];
        $data->coursecompletion = $generator->create_coursecompletion($record);

        $data->source_table = new aggregation_users_table();

        return $data;
    }

    private function insert_temp_users(array $user_ids, int $has_changed = 0) {
        global $DB;

        $temp_records = [];
        foreach ($user_ids as $id) {
            $temp_records[] = ['competency_id' => 1, 'user_id' => $id, 'has_changed' => $has_changed];
        }

        $DB->insert_records('totara_competency_aggregation_queue', $temp_records);
    }

    public function test_update_completion_no_users() {
        global $DB;

        $data = $this->setup_data();
        $this->assertSame(0, $DB->count_records('totara_criteria_item_record'));

        $user_source = new item_evaluator_user_source($data->source_table);
        $item_evaluator = new course_item_evaluator($user_source);
        $item_evaluator->update_completion($data->coursecompletion);
        $this->assertSame(0, $DB->count_records('totara_criteria_item_record'));
    }

    public function test_update_completion_new_course_completion() {
        global $DB;

        // Redirecting events to prevent observers from interfering with tests
        $sink = $this->redirectEvents();

        $data = $this->setup_data();
        $test_users = [$data->users[1]->id, $data->users[2]->id];

        // Setting up ...
        // 2 users assigned
        $this->insert_temp_users($test_users);

        // Create the initial item_records and wait for a second to ensure we have unique timestamps
        $user_source = new item_evaluator_user_source($data->source_table);
        $item_evaluator = new course_item_evaluator($user_source);
        $item_evaluator->update_completion($data->coursecompletion);
        $this->waitForSecond();

        // Reset the has_changed flag for all assignments which was set when the item_records were created
        $DB->execute('UPDATE {totara_competency_aggregation_queue} SET has_changed = 0');

        // Mark user1 to have completed the course
        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $data->course->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        // Now for the real test ...
        // Testing that we mark item_records updated since the last time we performed the completion evaluation
        $this->waitForSecond();
        $item_evaluator->update_completion($data->coursecompletion);

        // The criterion_met should have been updated for user1, but not user2.
        $item_records = $DB->get_records('totara_criteria_item_record');
        $this->assertSame(count($test_users), count($item_records));

        foreach ($item_records as $record) {
            $this->assertTrue(in_array($record->user_id, $test_users));
            if ($record->user_id == $data->users[1]->id) {
                $this->assertEquals(1, $record->criterion_met);
                $this->assertEquals($completion->timecompleted, $record->timeachieved);
            } else {
                $this->assertEquals(0, $record->criterion_met);
                $this->assertNull($record->timeachieved);
            }
        }

        // Similarly - user1's has_changes in the temp table should be set, but not user2's
        $temp_records = $DB->get_records('totara_competency_aggregation_queue');
        $this->assertSame(count($test_users), count($temp_records));
        foreach ($temp_records as $record) {
            if ($record->user_id == $data->users[1]->id) {
                $this->assertEquals(1, $record->has_changed);
            } else {
                $this->assertEquals(0, $record->has_changed);
            }
        }

        $sink->close();
    }

    public function test_update_completion_course_completion_no_longer_exist() {
        global $DB;

        $data = $this->setup_data();

        $test_users = [$data->users[1]->id, $data->users[2]->id];

        // Setting up ...
        // 2 users assigned
        $this->insert_temp_users($test_users);

        // Create the initial item_records - tested in another function
        $user_source = new item_evaluator_user_source($data->source_table);
        $item_evaluator = new course_item_evaluator($user_source);
        $item_evaluator->update_completion($data->coursecompletion);
        // Reset the has_changed flag
        $DB->execute('UPDATE {totara_competency_aggregation_queue} SET has_changed = 0');

        // Now for the real test ...
        // Testing that the sql statements to catch item_records with invalid criterion_met values are updated correctly

        $DB->execute('UPDATE {totara_criteria_item_record} SET criterion_met = 1 WHERE user_id = :user2',
            ['user2' => $data->users[2]->id]
        );

        $this->waitForSecond();
        $item_evaluator->update_completion($data->coursecompletion);

        // The criterion_met should have been updated for user1, but not user2.
        $item_records = $DB->get_records('totara_criteria_item_record');
        $this->assertSame(count($test_users), count($item_records));

        foreach ($item_records as $record) {
            $this->assertTrue(in_array($record->user_id, $test_users));
            $this->assertEquals(0, $record->criterion_met);
            $this->assertNull($record->timeachieved);
        }

        // Similarly - user1's has_changes in the temp table should be set, but not user2's
        $temp_records = $DB->get_records('totara_competency_aggregation_queue');
        $this->assertSame(count($test_users), count($temp_records));
        foreach ($temp_records as $record) {
            if ($record->user_id == $data->users[1]->id) {
                $this->assertEquals(0, $record->has_changed);
            } else {
                $this->assertEquals(1, $record->has_changed);
            }
        }
    }

    public function test_update_completion_course_completion_missed_by_observer() {
        global $DB;

        // Redirecting events to prevent observers from interfering with tests
        $sink = $this->redirectEvents();

        $data = $this->setup_data();

        $test_users = [$data->users[1]->id, $data->users[2]->id];

        // Setting up ...
        // 2 users assigned
        $this->insert_temp_users($test_users);

        // Create the initial item_records and wait for a second to ensure we have unique timestamps
        $user_source = new item_evaluator_user_source($data->source_table);
        $item_evaluator = new course_item_evaluator($user_source);
        $item_evaluator->update_completion($data->coursecompletion);
        $this->waitForSecond();

        // Reset the has_changed flag for all assignments which was set when the item_records were created
        $DB->execute('UPDATE {totara_competency_aggregation_queue} SET has_changed = 0');

        // Mark user1 to have completed the course
        /** @var completion_completion $completion */
        $completion = new completion_completion(['course' => $data->course->id, 'userid' => $data->users[1]->id]);
        $completion->mark_complete();

        // For testing purposes only - resetting the item_record.criterion_met flag that is set in the
        // event observer when the course was marked as completed by user1
        // This is still a valid test as it handles the case where the event observer failed to set the criterion_met flag
        // for some or other reason.
        $DB->execute('UPDATE {totara_criteria_item_record} SET criterion_met = 0');

        // Now for the real test ...
        // Testing that the sql statements to catch item_records with invalid criterion_met values are updated correctly
        $this->waitForSecond();
        $item_evaluator->update_completion($data->coursecompletion);

        // The criterion_met should have been updated for user1, but not user2.
        $item_records = $DB->get_records('totara_criteria_item_record');
        $this->assertSame(count($test_users), count($item_records));

        foreach ($item_records as $record) {
            $this->assertTrue(in_array($record->user_id, $test_users));
            if ($record->user_id == $data->users[1]->id) {
                $this->assertEquals(1, $record->criterion_met);
                $this->assertEquals($completion->timecompleted, $record->timeachieved);
            } else {
                $this->assertEquals(0, $record->criterion_met);
                $this->assertNull($record->timeachieved);
            }
        }

        // Similarly - user1's has_changes in the temp table should be set, but not user2's
        $temp_records = $DB->get_records('totara_competency_aggregation_queue');
        $this->assertSame(count($test_users), count($temp_records));
        foreach ($temp_records as $record) {
            if ($record->user_id == $data->users[1]->id) {
                $this->assertEquals(1, $record->has_changed);
            } else {
                $this->assertEquals(0, $record->has_changed);
            }
        }

        $sink->close();
    }

}
