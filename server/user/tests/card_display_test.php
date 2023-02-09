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

use core_user\profile\user_field_resolver;
use core_user\profile\card_display;
use core_user\profile\value_card_display_field;
use core_user\profile\null_card_display_field;
use core_user\profile\display_setting;

class core_user_card_display_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_construct_default_instance(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $resolver = user_field_resolver::from_record($user_one);
        $card_display = card_display::create($resolver);

        $display_fields = $card_display->get_card_display_fields();
        $this->assertCount(4, $display_fields);

        // First two items of the list are default to 'fullname' and 'department'
        $full_name_field = $display_fields[0];
        $department_field = $display_fields[1];

        $this->assertInstanceOf(value_card_display_field::class, $full_name_field);
        $this->assertInstanceOf(value_card_display_field::class, $department_field);

        // Last two items of the list are not set.
        $empty_one_field = $display_fields[2];
        $empty_two_field = $display_fields[3];

        $this->assertInstanceOf(null_card_display_field::class, $empty_one_field);
        $this->assertInstanceOf(null_card_display_field::class, $empty_two_field);

        // Check for the values.
        $this->assertEmpty($department_field->get_field_value());
        $this->assertEquals(fullname($user_one), $full_name_field->get_field_value());
    }

    /**
     * @return void
     */
    public function test_save_custom_invalid_fields(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        display_setting::save_display_fields(['custom_field_one', '', '', '']);
        $this->setUser($user_one);

        $resolver = user_field_resolver::from_record($user_one);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Coding error detected, it must be fixed by a programmer: " .
            "Cannot find the field metadata from field name 'custom_field_one'"
        );

        card_display::create($resolver);
    }

    /**
     * @return void
     */
    public function test_custom_field(): void {
        global $CFG;

        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator = $generator->get_plugin_generator('core_user');
        $custom_field = $user_generator->create_custom_field('text', 'short_text');

        $user_one = $generator->create_user();
        $user_one->profile_field_short_text = 'This is short-text';

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        profile_save_data($user_one);

        display_setting::save_display_fields(['fullname', 'profile_field_short_text', '', '']);

        // Check on the card display.
        $this->setAdminUser();

        $resolver = user_field_resolver::from_record($user_one);
        $card_display = card_display::create($resolver);

        $display_fields = $card_display->get_card_display_fields();

        // First two of items of the list are fullname and profile_field_short_text.
        // However we are only interesting in the custom field.
        $custom_display_field = $display_fields[1];

        $this->assertTrue($custom_display_field->is_custom_field());
        $this->assertEquals($custom_field->field->name, $custom_display_field->get_field_label());
        $this->assertEquals('This is short-text', $custom_display_field->get_field_value());
        $this->assertNull($custom_display_field->get_field_url());
    }
}