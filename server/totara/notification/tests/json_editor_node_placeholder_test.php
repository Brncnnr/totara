<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use core\json_editor\formatter\default_formatter;
use core_phpunit\testcase;
use totara_notification\json_editor\node\placeholder;

class totara_notification_json_editor_node_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    public function test_validate_schema(): void {
        self::assertTrue(
            placeholder::validate_schema([
                'type' => 'totara_notification_placeholder',
                'attrs' => [
                    'key' => 'course:fullname',
                    'label' => 'Full name',
                ],
            ])
        );

        self::assertFalse(
            placeholder::validate_schema([
                'type' => 'totara_notification_placeholder',
            ])
        );

        self::assertFalse(
            placeholder::validate_schema([
                'type' => 'totara_notification_placeholder',
                'other_key' => '',
                'attrs' => [
                    'key' => 'course:fullname',
                    'label' => 'Full name',
                ],
            ])
        );

        self::assertFalse(
            placeholder::validate_schema([
                'type' => 'totara_notification_placeholder',
                'other_key' => '',
                'attrs' => [
                    'key' => 'course:fullname',
                    'label' => 'Full name',
                ],
            ])
        );

        self::assertFalse(
            placeholder::validate_schema([
                'type' => 'totara_notification_placeholder',
                'attrs' => [
                    'key' => 'course:fullname',
                    'label' => 'Fullname',
                    'new_thing' => 'dota',
                ],
            ])
        );

        self::assertFalse(
            placeholder::validate_schema([
                'type' => 'totara_notification_placeholder',
                'attrs' => [
                    'key' => 'course:fullname',
                ],
            ])
        );

        self::assertFalse(
            placeholder::validate_schema([
                'type' => 'totara_notification_placeholder',
                'attrs' => [
                    'key' => 'course:data~',
                    'label' => 'Anything',
                ],
            ])
        );

        self::assertTrue(
            placeholder::validate_schema([
                'type' => 'totara_notification_placeholder',
                'attrs' => [
                    'key' => 'course:data_koko',
                    'label' => 'Anything',
                ],
            ])
        );

        // We need to reset debugging, as they are called multiple times.
        // This is only to silence this test, there are more tests to test the
        // debugging messages down from here.
        $this->resetDebugging();
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_invalid_key(): void {
        self::assertFalse(
            placeholder::validate_schema([
                'type' => 'totara_notification_placeholder',
                'attrs' => [
                    'key' => 'course:data+cd',
                    'label' => 'Anything',
                ],
            ])
        );

        $this->assertDebuggingCalled(
            "Invalid group key 'course:data+cd' in json node 'totara_notification_placeholder'"
        );
    }

    /**
     * @return void
     */
    public function test_clean_raw_node(): void {
        self::assertEquals(
            [
                'type' => placeholder::get_type(),
                'attrs' => [
                    'key' => 'hello:hello',
                    'label' => 'alert("boom")',
                ],
            ],
            placeholder::clean_raw_node([
                'type' => placeholder::get_type(),
                'attrs' => [
                    'key' => 'hello:hello',
                    'label' => /** @lang text */ '<script>alert("boom")</script>',
                ],
            ])
        );

        self::assertEquals(
            [
                'type' => placeholder::get_type(),
                'attrs' => [
                    'key' => 'hello:hello',
                    'label' => 'Abc',
                ],
            ],
            placeholder::clean_raw_node([
                'type' => placeholder::get_type(),
                'attrs' => [
                    'key' => '[hello:hello]',
                    'label' => 'Abc',
                ],
            ])
        );
    }

    /**
     * @return void
     */
    public function test_clean_raw_node_with_invalid_key(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid key value that does not match the pattern: 'hello+:hello+'");

        placeholder::clean_raw_node([
            'type' => placeholder::get_type(),
            'attrs' => [
                'key' => '[hello+:hello+]',
                'label' => 'Abc',
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_render_to_normal_html(): void {
        $formatter = new default_formatter();

        self::assertEquals(
            /** @lang text */ '<span data-key="key:doctor" data-label="Doctor">[key:doctor]</span>',
            (placeholder::from_node([
                'type' => placeholder::get_type(),
                'attrs' => [
                    'key' => 'key:doctor',
                    'label' => 'Doctor',
                ],
            ]))->to_html($formatter)
        );

        self::assertEquals(
            /** @lang text */ '<span data-key="key:doctor_what" data-label="Doctor">[key:doctor_what]</span>',
            (placeholder::from_node([
                'type' => placeholder::get_type(),
                'attrs' => [
                    'key' => 'key:doctor_what',
                    'label' => 'Doctor',
                ],
            ]))->to_html($formatter)
        );

        self::assertEquals(
            /** @lang text */ '<span data-key="key:doctor" data-label="Doctor">[key:doctor]</span>',
            (placeholder::from_node([
                'type' => placeholder::get_type(),
                'attrs' => [
                    'key' => '[key:doctor]',
                    'label' => 'Doctor',
                ],
            ]))->to_html($formatter)
        );

        self::assertEquals(
            /** @lang text */ '<span data-key="key:doctor" data-label="Doctor">[key:doctor]</span>',
            (placeholder::from_node([
                'type' => placeholder::get_type(),
                'attrs' => [
                    'key' => 'key+:doctor@*(*+()',
                    'label' => 'Doctor',
                ],
            ]))->to_html($formatter)
        );

        $xss = s(/** @lang text */ '<script>alert("Doctor")</script>');
        self::assertEquals(
            '<span data-key="key:doctor" data-label="' . $xss . '">[key:doctor]</span>',
            (placeholder::from_node([
                'type' => placeholder::get_type(),
                'attrs' => [
                    'key' => 'key:doctor',
                    'label' => /** @lang text */ '<script>alert("Doctor")</script>',
                ],
            ]))->to_html($formatter)
        );
    }
}