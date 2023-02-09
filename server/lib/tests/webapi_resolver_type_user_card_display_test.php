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

use core_user\access_controller;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_user\profile\user_field_resolver;
use core_user\profile\card_display;
use core_user\profile\display_setting;
use totara_core\hook\manager as hook_manager;

class core_webapi_resolver_type_user_card_display_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    public function setUp(): void {
        parent::setUp();

        // Clear all the hooks so that the test can be accurate.
        hook_manager::phpunit_replace_watchers([]);
        access_controller::clear_instance_cache();
    }

    /**
     * @return void
     */
    public function test_resolve_user_picture_url_field(): void {
        global $PAGE;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Since the access controller can only live within one session of per user, hence we have to
        // reset the resolver/card display every time we switch different user in session.

        // Viewing user profile picture as non admin
        $this->setUser($user_two);
        $resolver = user_field_resolver::from_record($user_one);
        $card_display = card_display::create($resolver);

        $empty_result = $this->resolve_graphql_type('core_user_card_display', 'profile_picture_url', $card_display, []);
        $this->assertNull($empty_result);

        // Viewing user's profile picture as admi, since
        $this->setAdminUser();
        $resolver = user_field_resolver::from_record($user_one);
        $card_display = card_display::create($resolver);

        $picture = new user_picture($user_one, 1);
        $result = $this->resolve_graphql_type('core_user_card_display', 'profile_picture_url', $card_display, []);
        $this->assertSame($picture->get_url($PAGE)->out(false), $result);
    }

    /**
     * @return void
     */
    public function test_resolve_user_picture_url_field_with_setting_toggle(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        display_setting::save_display_user_profile(false);
        $this->setAdminUser();

        $resolver = user_field_resolver::from_record($user_one);
        $card_display = card_display::create($resolver);

        $empty_result = $this->resolve_graphql_type('core_user_card_display', 'profile_picture_url', $card_display, []);
        $this->assertNull($empty_result);

        display_setting::save_display_user_profile(true);
        $result = $this->resolve_graphql_type('core_user_card_display', 'profile_picture_url', $card_display, []);
        $this->assertNotNull($result);
    }

    /**
     * @return void
     */
    public function test_resolve_user_picture_alt_field(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user(['imagealt' => 'something']);

        display_setting::save_display_user_profile(false);
        $this->setAdminUser();

        $resolver = user_field_resolver::from_record($user_one);
        $card_display = card_display::create($resolver);

        $empty_result = $this->resolve_graphql_type('core_user_card_display', 'profile_picture_alt', $card_display, []);
        $this->assertNull($empty_result);

        display_setting::save_display_user_profile(true);
        $result = $this->resolve_graphql_type('core_user_card_display', 'profile_picture_alt', $card_display, []);
        $this->assertEquals('something', $result);
    }

    /**
     * @return void
     */
    public function test_resolve_user_display_field(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user(['imagealt' => 'something']);

        $this->setUser($user_one);

        $resolver = user_field_resolver::from_record($user_one);
        $card_display = card_display::create($resolver);

        $display_fields = $this->resolve_graphql_type('core_user_card_display', 'display_fields', $card_display, []);
        $this->assertCount(display_setting::MAGIC_NUMBER_OF_DISPLAY_FIELDS, $display_fields);
    }
}