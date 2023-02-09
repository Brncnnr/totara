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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\dates\resolvers\dynamic\dynamic_source;
use mod_perform\dates\resolvers\dynamic\user_creation_date;

/**
 * @coversDefaultClass \mod_perform\models\activity\track
 *
 * @group perform
 */
class mod_perform_date_resolvers_dynamic_source_testcase extends advanced_testcase {

    /**
     * Simple sanity test.
     */
    public function test_all_available_factory_method(): void {
        $all_options = dynamic_source::all_available();

        self::assertGreaterThan(0, $all_options->count());
        self::assertContainsOnlyInstancesOf(dynamic_source::class, $all_options);
    }

    public function test_create_from_json_missing_fields(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('resolver_class_name and option_key fields are mandatory');

        dynamic_source::create_from_json([]);
    }

    /**
     * Test that options that may have been valid at one point can still be created.
     * Or not if the must be available flag is set.
     */
    public function test_create_from_json_unavailable_resolver_class(): void {
        $dynamic_source = dynamic_source::create_from_json([
            'resolver_class_name' => 'fake_resolver_class_name',
            'option_key' => 'fake_option_key',
            'display_name' => 'Display name fed in',
        ]);

        self::assertFalse($dynamic_source->is_available());
        self::assertNull($dynamic_source->get_resolver_class_name());
        self::assertEquals('Display name fed in', $dynamic_source->get_display_name());
        self::assertEquals('fake_option_key', $dynamic_source->get_option_key());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Source is not available');
        dynamic_source::create_from_json([
            'resolver_class_name' => 'fake_resolver_class_name',
            'option_key' => 'fake_option_key',
            'display_name' => 'Display name fed in',
        ], true);
    }

    public function test_create_from_json_invalid_option_key(): void {
        /** @var dynamic_source $user_creation_date_option */
        $user_creation_date_option = (new user_creation_date())->get_options()->first();

        $dynamic_source = dynamic_source::create_from_json([
            'resolver_class_name' => $user_creation_date_option->get_resolver_class_name(),
            'option_key' => 'fake_option_key',
            'display_name' => $user_creation_date_option->get_display_name(),
        ]);

        self::assertFalse($dynamic_source->is_available());
        self::assertEquals(user_creation_date::class, $dynamic_source->get_resolver_class_name());
        self::assertEquals('User creation date', $dynamic_source->get_display_name());
        self::assertEquals('fake_option_key', $dynamic_source->get_option_key());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Source is not available');
        dynamic_source::create_from_json([
            'resolver_class_name' => $user_creation_date_option->get_resolver_class_name(),
            'option_key' => 'fake_option_key',
            'display_name' => $user_creation_date_option->get_display_name(),
        ], true);
    }

    /**
     * @param string|array $data
     * @param bool $must_be_available
     * @dataProvider create_from_json_available_provider
     */
    public function test_create_from_json_valid_and_available($data, bool $must_be_available): void {
        $dynamic_source = dynamic_source::create_from_json($data, $must_be_available);

        self::assertTrue($dynamic_source->is_available());
        self::assertEquals(user_creation_date::class, $dynamic_source->get_resolver_class_name());
        self::assertEquals('User creation date', $dynamic_source->get_display_name());
        self::assertEquals(user_creation_date::DEFAULT_KEY, $dynamic_source->get_option_key());
    }

    public function create_from_json_available_provider(): array {
        /** @var dynamic_source $user_creation_date_option */
        $user_creation_date_option = (new user_creation_date())->get_options()->first();

        $data = [
            'resolver_class_name' => $user_creation_date_option->get_resolver_class_name(),
            'option_key' => $user_creation_date_option->get_option_key(),
            'display_name' => 'the display name will be reloaded from source',
        ];

        return [
            'json string and must be available' => [json_encode($data), true],
            'json string and does not need to be available' => [json_encode($data), false],
            'data array and must be available' => [$data, true],
            'data array and does not need to be available' => [$data, false],
        ];
    }

}
