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

use core_user\profile\field\summary_field_provider;
use core_user\profile\field\field_helper;
use core_user\profile\field\metadata;

/**
 * Test to make sure that the card field provider is working
 */
class core_user_summary_card_field_provider_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_available_custom_fields(): void {
        $provider = new summary_field_provider();
        $empty_set = $provider->get_available_custom_fields();

        $this->assertEmpty($empty_set);

        // Add custom fields, which is including text, menu and date time.
        // Provider will only able to fetch the text/menu for now, but not date time.
        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator = $generator->get_plugin_generator('core_user');

        // Generate text field.
        $text_field = $user_generator->create_custom_field('text', 'text_me');

        // Generate menu field.
        $menu_field = $user_generator->create_custom_field('menu', 'menu_me');

        // Generate date time field.
        $user_generator->create_custom_field('datetime', 'datetime_me');

        $custom_fields = $provider->get_available_custom_fields(true);
        $this->assertCount(2, $custom_fields);

        // Date time custom field will be excluded.
        foreach ($custom_fields as $custom_field) {
            $short_name = $custom_field->get_key();
            $this->assertNotEquals(
                'datetime_me',
                $short_name,
                "The provider does NOT fetch any other custom field rather than 'text' and 'menu'"
            );

            $this->assertContains(
                $short_name,
                [
                    field_helper::format_custom_field_short_name($text_field->field->shortname),
                    field_helper::format_custom_field_short_name($menu_field->field->shortname)
                ]
            );
        }
    }

    /**
     * This is to prevent the changes on the metadata function, as if there are changes related to
     * the metadata function itself, the changer/author will have to update this test.
     *
     * @return void
     */
    public function test_get_system_fields(): void {
        $system_fields = summary_field_provider::get_system_fields();
        $this->assertCount(14, $system_fields);

        foreach ($system_fields as $field) {
            $this->assertInstanceOf(metadata::class, $field);
        }
    }

    /**
     * @return void
     */
    public function test_get_provide_fields(): void {
        $provider = new summary_field_provider();
        $original_fields = $provider->get_all_provide_fields();

        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator = $generator->get_plugin_generator('core_user');
        for ($i = 0; $i < 5; $i++) {
            $user_generator->create_custom_field('text', uniqid());
        }

        // Reload custom fields
        $provider->get_available_custom_fields(true);
        $all_fields = $provider->get_all_provide_fields();

        // The original fields will not have the custom fields, as by default, there are no custom fields.
        $this->assertGreaterThan(count($original_fields), count($all_fields));
        $this->assertCount(
            (count($original_fields) + 5),
            $all_fields,
            "All the fields MUST include the system fields and the newly created custom fields"
        );
    }

    /**
     * @return void
     */
    public function test_find_system_field(): void {
        $fields = summary_field_provider::get_system_fields();
        $provider = new summary_field_provider();

        foreach ($fields as $field) {
            $field_name = $field->get_key();
            $this->assertNotNull(
                $provider->get_field_metadata($field_name),
                "Cannot find the system field with '{$field_name}'"
            );
        }
    }

    /**
     * @return void
     */
    public function test_find_custom_field(): void {
        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator = $generator->get_plugin_generator('core_user');
        $custom_fields = [];

        for ($i = 0; $i < 5; $i++) {
            $short_name = "short_name_{$i}";
            $user_generator->create_custom_field('menu', $short_name);

            $custom_fields[] = $short_name;
        }

        $provider = new summary_field_provider();
        foreach ($custom_fields as $custom_field) {
            // This will fail because the custom_field without prefix 'custom_' will not be found.
            $this->assertNull(
                $provider->get_field_metadata($custom_field),
                "The custom field '{$custom_field}' should not be found"
            );

            $prefixed_short_name = field_helper::format_custom_field_short_name($custom_field);
            $this->assertNotNull(
                $provider->get_field_metadata($prefixed_short_name),
                "The custom field that got prefixed '{$prefixed_short_name}' should be found"
            );
        }
    }
}