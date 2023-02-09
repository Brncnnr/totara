<?php
/*
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package core_completion
*/

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');

use core\event\course_module_viewed;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

class webapi_resolver_mutation_activity_view_testcase extends testcase {

    private const MUTATION = 'core_completion_activity_view';

    use webapi_phpunit_helper;

    protected function setUp(): void {
        global $CFG;
        parent::setup();

        $CFG->enablecompletion = true;
    }

    private function create_completion_activity($activity): array {

        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1,
        );

        $course = self::getDataGenerator()->create_course($coursedefaults, array('createsections' => true));

        // Enrol user if present.
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);
        self::getDataGenerator()->enrol_user($user->id, $course->id);

        $module = self::getDataGenerator()->create_module(
            $activity,
            ['course' => $course->id],
            ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => COMPLETION_VIEW_REQUIRED]
        );
        return [$module, $course];
    }

    /**
     * Supported modules
     * Provider for test_resolve_activity_view
     * @return array
     */
    public function module_provider(): array {
        return [
            ['certificate'], ['chat'], ['choice'], ['data'], ['facetoface'], ['feedback'], ['folder'], ['forum'], ['imscp'],
            ['lesson'], ['lti'], ['page'], ['quiz'], ['resource'], ['scorm'], ['url'], ['wiki']
        ];
    }

    /**
     * @dataProvider module_provider
     * @throws coding_exception
     */
    public function test_resolve_activity_view($activity): void {
        [$module, $course] = $this->create_completion_activity($activity);
        $cm = get_coursemodule_from_instance($activity, $module->id);
        $completion = new completion_info($course);
        $completion_data = $completion->get_data($cm);
        self::assertEquals(0, $completion_data->viewed);

        $sink = $this->redirectEvents();

        $args = ['cmid' => $module->cmid, 'activity' => $activity];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        self::assertTrue($result);

        // This triggers several events. Make sure there is a course_module_viewed event among them.
        $viewed_events = array_filter($sink->get_events(), static function (core\event\base $event) {
            return $event instanceof course_module_viewed;
        });
        self::assertCount(1, $viewed_events);

        // Check completion status.
        $completion_data = $completion->get_data($cm);
        self::assertEquals(1, $completion_data->viewed);
    }

    public function test_resolve_activity_view_no_support(): void {
        $activity = 'book';
        [$module, ] = $this->create_completion_activity($activity);
        $args = ['cmid' => $module->cmid, 'activity' => $activity];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        self::assertFalse($result);
    }

    public function test_resolve_activity_view_with_mismatching_name_and_module(): void {
        $activity = 'book';
        [$module, ] = $this->create_completion_activity($activity);
        $args = ['cmid' => $module->cmid, 'activity' => 'lesson'];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Specified module could not be found.');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_resolve_activity_view_is_using_require_login_course_middleware() {
        $activity = 'lesson';
        [$module, $course] = $this->create_completion_activity($activity);
        $cm = get_coursemodule_from_instance($activity, $module->id);

        // Switch to a user that is not enrolled.
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);
        $args = ['cmid' => $module->cmid, 'activity' => $activity];

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }
}