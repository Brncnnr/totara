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
use core_user\external\user_helper;
use core_user\profile\field\field_helper;
use core\orm\query\builder;
use totara_tenant\exception\unresolved_tenant_reference;
use core\entity\tenant as tenant_entity;

/**
 * Unit tests for the External API user_helper class.
 */
class core_user_user_helper_testcase extends testcase {
    /**
     * @return void
     */
    public function test_restructure_custom_field_inputs(): void {
        $mock = [
            'custom_fields' => [
                [
                    'shortname' => 'textarea',
                    'data' => 'textarea',
                    'data_format' => 1
                ],
                [
                    'shortname' => 'text',
                    'data' => 'text',
                ],
            ]
        ];
        $input = user_helper::restructure_custom_field_inputs($mock);
        self::assertArrayHasKey(field_helper::format_custom_field_short_name('textarea'), $input);
        self::assertArrayHasKey(field_helper::format_custom_field_short_name('text'), $input);

        $value = $input[field_helper::format_custom_field_short_name('textarea')];
        // Only textarea contains format and text.
        self::assertArrayHasKey('text', $value);
        self::assertArrayHasKey('format', $value);

        $value = $input[field_helper::format_custom_field_short_name('text')];
        self::assertEquals('text', $value);
    }

    /**
     * @return void
     */
    public function test_restructure_custom_field_inputs_for_multiple_same_shortnames(): void {
        $mock = [
            'custom_fields' => [
                [
                    'shortname' => 'text',
                    'data' => 'text1',
                ],
                [
                    'shortname' => 'text',
                    'data' => 'text2',
                ],
                [
                    'shortname' => 'text',
                    'data' => 'text3',
                ],
                [
                    'shortname' => 'menu',
                    'data' => '0',
                ],
                [
                    'shortname' => 'textarea',
                    'data' => 'textarea',
                    'data_format' => 1
                ],
            ]
        ];

        $input = user_helper::restructure_custom_field_inputs($mock);
        self::assertEquals(3, count(array_keys($input)));

        // Get last value.
        self::assertEquals('text3', $input[field_helper::format_custom_field_short_name('text')]);
    }

    /**
     * @return void
     */
    public function test_restructure_custom_fields_for_save(): void {
        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');

        // Only menu field define 'convert_external_data()'
        $menu = $generator->create_custom_profile_field(['datatype' => 'menu', 'param1' => ['xx', 'yy', 'zz']]);
        $user = self::getDataGenerator()->create_user();
        $key = field_helper::format_custom_field_short_name($menu->shortname);
        $user->$key = 'qqq';

        $user = user_helper::restructure_custom_fields_for_save($user);
        self::assertNull($user->$key);
    }

    /**
     * @return void
     */
    public function test_save_custom_fields_data(): void {
        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');

        $text = $generator->create_custom_profile_field(['datatype' => 'text']);
        $checkbox = $generator->create_custom_profile_field(['datatype' => 'checkbox', 'defaultdata' => 0]);
        $date = $generator->create_custom_profile_field(['datatype' => 'date']);
        $menu = $generator->create_custom_profile_field(['datatype' => 'menu', 'param1' => ['xx', 'yy', 'zz']]);
        $textarea = $generator->create_custom_profile_field(['datatype' => 'textarea']);
        $datetime = $generator->create_custom_profile_field(['datatype' => 'datetime']);

        $user = self::getDataGenerator()->create_user();
        $key = field_helper::format_custom_field_short_name($menu->shortname);
        $user->$key = 'xx';
        $key = field_helper::format_custom_field_short_name($text->shortname);
        $user->$key = 'text';
        $key = field_helper::format_custom_field_short_name($textarea->shortname);
        $user->$key = 'textarea';
        $key = field_helper::format_custom_field_short_name($date->shortname);
        $user->$key = '2011-01-11';
        $key = field_helper::format_custom_field_short_name($datetime->shortname);
        $user->$key = '2011-01-12';
        $key = field_helper::format_custom_field_short_name($checkbox->shortname);
        $user->$key = '0';

        user_helper::save_custom_fields_data($user);

        $data = builder::get_db()->get_records('user_info_data', ['userid' => $user->id]);
        self::assertCount(6, $data);

        $data = array_map(function ($info_data) {
            self::assertNotNull($info_data->data);
            return $info_data->data;
        }, $data);

        self::assertTrue(in_array('textarea', $data));
        self::assertTrue(in_array(0, $data));
        self::assertTrue(in_array('text', $data));
        self::assertTrue(in_array('xx', $data));
        // Date/datetime converts timestamp.
        self::assertTrue(in_array(1294747200, $data));
        self::assertTrue(in_array(1294761600, $data));
    }

    /**
     * @return void
     */
    public function test_validate_tenant_by_id(): void {
        global $CFG;
        // Set up
        $this->setAdminUser();
        $original_config = $CFG->tenantsenabled;

        // Create a tenant & set is as suspended, so when we search for it, it won't be found.
        $tenant_generator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant_entity = tenant_entity::repository()->find($tenant1->id);
        $tenant_entity->suspended = 1;
        $tenant_entity->save();

        // Expect tenant to be not found & fail.
        try {
            user_helper::validate_tenant_by_id($tenant1->id, 0);
        } catch (unresolved_tenant_reference $exception) {
            $this->assertEquals('Tenant reference must identify exactly one tenant.', $exception->getMessage());
        }

        // Expect tenant to be found & succeed.
        user_helper::validate_tenant_by_id($tenant1->id, 1);

        // Tear down
        set_config('tenantsenabled', $original_config);
    }

}