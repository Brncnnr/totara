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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_user
 */
defined('MOODLE_INTERNAL') || die();

use core_user\profile\display_setting;
use core_user\profile\field\field_helper;

class core_user_display_setting_testcase extends advanced_testcase {
    /**
     * If any of dev are going to change the metadata function, then probably they will have
     * to go to this test and fix it !
     *
     * @return void
     */
    public function test_get_default_display_setting(): void {
        $default_result = display_setting::get_display_fields();
        $this->assertNotEmpty($default_result);

        $expected = [
            field_helper::format_position_key(0) => 'fullname',
            field_helper::format_position_key(1) => 'department',
            field_helper::format_position_key(2) => null,
            field_helper::format_position_key(3) => null
        ];

        $this->assertSame($expected, $default_result);
    }

    /**
     * This is to make sure that if any of dev(s) are going to update the metadata function they
     * will have to update this test too.
     *
     * @return void
     */
    public function test_get_default_display_user_picture(): void {
        $this->assertTrue(display_setting::display_user_picture());
    }

    /**
     * @return void
     */
    public function test_update_invalid_display_fields(): void {
        try {
            display_setting::save_display_fields(['woop', 'x', 'me', 'd', 'z']);
        } catch (coding_exception $e) {
            $this->assertStringContainsString(
                "The number of fields exceeds the limit of acceptable fields",
                $e->getMessage()
            );
        }

        try {
            display_setting::save_display_fields([]);
        } catch (coding_exception $e) {
            $this->assertStringContainsString(
                "There must be at least a field to be not empty",
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_normalise_save_display_fields(): void {
        display_setting::save_display_fields(['custom_user', 'custom_me']);
        $value = get_config('core_user', display_setting::SETTING_FIELD_KEY);

        $this->assertEquals('custom_user,custom_me,,', $value);
        $fields = display_setting::get_display_fields();

        $expect = [
            field_helper::format_position_key(0) => 'custom_user',
            field_helper::format_position_key(1) => 'custom_me',
            field_helper::format_position_key(2) => null,
            field_helper::format_position_key(3) => null
        ];

        $this->assertSame($expect, $fields);
    }

    /**
     * @return void
     */
    public function test_save_display_user_picture(): void {
        display_setting::save_display_user_profile(true);
        $this->assertEquals(1, get_config('core_user', display_setting::SETTING_PICTURE_KEY));

        display_setting::save_display_user_profile(false);
        $this->assertEquals(0, get_config('core_user', display_setting::SETTING_PICTURE_KEY));
    }

    /**
     * @return void
     */
    public function test_save_display_fields_that_existing_in_hidden_user_fields(): void {
        set_config('hiddenuserfields', 'skypeid');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Coding error detected, it must be fixed by a programmer: " .
            "Cannot save field 'skypeid' as it is appearing in the list of hidden user fields"
        );

        display_setting::save_display_fields(['skypeid', 'admin', 'me', 'd']);
    }

    /**
     * @return void
     */
    public function test_save_display_fields_that_are_duplicated(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'Coding error detected, it must be fixed by a programmer: Display fields cannot be duplicated'
        );
        display_setting::save_display_fields(['skypeid', 'skypeid', 'me', 'ddd']);
    }

    /**
     * @return void
     */
    public function test_remove_display_setting(): void {
        global $DB;

        $shortname = 'newfield';
        $custom_profile_id = $DB->insert_record(
            'user_info_field',
            [
                'shortname' => $shortname,
                'name' => 'Description of new field',
                'categoryid' => 1,
                'datatype' => 'text'
            ]
        );

        $format_name = field_helper::format_custom_field_short_name($shortname);
        display_setting::save_display_fields([$format_name]);

        self::assertTrue(in_array($format_name, array_values(display_setting::get_display_fields())));
        display_setting::remove_display_field_by_id($custom_profile_id);
        self::assertFalse(in_array($format_name, array_values(display_setting::get_display_fields())));
    }
}