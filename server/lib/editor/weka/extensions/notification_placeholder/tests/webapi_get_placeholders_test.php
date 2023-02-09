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
 * @package weka_notification_placeholder
 */

use totara_notification\placeholder\option;
use totara_notification\placeholder\placeholder_option;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_event_resolver;
use totara_notification_mock_single_placeholder as mock_placeholder;
use totara_webapi\phpunit\webapi_phpunit_helper;
use weka_notification_placeholder\webapi\resolver\query\placeholders;

class weka_notification_placeholders_webapi_get_placeholders_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $generator->include_mock_notifiable_event();
        $generator->include_mock_single_placeholder();
        $generator->include_mock_notifiable_event_resolver();
    }

    /**
     * @return void
     */
    public function test_get_placeholders_of_a_resolver_with_empty_pattern(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');

        mock_placeholder::add_options(
            option::create('key_one', 'Key one'),
            option::create('key_two', 'Key two'),
            option::create('key_three', 'Key three'),
        );

        $invalid_callable = function (): void {
            throw new coding_exception("Do not call to this function in unit tests");
        };

        mock_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'user_one',
                mock_placeholder::class,
                $generator->give_my_mock_lang_string('User one'),
                $invalid_callable
            ),

            placeholder_option::create(
                'user_two',
                mock_placeholder::class,
                $generator->give_my_mock_lang_string('User two'),
                $invalid_callable
            )
        );

        $this->setAdminUser();
        $context_system = context_system::instance();

        // Execute the graphql and check for the result.
        $first_result = $this->resolve_graphql_query(
            $this->get_graphql_name(placeholders::class),
            [
                'context_id' => $context_system->id,
                'resolver_class_name' => mock_event_resolver::class,
                'pattern' => '',
            ]
        );

        // There are 6 placeholders in total.
        self::assertCount(6, $first_result);

        /** @var option $option */
        foreach ($first_result as $option) {
            self::assertContainsEquals(
                $option->get_key(),
                [
                    'user_one:key_one',
                    'user_one:key_two',
                    'user_one:key_three',
                    'user_two:key_one',
                    'user_two:key_two',
                    'user_two:key_three',
                ]
            );
        }

        $second_result = $this->resolve_graphql_query(
            $this->get_graphql_name(placeholders::class),
            [
                'context_id' => $context_system->id,
                'resolver_class_name' => mock_event_resolver::class,
                'pattern' => 'subject key one',
            ]
        );

        self::assertCount(2, $second_result);

        /** @var option $option */
        foreach ($second_result as $option) {
            self::assertContainsEquals(
                $option->get_key(),
                [
                    'user_one:key_one',
                    'user_two:key_one',
                ]
            );
        }

        $third_result = $this->resolve_graphql_query(
            $this->get_graphql_name(placeholders::class),
            [
                'context_id' => $context_system->id,
                'resolver_class_name' => mock_event_resolver::class,
                'pattern' => 'key one',
            ]
        );

        self::assertCount(2, $third_result);

        /** @var option $option */
        foreach ($third_result as $option) {
            self::assertContainsEquals(
                $option->get_key(),
                [
                    'user_one:key_one',
                    'user_two:key_one',
                ]
            );
        }
    }

    /**
     * @return void
     */
    public function test_fetch_query_with_invalid_resolver_class_name(): void {
        $this->setAdminUser();
        $context_system = context_system::instance();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The resolver class is not a notifiable event resolver');

        $this->resolve_graphql_query(
            $this->get_graphql_name(placeholders::class),
            [
                'context_id' => $context_system->id,
                'resolver_class_name' => 'somebody_else',
                'pattern' => '',
            ]
        );
    }
}