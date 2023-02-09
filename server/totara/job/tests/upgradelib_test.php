<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package totara_job
 */

use core_phpunit\testcase;

global $CFG;
require_once($CFG->dirroot . '/totara/job/db/upgradelib.php');

use totara_job\job_assignment;

/**
 * @group totara_job
 */
class totara_job_upgradelib_testcase extends testcase {
    public function test_fix_dangling_temp_manager_assignments(): void {
        global $DB;

        self::setAdminUser();

        $generator = self::getDataGenerator();
        $user_id = $generator->create_user()->id;
        $temp_mgr_ja_id = job_assignment::create([
            'userid' => $generator->create_user()->id,
            'fullname' => 'temp_mgr_ja',
            'shortname' => 'temp_mgr_ja',
            'idnumber' => 'temp_mgr_ja'
        ])->id;

        $now = time();
        $ja_valid_idnumber = 'test_ja';
        $valid_ja = [
            'userid' => $user_id,
            'fullname' => $ja_valid_idnumber,
            'shortname' => $ja_valid_idnumber,
            'idnumber' => $ja_valid_idnumber,
            'tempmanagerjaid' => $temp_mgr_ja_id,
            'tempmanagerexpirydate' => $now,
        ];
        $valid_ja_id = job_assignment::create($valid_ja)->id;

        // This test requires the job assignment table NOT to have referential
        // integrity. If the table has referential integrity, the temp manager
        // ja can never be invalid and this test is unnecessary.
        //
        // Also, invalid data has to be inserted directly into the database; the
        // job assignment class does not allow all affected fields to be updated
        // via its methods.
        $invalid_ref = -1 * $valid_ja_id;
        $ja_invalid_idnumber = 'test_invalid_ja';
        $invalid_ja = [
            'userid' => $user_id,
            'fullname' => $ja_invalid_idnumber,
            'shortname' => $ja_invalid_idnumber,
            'idnumber' => $ja_invalid_idnumber,
            'positionid' => $invalid_ref,
            'positionassignmentdate' => $invalid_ref,
            'organisationid' => $invalid_ref,
            'managerjaid' => $invalid_ref,
            'managerjapath' => "/$invalid_ref",
            'tempmanagerjaid' => $invalid_ref,
            'tempmanagerexpirydate' => $now,
            'appraiserid' => $invalid_ref,
            'totarasync' => true,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => get_admin()->id,
            'sortorder' => 999
        ];
        $DB->insert_record('job_assignment', $invalid_ja);

        // Confirm ja records has expected values.
        foreach ([$valid_ja, $invalid_ja] as $ja) {
            $idnumber = ['idnumber' => $ja['idnumber']];
            $record = (array) $DB->get_record(
                'job_assignment',
                $idnumber,
                implode(',', array_keys($ja)),
                MUST_EXIST
            );

            $this->assertEquals($ja, $record);
        }

        // 'Run' upgrade.
        totara_job_fix_dangling_temp_manager_assignments();

        // Confirm ja records have been fixed.
        $fixed_ja = [
            'userid' => $user_id,
            'fullname' => $ja_invalid_idnumber,
            'shortname' => $ja_invalid_idnumber,
            'idnumber' => $ja_invalid_idnumber,
            'tempmanagerjaid' => null,
            'tempmanagerexpirydate' => null
        ];

        foreach ([$valid_ja, $fixed_ja] as $ja) {
            $idnumber = ['idnumber' => $ja['idnumber']];
            $record = (array)$DB->get_record(
                'job_assignment',
                $idnumber,
                implode(',', array_keys($ja)),
                MUST_EXIST
            );

            $this->assertEquals($ja, $record);
        }
    }
}