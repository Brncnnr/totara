<?php
/*
 * This file is part of Totara Learning
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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package core_completion
 */

defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;

global $CFG;
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

class completion_completion_criteria_activity_testcase extends testcase {

    /** @var  \core\testing\generator $data_generator */
    protected $generator;

    /** @var \core_completion\testing\generator $completion_generator */
    protected $completion_generator;

    protected function setUp(): void {
        parent::setup();

        $this->generator = self::getDataGenerator();
        $this->completion_generator = self::getDataGenerator()->get_plugin_generator('core_completion');
    }

    protected function tearDown(): void {
        $this->generator = null;
        $this->completion_generator = null;

        parent::tearDown();
    }

    public function test_completion_criteria_activity_get_progress(): void {
        global $DB;

        // Create a course and a couple users.
        $user1 = $this->generator->create_user();
        $user2 = $this->generator->create_user();

        $course = $this->generator->create_course();
        $this->completion_generator->enable_completion_tracking($course);

        // Enrol users to the course.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->generator->enrol_user($user1->id, $course->id, $studentrole->id);
        $this->generator->enrol_user($user2->id, $course->id, $studentrole->id);

        $assign = $this->generator->create_module('assign', ['course' => $course->id], ['completion' => 1]);
        $forum = $this->generator->create_module('forum', ['course' => $course->id], ['completion' => 1]);

        $this->completion_generator->set_activity_completion($course->id, [$assign, $forum]);

        $c = new completion_info($course);

        $cmf = get_coursemodule_from_instance('forum', $forum->id);
        $cma = get_coursemodule_from_instance('assign', $assign->id);

        $user1_progress = [$cmf->id => 55, $cma->id => 30];
        $user2_progress = [$cmf->id => 10, $cma->id => 100];

        $c->update_progress($cmf, $user1_progress[$cmf->id], $user1->id);
        $c->update_progress($cma, $user1_progress[$cma->id], $user1->id);

        $c->update_progress($cmf, $user2_progress[$cmf->id], $user2->id);
        $c->update_progress($cma, $user2_progress[$cma->id], $user2->id);

        self::setAdminUser();

        $completion_criteria_activity = new completion_criteria_activity(
            [
                'course'         => $course->id,
                'criteriatype'   => COMPLETION_CRITERIA_TYPE_ACTIVITY,
                'courseinstance' => $course->id,
            ], true
        );

        // Check user1 progress.
        $completion_criteria_completion1 = new completion_criteria_completion(
            [
                'course'     => $course->id,
                'userid'     => $user1->id,
                'criteriaid' => $completion_criteria_activity->id,
            ]
        );

        foreach ($c->get_criteria() as $criterion) {
            $progress = $criterion->get_progress($completion_criteria_completion1);
            self::assertEquals(round($user1_progress[$criterion->moduleinstance] / 100, 3), $progress);
        }

        // Check user2 progress.
        $completion_criteria_completion2 = new completion_criteria_completion(
            [
                'course'     => $course->id,
                'userid'     => $user2->id,
                'criteriaid' => $completion_criteria_activity->id,
            ]
        );

        foreach ($c->get_criteria() as $criterion) {
            $progress = $criterion->get_progress($completion_criteria_completion2);
            self::assertEquals(round($user2_progress[$criterion->moduleinstance] / 100, 3), $progress);
        }
    }
}
