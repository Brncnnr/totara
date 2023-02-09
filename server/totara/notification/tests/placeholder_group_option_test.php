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
use totara_notification\placeholder\option;
use totara_notification\placeholder\placeholder_option;
use totara_notification\testing\generator;
use totara_notification_mock_single_placeholder as mock_placeholder;

class totara_notification_placeholder_group_option_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_placeholder_options_as_group(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $generator->include_mock_single_placeholder();

        mock_placeholder::add_options(
            option::create('random_one_key', 'One'),
            option::create('random_two_key', 'One'),
            option::create('random_three_key', 'One'),
            option::create('random_four_key', 'One'),
            option::create('random_five_key', 'One'),
            option::create('random_six_key', 'One'),
        );

        $placeholder_option = placeholder_option::create(
            'object',
            mock_placeholder::class,
            $generator->give_my_mock_lang_string('Object'),
            function (): void {
                throw new coding_exception("Do not call to this function in this unit test");
            }
        );

        $options = $placeholder_option->get_map_group_options();
        foreach ($options as $option) {
            self::assertStringContainsString('object:', $option->get_key());
            self::assertContainsEquals(
                $option->get_key(),
                [
                    'object:random_one_key',
                    'object:random_two_key',
                    'object:random_three_key',
                    'object:random_four_key',
                    'object:random_five_key',
                    'object:random_six_key',
                ]
            );
        }
    }

    /**
     * @return void
     */
    public function test_get_provided_placeholder(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $generator->include_mock_single_placeholder();

        mock_placeholder::add_options(
            option::create('random_one_key', 'One'),
            option::create('random_two_key', 'One'),
            option::create('random_three_key', 'One'),
            option::create('random_four_key', 'One'),
            option::create('random_five_key', 'One'),
            option::create('random_six_key', 'One'),
        );

        $placeholder_option = placeholder_option::create(
            'object',
            mock_placeholder::class,
            $generator->give_my_mock_lang_string('Object'),
            function (): void {
                throw new coding_exception("Do not call to this function in this unit test");
            }
        );

        $options = $placeholder_option->get_provided_placeholder_options();
        foreach ($options as $option) {
            self::assertStringNotContainsString('object:', $option->get_key());
            self::assertContainsEquals(
                $option->get_key(),
                [
                    'random_one_key',
                    'random_two_key',
                    'random_three_key',
                    'random_four_key',
                    'random_five_key',
                    'random_six_key',
                ]
            );
        }
    }

    /**
     * @return void
     */
    public function test_find_group_options_by_match(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $generator->include_mock_single_placeholder();

        mock_placeholder::add_options(
            option::create('fullname', 'Full name'),
            option::create('lastname', 'Last name'),
            option::create('email', 'Email'),
            option::create('doctor', 'Doctor What?')
        );

        $placeholder_option = placeholder_option::create(
            'user',
            mock_placeholder::class,
            $generator->give_my_mock_lang_string('User'),
            function (): void {
                throw new coding_exception("Do not call to this function in here");
            }
        );

        $first_result = $placeholder_option->find_map_group_options_match('random');
        self::assertEmpty($first_result);

        $second_result = $placeholder_option->find_map_group_options_match('em');
        self::assertCount(1, $second_result);

        $second_result_option = reset($second_result);
        self::assertEquals('user:email', $second_result_option->get_key());

        $third_result = $placeholder_option->find_map_group_options_match('');
        self::assertCount(4, $third_result);

        $fourth_result = $placeholder_option->find_map_group_options_match('[Subj');
        self::assertCount(4, $fourth_result);
    }
}