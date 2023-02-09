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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\user_custom_field;

/**
 * Class mod_perform_date_resolver_dynamic_source_user_custom_field_testcase
 *
 * @group perform
 */
class mod_perform_date_resolver_dynamic_source_user_custom_field_testcase extends advanced_testcase {

    public function test_get_option() {
        $this->generate_test_data();
        $custom_field_date_resolver = new user_custom_field();
        $result = $custom_field_date_resolver->get_options();
        $this->assertCount(2, $result);
    }

    public function test_option_is_available() {
        $this->generate_test_data();
        $custom_field_date_resolver = new user_custom_field();
        $this->assertTrue($custom_field_date_resolver->option_is_available('datetime-1'));

        $this->assertFalse($custom_field_date_resolver->option_is_available('not-existing-key'));
    }

    public function test_resolve() {
        $data = $this->generate_test_data();
        $custom_field_date_resolver = new user_custom_field();
        $custom_field_date_resolver->set_parameters(
            new date_offset(1, date_offset::UNIT_DAY, date_offset::DIRECTION_BEFORE),
            new date_offset(1, date_offset::UNIT_DAY, date_offset::DIRECTION_BEFORE),
            'datetime-1',
            [$data['user1']->id]
        );

        $start_date_ts = $custom_field_date_resolver->get_start($data['user1']->id);
        $start_date = (new DateTime())->setTimestamp($start_date_ts);
        $this->assertSame('2020-06-12', $start_date->format('Y-m-d'));

        //check non saving user custom fields
        $start_date_ts = $custom_field_date_resolver->get_start($data['user2']->id);
        $this->assertNull($start_date_ts);
    }

    private function generate_test_data(): array {
        global $DB;
        $data = [];
        $data_generator = $this->getDataGenerator();
        $data['user1'] = $data_generator->create_user();
        $data['user2'] = $data_generator->create_user();
        $DB->delete_records('user_info_field');
        $DB->insert_record('user_info_field', (object)['shortname' => 'ch-1', 'name' => 'n 1', 'categoryid' => 1, 'datatype' => 'checkbox']);
        $DB->insert_record('user_info_field', (object)['shortname' => 'rex-1', 'name' => 'text 1', 'categoryid' => 1, 'datatype' => 'text']);
        $data['datetime-1']  = $DB->insert_record('user_info_field', (object)['shortname' => 'datetime-1', 'name' => 'time 1', 'categoryid' => 1, 'datatype' => 'datetime']);
        $data['datetime-2'] = $DB->insert_record('user_info_field', (object)['shortname' => 'datetime-2', 'name' => 'time 2', 'categoryid' => 1, 'datatype' => 'datetime']);

        $DB->insert_record('user_info_data', (object)['userid' => $data['user1']->id, 'fieldid' => $data['datetime-1'], 'data' => 1592006400]);
        return $data;
    }
}
