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
 * @package totara_certification
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use totara_core\advanced_feature;
use totara_job\job_assignment;
use totara_program\testing\generator as program_generator;
use totara_program\totara_notification\placeholder\assignment;
use totara_program\totara_notification\placeholder\program as program_placeholder;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
abstract class totara_certification_totara_notification_base extends testcase {
    protected function setup_certifications(): stdClass {
        self::setAdminUser();

        // Delete built-in notifications.
        builder::table('notification_preference')->delete();

        $test_data = new stdClass();

        // Make sure it works with programs turned off.
        set_config('enableprograms', advanced_feature::DISABLED);

        $generator = self::getDataGenerator();
        $program_generator = program_generator::instance();

        // Create a user with two managers.
        $test_data->user1 = $generator->create_user(['firstname' => 'User1 first name', 'lastname' => 'User1 last name']);
        $manager1 = $generator->create_user(['firstname' => 'Manager1 first name', 'lastname' => 'Manager1 last name']);
        $manager2 = $generator->create_user(['firstname' => 'Manager2 first name', 'lastname' => 'Manager2 last name']);
        /** @var job_assignment $manager1job */
        $manager1job = job_assignment::create(['userid' => $manager1->id, 'idnumber' => 'job1']);
        /** @var job_assignment $manager2job */
        $manager2job = job_assignment::create(['userid' => $manager2->id, 'idnumber' => 'job2']);
        job_assignment::create([
            'userid' => $test_data->user1->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager1job->id
        ]);
        job_assignment::create([
            'userid' => $test_data->user1->id,
            'idnumber' => 'userjob2',
            'managerjaid' => $manager2job->id
        ]);

        // Create two certifications.
        $test_data->program1 = $program_generator->create_certification(['fullname' => 'My certification1 full name']);
        $test_data->program2 = $program_generator->create_certification(['fullname' => 'My certification2 full name']);

        // Create two courses.
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        // Assign courses to certification.
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

        // Assign users to courses.
        $generator->enrol_user($test_data->user1->id, $course1->id);
        $generator->enrol_user($test_data->user1->id, $course2->id);

        // Assign user to certification and assigned event will be triggered.
        $program_generator->assign_program($test_data->program1->id, [$test_data->user1->id]);

        $test_data->due_date = new DateTime('2020-10-25', new DateTimeZone('Pacific/Auckland'));
        [$certif_compl1, $prog_compl1] = certif_load_completion($test_data->program1->id, $test_data->user1->id);
        $prog_compl1->timedue = $test_data->due_date->getTimestamp();
        self::assertTrue(certif_write_completion($certif_compl1, $prog_compl1));

        return $test_data;
    }

    protected function create_extra_fake_user_assignment(int $program_id, int $user_id): void {
        $existing_assignment = builder::table('prog_assignment')
            ->where('programid', '=', $program_id)
            ->one(false);
        $existing_assignment->assignmenttypeid = -$user_id; // Use the negative, so that we can call this with another user.
        unset($existing_assignment->id);
        $new_assignment_id = builder::table('prog_assignment')
            ->insert($existing_assignment);
        builder::table('prog_user_assignment')
            ->insert([
                'programid' => $program_id,
                'userid' => $user_id,
                'assignmentid' => $new_assignment_id,
                'timeassigned' => time(),
            ]);
    }

    /**
     * @param string $resolver_class_name
     * @param int $min_time
     * @param int $max_time
     * @param array $expected
     */
    protected static function assert_scheduled_events(
        string $resolver_class_name,
        int $min_time,
        int $max_time,
        array $expected
    ): void {
        $events = call_user_func([$resolver_class_name, 'get_scheduled_events'], $min_time, $max_time);
        $actual = $events->to_array();
        $actual_to_array = array_map(static function (stdClass $scheduled) {
            return (array)$scheduled;
        }, $actual);
        self::assertEqualsCanonicalizing(
            $expected,
            $actual_to_array,
            'Expected: ' . json_encode($expected) . ' but got: ' . json_encode($actual_to_array)
        );
    }
}
