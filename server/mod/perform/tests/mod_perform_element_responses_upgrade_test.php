<?php
/**
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\participant_section;

class mod_perform_element_responses_upgrade_testcase extends advanced_testcase {

    public function test_responses_submitted_have_their_timestamps_updated_to_participant_instance_creation_date() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/perform/db/upgradelib.php');
        $this->make_timestamp_columns_nullable();
        $this->setAdminUser();
        $user = get_admin();

        // Create some activity data.
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container();
        $subject_instance = $generator->create_subject_instance([
            'activity_id' => $activity->id, 'subject_username' => $user->username, 'subject_is_participating' => true,
        ]);
        $generator->create_responses($subject_instance);
        $time = time();
        participant_section::repository()->update(['updated_at' => $time]);
        element_response::repository()->update([
            'created_at' => null,
            'updated_at' => null,
        ]);

        mod_perform_upgrade_element_responses_to_include_timestamps();

        $element_responses = element_response::repository()->get();

        foreach ($element_responses as $element_response) {
            $this->assertEquals($element_response->created_at, $time);
            $this->assertEquals($element_response->updated_at, $time);
        }
    }

    private function make_timestamp_columns_nullable() {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table('perform_element_response');

        $created_at_field = new xmldb_field('created_at', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'response_data');
        $dbman->change_field_notnull($table, $created_at_field);

        $updated_at_field = new xmldb_field('updated_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'created_at');
        $dbman->change_field_notnull($table, $updated_at_field);
    }
}

