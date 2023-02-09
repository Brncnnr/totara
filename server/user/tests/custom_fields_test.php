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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core_user
 */

use core_phpunit\testcase;
use core\orm\query\builder;
use core_user\profile\custom_fields;

class core_user_custom_fields_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_custom_fields(): void {
        self::setAdminUser();

        $gen = self::getDataGenerator();
        /** @var \totara_core\testing\generator $generator */
        $generator = $gen->get_plugin_generator('totara_core');

        $text = $generator->create_custom_profile_field(['datatype' => 'text']);
        $checkbox = $generator->create_custom_profile_field(['datatype' => 'checkbox', 'defaultdata' => 0]);
        $date = $generator->create_custom_profile_field(['datatype' => 'date']);
        $menu = $generator->create_custom_profile_field(['datatype' => 'menu', 'param1' => ['xx', 'yy', 'zz']]);
        $textarea = $generator->create_custom_profile_field(['datatype' => 'textarea']);
        $datetime = $generator->create_custom_profile_field(['datatype' => 'datetime']);

        $user = $gen->create_user();
        builder::get_db()->insert_record('user_info_data', ['userid' => $user->id, 'fieldid' => $text->id, 'data' => 'text']);
        builder::get_db()->insert_record('user_info_data', ['userid' => $user->id, 'fieldid' => $menu->id, 'data' => 'yy']);
        builder::get_db()->insert_record('user_info_data', ['userid' => $user->id, 'fieldid' => $textarea->id, 'data' => 'textarea', 'dataformat' => 1]);
        builder::get_db()->insert_record('user_info_data', ['userid' => $user->id, 'fieldid' => $date->id, 'data' => mktime(0, 0, 0, 7, 1, 2000)]);
        builder::get_db()->insert_record('user_info_data', ['userid' => $user->id, 'fieldid' => $checkbox->id, 'data' => '1']);
        builder::get_db()->insert_record('user_info_data', ['userid' => $user->id, 'fieldid' => $datetime->id, 'data' => mktime(0, 0, 0, 7, 1, 2022)]);

        $custom_fields = custom_fields::create($user);

        self::assertCount(6, $custom_fields);
        $data = array_map(function ($custom_field) {
            self::assertArrayHasKey('shortname', $custom_field);
            self::assertArrayHasKey('data', $custom_field);
            self::assertArrayHasKey('data_format', $custom_field);
            return $custom_field['data'];
        }, $custom_fields);

        self::assertTrue(in_array('textarea', $data));
        self::assertTrue(in_array('962380800', $data));
        self::assertTrue(in_array('1', $data));
        self::assertTrue(in_array('text', $data));
        self::assertTrue(in_array('yy', $data));
        self::assertTrue(in_array('1656604800', $data));
    }
}