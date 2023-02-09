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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\models\activity\activity_setting;
use mod_perform\state\activity\draft;
use totara_webapi\phpunit\webapi_phpunit_helper;

class webapi_query_manual_relationship_options_testcase extends advanced_testcase {

    private $query = 'mod_perform_manual_relationship_selector_options';

    use webapi_phpunit_helper;

    public function test_query_successful() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $args = $this->create_activity($user);

        $this->setUser($user);

        $records = $DB->get_records('totara_core_relationship', ['type' => 0]);
        $result = $this->resolve_graphql_query($this->query, $args);

        $this->assertSameSize($records, $result);

        foreach ($records as $relationship) {
            $this->assertEquals($relationship->idnumber, $result->item($relationship->id)->idnumber);
        }
    }

    public function test_without_manager_capability() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $args = $this->create_activity($user1);

        $this->setUser($user2);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid activity');

        $this->resolve_graphql_query($this->query, $args);
    }

    /**
     * Creates an activity with one section, one question and one relationship
     *
     * @param stdClass|null $as_user
     * @return array
     * @throws coding_exception
     */
    protected function create_activity(?stdClass $as_user = null): array {
        self::setUser($as_user);

        $data_generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container([
            'activity_name' => 'Activity One',
            'activity_status' => draft::get_code()
        ]);

        $activity->get_settings()->update([activity_setting::MULTISECTION => true]);

        $args = [
            'activity_id' => $activity->id
        ];

        return $args;
    }
}