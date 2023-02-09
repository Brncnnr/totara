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
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_user\profile\value_card_display_field;
use core_user\profile\user_field_resolver;
use core_user\profile\field\summary_field_provider;
use core_user\profile\display_setting;
use core_user\profile\card_display;

class core_webapi_resolver_type_user_card_display_field_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_resolve_field_value_for_valid_instance(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $provider = new summary_field_provider();

        // Testing own user seeing self field
        $this->setUser($user_one);

        $resolver = user_field_resolver::from_record($user_one);
        $field_metadata = $provider->get_field_metadata('fullname');

        $field_display = new value_card_display_field($resolver, $field_metadata);
        $result = $this->resolve_graphql_type(
            'core_user_card_display_field',
            'value', $field_display,
            ['format' => format::FORMAT_PLAIN]
        );

        $this->assertSame(fullname($user_one), $result);
    }

    /**
     * @return void
     */
    public function test_resolve_field_value_for_custom_field(): void {
        global $CFG;

        $generator = $this->getDataGenerator();

        /** @var \core_user\testing\generator $user_generator */
        $user_generator = $generator->get_plugin_generator('core_user');
        $user_generator->create_custom_field('text', 'text_short_name');

        $user_one = $generator->create_user();
        $user_one->profile_field_text_short_name = 'Something worth it';

        require_once("{$CFG->dirroot}/user/profile/lib.php");
        profile_save_data($user_one);

        // Update display setting.
        display_setting::save_display_fields(['fullname', 'profile_field_text_short_name', '', '']);

        // Log in as user one and check user's own card.
        $this->setUser($user_one);
        $resolver = user_field_resolver::from_record($user_one);
        $card_display = card_display::create($resolver);

        $display_fields = $card_display->get_card_display_fields();

        // First display field will be fullname.
        $fullname_display_field = $display_fields[0];
        $this->assertInstanceOf(value_card_display_field::class, $fullname_display_field);

        $this->assertEquals(
            fullname($user_one),
            $this->resolve_graphql_type(
                'core_user_card_display_field',
                'value', $fullname_display_field,
                ['format' => format::FORMAT_PLAIN]
            )
        );

        $custom_display_field = $display_fields[1];
        $this->assertInstanceOf(value_card_display_field::class, $custom_display_field);

        $this->assertEquals(
            'Something worth it',
            $this->resolve_graphql_type(
                'core_user_card_display_field',
                'value', $custom_display_field,
                ['format' => format::FORMAT_PLAIN]
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_value_with_special_value(): void {
        $generator = $this->getDataGenerator();
        $record['firstname'] = "$%^$%^'@$#@~";
        $record['lastname'] = '@!$##@test';
        $user_one = $generator->create_user($record);

        $provider = new summary_field_provider();

        // Testing own user seeing self field
        $this->setUser($user_one);

        $resolver = user_field_resolver::from_record($user_one);
        $field_metadata = $provider->get_field_metadata('fullname');

        $field_display = new value_card_display_field($resolver, $field_metadata);
        $result = $this->resolve_graphql_type(
            'core_user_card_display_field',
            'value', $field_display,
            ['format' => format::FORMAT_PLAIN]
        );

        $this->assertSame("$%^$%^'@$#@~ @!$##@test", $result);

        $result = $this->resolve_graphql_type(
            'core_user_card_display_field',
            'value', $field_display,
            ['format' => format::FORMAT_HTML]
        );

        $this->assertSame("$%^$%^&#39;@$#@~ @!$##@test", $result);
    }
}