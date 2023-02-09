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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_program
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_notification\placeholder\option;
use totara_program\utils;
use totara_program\testing\generator as program_generator;
use totara_program\totara_notification\placeholder\assignment;
use totara_program\totara_notification\placeholder\program as program_placeholder_group;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification totara_program
 */
class totara_program_totara_notification_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        global $CFG;
        require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');
    }

    public function test_program_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, program_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            ['full_name', 'full_name_link'],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $data = $this->setup_program();

        $placeholder_group = program_placeholder_group::from_id($data->program1->id);
        self::assertEquals('My program1 full name', $placeholder_group->do_get('full_name'));
        self::assertEquals(
            '<a href="https://www.example.com/moodle/totara/program/view.php?id='
            . $data->program1->id . '">My program1 full name</a>',
            $placeholder_group->do_get('full_name_link')
        );
    }

    public function test_program_assignment_placeholders_not_available(): void {
        $placeholder_group = assignment::from_program_id_and_user_id(1, - 1);
        self::assertEquals('', $placeholder_group->get('due_date'));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The program assignment record is empty');
        $placeholder_group->do_get('due_date');
    }

    public function test_program_assignment_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, assignment::get_options());
        self::assertEqualsCanonicalizing(
            ['due_date', 'due_date_criteria', 'program_full_name_manager_link'],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $data = $this->setup_program();
        $placeholder_group = assignment::from_program_id_and_user_id($data->program1->id, $data->user1->id);
        self::assertEquals(
            userdate($data->due_date->getTimestamp(), '%d/%m/%Y', 99, false),
            $placeholder_group->do_get('due_date')
        );

        // Remove due date
        builder::table('prog_completion')
            ->where('programid', $data->program1->id)
            ->where('userid', $data->user1->id)
            ->update(['timedue' => 0]);
        self::assertEquals('No due date set', $placeholder_group->do_get('due_date'));

        // Set due date back to value from above.
        builder::table('prog_completion')
            ->where('programid', $data->program1->id)
            ->where('userid', $data->user1->id)
            ->update(['timedue' => $data->due_date->getTimestamp()]);
        self::assertEquals('Due date criteria not defined', $placeholder_group->do_get('due_date_criteria'));

        // Update completion time and set completion event to 'none'
        $user_assignment = builder::table('prog_user_assignment')
            ->where('programid', $data->program1->id)
            ->where('userid', $data->user1->id)
            ->one(true);
        $now = time();
        builder::table('prog_assignment')
            ->where('id', $user_assignment->assignmentid)
            ->update([
                'completiontime' => $now,
                'completionevent' => COMPLETION_EVENT_NONE,
            ]);
        assignment::clear_instance_cache();
        $placeholder_group = assignment::from_program_id_and_user_id($data->program1->id, $data->user1->id);
        self::assertStringContainsString('Complete by', $placeholder_group->do_get('due_date_criteria'));
        self::assertEquals(prog_assignment_category::build_completion_string(
            userdate($now, '%d/%m/%Y', 99, false), 0, 0
        ), $placeholder_group->do_get('due_date_criteria'));

        // Update completion time and set completion event to 'program completion'.
        builder::table('prog_assignment')
            ->where('id', $user_assignment->assignmentid)
            ->update([
                'completiontime' => null,
                'completionoffsetamount' => 1,
                'completionoffsetunit' => utils::TIME_SELECTOR_MONTHS,
                'completionevent' => COMPLETION_EVENT_PROGRAM_COMPLETION,
            ]);
        assignment::clear_instance_cache();
        $placeholder_group = assignment::from_program_id_and_user_id($data->program1->id, $data->user1->id);
        self::assertStringContainsString(
            'Complete within 1 Month(s) of completion of program',
            $placeholder_group->do_get('due_date_criteria')
        );

        assignment::clear_instance_cache();
        $placeholder_group = assignment::from_program_id_and_user_id($data->program1->id, $data->user1->id);
        $url = new \moodle_url(
            '/totara/program/required.php',
            [
                'id' => $data->program1->id,
                'userid' => $data->user1->id
            ]
        );
        self::assertStringContainsString(
            \html_writer::link($url, 'My program1 full name'),
            $placeholder_group->do_get('program_full_name_manager_link')
        );
    }

    public function test_program_assignment_placeholder_instances_are_cached(): void {
        global $DB;

        self::setAdminUser();
        $data = $this->setup_program();

        $query_count = $DB->perf_get_reads();
        assignment::from_program_id_and_user_id($data->program1->id, $data->user1->id);
        // When not cached: 2 queries are needed for lookup.
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        assignment::from_program_id_and_user_id($data->program1->id, $data->user1->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        // Cache also works for non-existent ids.
        $non_existent_id = - 1;
        assignment::from_program_id_and_user_id($non_existent_id , $non_existent_id);
        // ... but only one query is triggered when assignment doesn't exist.
        self::assertEquals($query_count + 3, $DB->perf_get_reads());

        assignment::from_program_id_and_user_id($data->program1->id, $data->user1->id);
        assignment::from_program_id_and_user_id($non_existent_id , $non_existent_id);
        self::assertEquals($query_count + 3, $DB->perf_get_reads());
    }

    private function setup_program(): object {
        self::setAdminUser();

        $test_data = new class() {
            public $user1;
            /** @var program */
            public $program1;
            /** @var DateTime */
            public $due_date;
        };

        // Make sure it works with certifications turned off.
        set_config('enablecertifications', advanced_feature::DISABLED);

        $generator = self::getDataGenerator();
        $program_generator = program_generator::instance();

        $test_data->user1 = $generator->create_user(['lastname' => 'My user1 last name']);
        $test_data->program1 = $program_generator->create_program(['fullname' => 'My program1 full name']);

        // Create two courses.
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        // Assign courses to program.
        $coursesetdata = [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => [$course1]
            ],
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => [$course2]
            ],
        ];
        $program_generator->legacy_add_coursesets_to_program($test_data->program1, $coursesetdata);

        // Assign user to courses.
        $generator->enrol_user($test_data->user1->id, $course1->id);
        $generator->enrol_user($test_data->user1->id, $course2->id);

        // Assign user to program.
        $program_generator->assign_program($test_data->program1->id, [$test_data->user1->id]);

        $test_data->due_date = new DateTime('2020-10-25', new DateTimeZone('Pacific/Auckland'));
        $prog_compl1 = prog_load_completion($test_data->program1->id, $test_data->user1->id);
        $prog_compl1->timedue = $test_data->due_date->getTimestamp();
        self::assertTrue(prog_write_completion($prog_compl1));

        return $test_data;
    }

    public function test_instances_are_cached(): void {
        global $DB;

        self::setAdminUser();

        $program_generator = program_generator::instance();

        $program1 = $program_generator->create_program();
        $program2 = $program_generator->create_program();

        $query_count = $DB->perf_get_reads();
        program_placeholder_group::from_id($program1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        program_placeholder_group::from_id($program1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        program_placeholder_group::from_id($program2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        program_placeholder_group::from_id($program1->id);
        program_placeholder_group::from_id($program2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }
}
