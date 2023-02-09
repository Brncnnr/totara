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

use core_phpunit\testcase;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_before_event;
use totara_notification\schedule\schedule_on_event;
use totara_notification\webapi\resolver\mutation\validate_notification_preference_input;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_notification_webapi_validate_notification_preference_input_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * For <input/> tags, they are malicious value and will be stripped out by clean_param.
     * Hence we will be left with empty string, and that will fail the validation if the content is empty.
     *
     * @return void
     */
    public function test_validate_notification_preference_input_with_invalid_html(): void {
        $this->setAdminUser();
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(validate_notification_preference_input::class),
            [
                'title' => /** @lang text */ '<input type="text" value="value"/>',
                'body' => /** @lang text */ '<input type="text" value="value"/>',
                'subject' => /** @lang text */ '<input type="text" value="vvv"/>',
                'schedule_type' => '',
                'schedule_offset' => '',
            ]
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(4, $result);

        foreach ($result as $single_item) {
            self::assertIsArray($single_item);
            self::assertArrayHasKey('field_name', $single_item);
            self::assertArrayHasKey('error_message', $single_item);

            self::assertContainsEquals($single_item['field_name'], ['body', 'subject', 'title', 'schedule_type']);
            self::assertEquals(
                get_string('invalid_input', 'totara_notification'),
                $single_item['error_message']
            );
        }
    }


    /**
     * For <input/> tags, they are malicious value and will be stripped out by clean_param.
     * Hence we will be left with empty string, and that will fail the validation if the content is empty.
     *
     * @return void
     */
    public function test_validate_notification_preference_input_with_invalid_html_and_text(): void {
        $this->setAdminUser();
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(validate_notification_preference_input::class),
            [
                'title' => /** @lang text */ '<input type="text" value="value"/> Some randome text',
                'body' => /** @lang text */ '<input type="text" value="value"/>  Some randome text',
                'subject' => /** @lang text */ '<input type="text" value="vvv"/>  Some randome text',
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 10,
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
        self::assertCount(0, $result);
    }


    /**
     * With XSS content it is still a valid, because the text within the \<script\> tags
     * will still stay remain.
     *
     * @return void
     */
    public function test_validate_notification_preference_input_with_xss_content(): void {
        $this->setAdminUser();
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(validate_notification_preference_input::class),
            [
                'title' => /** @lang text */ '<script>alert(1)</script>',
                'body' => /** @lang text */ '<script>alert(1)</script>',
                'subject' => /** @lang text */ '<script>alert(1)</script>',
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 10,
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_validate_notification_preference_with_text(): void {
        $this->setAdminUser();
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(validate_notification_preference_input::class),
            [
                'title' => 'ccd',
                'body' => 'ccd',
                'subject' => 'ccd',
                'schedule_type' => schedule_before_event::identifier(),
                'schedule_offset' => 10,
            ]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    public function test_validate_notification_preference_with_invalid_schedule_type(): void {
        $this->setAdminUser();
        $result = $this->resolve_graphql_mutation(
            $this->get_graphql_name(validate_notification_preference_input::class),
            [
                'title' => 'ccd',
                'body' => 'ccd',
                'subject' => 'ccd',
                'schedule_type' => 'invalid_type',
                'schedule_offset' => 10,
            ]
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        foreach ($result as $single_item) {
            self::assertIsArray($single_item);
            self::assertArrayHasKey('field_name', $single_item);
            self::assertArrayHasKey('error_message', $single_item);

            self::assertContainsEquals($single_item['field_name'], ['schedule_type']);
            self::assertEquals(
                get_string('invalid_input', 'totara_notification'),
                $single_item['error_message']
            );
        }
    }

    public function test_validate_notification_preference_with_invalid_schedule_offset(): void {
        $this->setAdminUser();

        $invalid_combinations = [
            ['type' => schedule_on_event::identifier(), 'offset' => 5],
            ['type' => schedule_before_event::identifier(), 'offset' => -5],
            ['type' => schedule_before_event::identifier(), 'offset' => 0],
            ['type' => schedule_after_event::identifier(), 'offset' => -5],
            ['type' => schedule_after_event::identifier(), 'offset' => 0],
        ];

        foreach ($invalid_combinations as $invalid_combination) {
            $result = $this->resolve_graphql_mutation(
                $this->get_graphql_name(validate_notification_preference_input::class),
                [
                    'title' => 'ccd',
                    'body' => 'ccd',
                    'subject' => 'ccd',
                    'schedule_type' => $invalid_combination['type'],
                    'schedule_offset' => $invalid_combination['offset'],
                ]
            );

            self::assertIsArray($result);
            self::assertNotEmpty($result);
            self::assertCount(1, $result);
            foreach ($result as $single_item) {
                self::assertIsArray($single_item);
                self::assertArrayHasKey('field_name', $single_item);
                self::assertArrayHasKey('error_message', $single_item);

                self::assertContainsEquals($single_item['field_name'], ['schedule_type']);
                self::assertEquals(
                    get_string('invalid_input', 'totara_notification'),
                    $single_item['error_message']
                );
            }
        }
    }
}