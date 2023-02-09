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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\job_assignment_start_date;
use totara_job\job_assignment;

/**
 * Class mod_perform_job_assignment_start_date_resolver_testcase
 *
 * @group perform
 */
class mod_perform_job_assignment_start_date_resolver_testcase extends advanced_testcase {

    public function test_get_option() {
        $this->generate_test_data();
        $job_assignment_start_date_resolver = new job_assignment_start_date();
        $result = $job_assignment_start_date_resolver->get_options();
        $this->assertCount(1, $result);
    }

    public function test_option_is_available() {
        $this->generate_test_data();
        $job_assignment_start_date_resolver = new job_assignment_start_date();
        $this->assertTrue($job_assignment_start_date_resolver->option_is_available('job-assignment-start-date'));

        $this->assertFalse($job_assignment_start_date_resolver->option_is_available('not-existing-key'));
    }

    public function test_resolve() {
        $data = $this->generate_test_data();
        $job_assignment_start_date_resolver = new job_assignment_start_date();
        $job_assignment_start_date_resolver->set_parameters(
            new date_offset(1, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
            new date_offset(2, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
            'job-assignment-start-date',
            [$data['user1-job2-with-start']->id]
        );

        // Job based, get_start should always return null
        $start_date_user = $job_assignment_start_date_resolver->get_start($data['user1']->id);
        $this->assertNull($start_date_user);
        $start_date_user = $job_assignment_start_date_resolver->get_start($data['user2']->id);
        $this->assertNull($start_date_user);
        $end_date_user = $job_assignment_start_date_resolver->get_end($data['user1']->id);
        $this->assertNull($end_date_user);
        $end_date_user = $job_assignment_start_date_resolver->get_end($data['user2']->id);
        $this->assertNull($end_date_user);

        $start_date_job1 = $job_assignment_start_date_resolver->get_start($data['user1-job1']->id);
        $this->assertNull($start_date_job1);

        // Unknown job assignment id
        $start_date_job1 = $job_assignment_start_date_resolver->get_start($data['user1']->id);
        $this->assertNull($start_date_job1);

        $expected = strtotime("+1 day", 123);
        $start_job2 = $job_assignment_start_date_resolver->get_start($data['user1-job2-with-start']->id);
        $this->assertSame($expected, $start_job2);

        // End days are adjusted to end of day
        $expected = strtotime("+2 day", 123) + DAYSECS;
        $end_job2 = $job_assignment_start_date_resolver->get_end($data['user1-job2-with-start']->id);
        $this->assertSame($expected, $end_job2);
    }

    private function generate_test_data(): array {
        set_config('totara_job_allowmultiplejobs', 1);

        $data = [];
        $data_generator = $this->getDataGenerator();
        $data['user1'] = $data_generator->create_user();
        $data['user2'] = $data_generator->create_user();
        $data['user1-job1'] = job_assignment::create([
            'userid' => $data['user1']->id,
            'idnumber' => 'job1',
        ]);
        $data['user1-job2-with-start'] = job_assignment::create([
            'userid' => $data['user1']->id,
            'idnumber' => 'job2',
            'startdate' => 123
        ]);
        $data['user2-job3'] = job_assignment::create([
            'userid' => $data['user2']->id,
            'idnumber' => 'job3',
            'startdate' => 456
        ]);

        return $data;
    }
}
