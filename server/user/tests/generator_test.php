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

class core_user_generator_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_text_custom_field(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator =  $generator->get_plugin_generator('core_user');

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        require_once("{$CFG->dirroot}/user/profile/field/text/field.class.php");

        $custom_field = $user_generator->create_custom_field('text', 'short_name');
        $this->assertInstanceOf(profile_field_text::class, $custom_field);

        $this->assertNotEmpty($custom_field->fieldid);
        $this->assertObjectHasAttribute('shortname', $custom_field->field);
        $this->assertEquals('short_name', $custom_field->field->shortname);
        $this->assertTrue($DB->record_exists('user_info_field', ['id' => $custom_field->fieldid]));
    }

    /**
     * @return void
     */
    public function test_create_menu_custom_field(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator =  $generator->get_plugin_generator('core_user');

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        require_once("{$CFG->dirroot}/user/profile/field/menu/field.class.php");

        $custom_field = $user_generator->create_custom_field('menu', 'short_name');
        $this->assertInstanceOf(profile_field_menu::class, $custom_field);

        $this->assertNotEmpty($custom_field->fieldid);
        $this->assertObjectHasAttribute('shortname', $custom_field->field);
        $this->assertEquals('short_name', $custom_field->field->shortname);
        $this->assertTrue($DB->record_exists('user_info_field', ['id' => $custom_field->fieldid]));
    }

    /**
     * @return void
     */
    public function test_create_datetime_custom_field(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator =  $generator->get_plugin_generator('core_user');

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        require_once("{$CFG->dirroot}/user/profile/field/datetime/field.class.php");

        $custom_field = $user_generator->create_custom_field('datetime', 'short_name');
        $this->assertInstanceOf(profile_field_datetime::class, $custom_field);

        $this->assertNotEmpty($custom_field->fieldid);
        $this->assertObjectHasAttribute('shortname', $custom_field->field);
        $this->assertEquals('short_name', $custom_field->field->shortname);
        $this->assertTrue($DB->record_exists('user_info_field', ['id' => $custom_field->fieldid]));
    }

    /**
     * @return void
     */
    public function test_create_date_custom_field(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator =  $generator->get_plugin_generator('core_user');

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        require_once("{$CFG->dirroot}/user/profile/field/date/field.class.php");

        $custom_field = $user_generator->create_custom_field('date', 'short_name');
        $this->assertInstanceOf(profile_field_date::class, $custom_field);

        $this->assertNotEmpty($custom_field->fieldid);
        $this->assertObjectHasAttribute('shortname', $custom_field->field);
        $this->assertEquals('short_name', $custom_field->field->shortname);
        $this->assertTrue($DB->record_exists('user_info_field', ['id' => $custom_field->fieldid]));
    }

    /**
     * @return void
     */
    public function test_create_textarea_custom_field(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator =  $generator->get_plugin_generator('core_user');

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        require_once("{$CFG->dirroot}/user/profile/field/textarea/field.class.php");

        $custom_field = $user_generator->create_custom_field('textarea', 'short_name');
        $this->assertInstanceOf(profile_field_textarea::class, $custom_field);

        $this->assertNotEmpty($custom_field->fieldid);
        $this->assertObjectHasAttribute('shortname', $custom_field->field);
        $this->assertEquals('short_name', $custom_field->field->shortname);
        $this->assertTrue($DB->record_exists('user_info_field', ['id' => $custom_field->fieldid]));
    }
}