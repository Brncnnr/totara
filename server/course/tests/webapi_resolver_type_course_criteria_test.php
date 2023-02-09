<?php
/*
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

use \totara_core\webapi\resolver\type;
use core\format;

/**
 * Tests the totara core course criteria type resolver.
 */
class totara_core_webapi_resolver_type_course_criteria_testcase extends advanced_testcase {
    private $context;

    protected function tearDown(): void {
        $this->context = null;
    }

    private function resolve($field, $item, array $args = []) {
        $excontext = $this->get_execution_context();
        $excontext->set_relevant_context($this->context);

        return \core\webapi\resolver\type\course_criteria::resolve(
            $field,
            $item,
            $args,
            $excontext
        );
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    /**
     * Create some courses and assign some users for testing.
     * @return []
     */
    private function create_dataset(array $users = []) {
        $users = [];
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        $courses = [];
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c1', 'fullname' => 'course1', 'summary' => 'first course']);
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c2', 'fullname' => 'course2', 'summary' => 'second course']);

        // Set-up a default context for the resolver.
        $this->context = \context_course::instance($courses[0]->id);

        $completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $completion_generator->enable_completion_tracking($courses[0]);
        $completion_generator->enable_completion_tracking($courses[1]);

        // Criteria
        $completioncriteria = [];

        $enddate = strtotime("+1 week");
        $completioncriteria[COMPLETION_CRITERIA_TYPE_DATE] = $enddate;
        $completioncriteria[COMPLETION_CRITERIA_TYPE_DURATION] = 2 * 86400;
        $completioncriteria[COMPLETION_CRITERIA_TYPE_GRADE] = 75.0;
        $completion_generator->set_completion_criteria($courses[0], $completioncriteria);

        $completion_generator->set_course_criteria_course_completion($courses[1], array($courses[0]->id), COMPLETION_AGGREGATION_ALL);

        $this->getDataGenerator()->enrol_user($users[0]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[1]->id, 'student', 'manual');

        return [$users, $courses];
    }

    /**
     * Mimic the code used in the course type to fetch all the criteria for a given course.
     *
     * @param object $course
     * @return array
     */
    private function fetch_course_criteria($course) {
        global $DB, $USER;

        $this->context = \context_course::instance($course->id);

        // Organise activity completions according to the course display order.
        // Obtain the display order of activity modules.
        $sections = $DB->get_records('course_sections', array('course' => $course->id), 'section ASC', 'id, sequence');
        $moduleorder = array();
        foreach ($sections as $section) {
            if (!empty($section->sequence)) {
                $moduleorder = array_merge(array_values($moduleorder), array_values(explode(',', $section->sequence)));
            }
        }

        $info = new \completion_info($course);
        $completions = $info->get_completions($USER->id);
        $modulecriteria = [];
        $nonactivitycompletions = [];
        foreach ($completions as $completion) {
            $criteria = $completion->get_criteria();
            $completion->typeaggregation = $info->get_aggregation_method($criteria->criteriatype);
            if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                if (!empty($criteria->moduleinstance)) {
                    $modulecriteria[$criteria->moduleinstance] = $completion;
                }
            } else {
                $nonactivitycompletions[] = $completion;
            }
        }

        // Compare to the course module order to put the activities in the same order as on the course view.
        $activitycompletions = [];
        foreach ($moduleorder as $module) {
            // Some modules may not have completion criteria and can be ignored.
            if (isset($modulecriteria[$module])) {
                $activitycompletions[] = $modulecriteria[$module];
            }
        }

        $orderedcompletions = [];

        // Put the activity completions at the top.
        foreach ($activitycompletions as $completion) {
            $orderedcompletions[] = $completion;
        }

        foreach ($nonactivitycompletions as $completion) {
            $orderedcompletions[] = $completion;
        }

        return $orderedcompletions;
    }

    /**
     * Check that this only works for course criterias.
     */
    public function test_resolve_criteria_only() {
        list($users, $courses) = $this->create_dataset();
        $this->setAdminUser();

        try {
            // Attempt to resolve an integer.
            $this->resolve('type', 7);
            $this->fail('Only completion_criteria_completion objects should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only completion_criteria_completion objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an array.
            $this->resolve('type', ['type' => 7]);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only completion_criteria_completion objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve a user item.
            $this->resolve('type', $users[0]);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only completion_criteria_completion objects are accepted: object',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an invalid object.
            $faux = new \stdClass();
            $faux->id = -1;
            $this->resolve('type', $faux);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only completion_criteria_completion objects are accepted: object',
                $ex->getMessage()
            );
        }

        // Check that each core instance of course criteria gets resolved.
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);
        foreach ($completions as $comp) {
            $criteria = $comp->get_criteria();
            $details = (object) $criteria->get_details($comp);

            try {
                $value = $this->resolve('type', $comp);
                $this->assertEquals($details->type, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course criteria type resolver for the type field
     */
    public function test_resolve_type() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);

        // Check that each core instance of course criteria gets resolved.
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);
        foreach ($completions as $comp) {
            $criteria = $comp->get_criteria();
            $details = (object) $criteria->get_details($comp);

            try {
                $value = $this->resolve('type', $comp);
                $this->assertEquals($details->type, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course criteria type resolver for the aggregation field
     */
    public function test_resolve_typeaggregation() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);

        // Check that each core instance of course criteria gets resolved.
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);
        foreach ($completions as $comp) {
            if ($comp->typeaggregation == COMPLETION_AGGREGATION_ALL) {
                $expected = get_string('all', 'completion');
            } else {
                $expected = get_string('any', 'completion');
            }

            try {
                $value = $this->resolve('typeaggregation', $comp);
                $this->assertEquals($expected, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course criteria type resolver for the criteria field
     */
    public function test_resolve_criteria() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);

        // Check that each core instance of course criteria gets resolved.
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);
        foreach ($completions as $comp) {
            $criteria = $comp->get_criteria();
            $details = (object) $criteria->get_details($comp);

            try {
                $value = $this->resolve('criteria', $comp);
                $this->assertEquals($details->criteria, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course criteria type resolver for the status field
     */
    public function test_resolve_status() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);

        // Check that each core instance of course criteria gets resolved.
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);
        foreach ($completions as $comp) {
            $criteria = $comp->get_criteria();
            $details = (object) $criteria->get_details($comp);

            try {
                $value = $this->resolve('status', $comp);
                $this->assertEquals($details->status, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course criteria type resolver for the requirement field
     */
    public function test_resolve_requirement() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);

        // Check that each core instance of course criteria gets resolved.
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);
        foreach ($completions as $comp) {
            $criteria = $comp->get_criteria();
            $details = (object) $criteria->get_details($comp);

            try {
                $value = $this->resolve('requirement', $comp);
                $this->assertEquals($details->requirement, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course criteria type resolver for the complete field
     */
    public function test_resolve_complete() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);

        // Check that each core instance of course criteria gets resolved.
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);
        foreach ($completions as $comp) {
            try {
                $value = $this->resolve('complete', $comp);
                $this->assertEquals($comp->is_complete(), $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course criteria type resolver for the completiondate field
     */
    public function test_resolve_completiondate() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);

        // Check that each core instance of course criteria gets resolved.
        $course = get_course($courses[0]->id);
        $completions = $this->fetch_course_criteria($course);
        foreach ($completions as $comp) {
            try {
                $value = $this->resolve('completiondate', $comp);
                $this->assertEquals($comp->timecompleted, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }
}
