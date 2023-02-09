<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_job
 */

defined('MOODLE_INTERNAL') || die();

use core\exception\unresolved_record_reference;
use core_phpunit\testcase;
use totara_job\job_assignment;
use totara_job\reference\job_assignment_record_reference;

/**
 * @group totara_job
 */
class totara_job_job_assignment_reference_testcase extends testcase {

    /**
     * @return void
     */
    public function test_find_reference_by_id(): void {
        self::setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $job_assignment1 = job_assignment::create_default($user1->id);
        job_assignment::create_default($user2->id);

        $reference = new job_assignment_record_reference();
        $record = $reference->get_record(['id' => $job_assignment1->id]);
        $this->assertEquals($job_assignment1->id, $record->id);
    }

    /**
     * @return void
     */
    public function test_find_reference_by_nonexisting_id(): void {
        self::setAdminUser();

        $reference = new job_assignment_record_reference();
        $this->expectException(unresolved_record_reference::class);
        $reference->get_record(['id' => 5789]);
    }

    /**
     * @return void
     */
    public function test_find_reference_by_idnumber(): void {
        self::setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        job_assignment::create_default($user1->id, ['idnumber' => 3]);
        $job_assignment2 = job_assignment::create_default($user2->id, ['idnumber' => 5]);

        $reference = new job_assignment_record_reference();
        $record = $reference->get_record(['idnumber' => $job_assignment2->idnumber]);
        $this->assertEquals($job_assignment2->id, $record->id);
        $this->assertEquals($job_assignment2->idnumber, $record->idnumber);
    }

    /**
     * @return void
     */
    public function test_match_multiple_job_assignments(): void {
        self::setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        job_assignment::create_default($user1->id, ['idnumber' => 5]);
        job_assignment::create_default($user2->id, ['idnumber' => 5]);

        $reference = new job_assignment_record_reference();
        $this->expectException(unresolved_record_reference::class);
        $reference->get_record(['idnumber' => 5]);
    }
}
