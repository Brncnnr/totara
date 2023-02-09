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
use totara_notification\placeholder\template_engine\square_bracket\engine;
use totara_notification\testing\generator;
use totara_notification_mock_notifiable_event_resolver as mock_notifiable_event_resolver;
use totara_notification_mock_single_placeholder as mock_placeholder;

class totara_notification_square_bracket_engine_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_single_placeholder();
        $generator->include_mock_notifiable_event_resolver();
    }

    /**
     * @return void
     */
    public function test_get_all_placeholders_from_text(): void {
        $ref_class = new ReflectionClass(engine::class);
        $method = $ref_class->getMethod('get_all_placeholders_from_text');
        $method->setAccessible(true);

        self::assertEmpty($method->invokeArgs(null, ["This is an awesome text"]));
        self::assertEquals(
            ['[author:firstname]', '[author:lastname]'],
            $method->invokeArgs(
                null,
                [
                    implode(
                        "\n",
                        [
                            'hello world from [author:firstname]',
                            'by [author:lastname]',
                        ]
                    ),
                ]
            )
        );

        self::assertEquals(
            ['[author:102]', '[author:lastname]'],
            $method->invokeArgs(
                null,
                [
                    implode(
                        "\n",
                        [
                            '[boom_boom_pow], [from+:c101] [author:102]',
                            'by [author:lastname] and [from:+koko] with [with:kk~text]',
                            'and [doc~tor:who], [(a:bcd)] and ([bob:builder&electrician])',
                            '[token:*]',
                        ]
                    ),
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_replace_text(): void {
        $generator = generator::instance();

        mock_placeholder::set_options(
            option::create('firstname', 'First name'),
            option::create('lastname', 'Last name'),
        );

        mock_notifiable_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'user',
                mock_placeholder::class,
                $generator->give_my_mock_lang_string("Hello world"),
                function (array $event_data): mock_placeholder {
                    return new mock_placeholder($event_data);
                }
            )
        );

        $engine = engine::create(
            mock_notifiable_event_resolver::class,
            [
                'firstname' => 'Martin',
                'lastname' => 'Garrix',
            ]
        );

        $text = implode(
            "\n",
            [
                'Hello, this is [user:firstname] [user:lastname] here.',
                'You are listening to [user:firstname]\'s channel.',
            ]
        );

        $admin = get_admin();
        self::assertEquals(
            "Hello, this is Martin Garrix here.\nYou are listening to Martin's channel.",
            trim($engine->render_for_user($text, $admin->id))
        );
    }

    /**
     * This is to test {@see engine::get_map_matches()}
     * @return void
     */
    public function test_get_map_matches(): void {
        $text = "The new [course:fullname] had added to the new program [program:shortname]";
        $engine = engine::create(mock_notifiable_event_resolver::class, []);

        $ref_class = new ReflectionClass($engine);
        $method = $ref_class->getMethod('get_map_matches');
        $method->setAccessible(true);

        $result = $method->invokeArgs($engine, [$text]);
        $expected_map = [
            'course' => [
                'fullname' => '[course:fullname]',
            ],
            'program' => [
                'shortname' => '[program:shortname]',
            ],
        ];

        self::assertEquals($expected_map, $result);
    }

    /**
     * This is to test {@see engine::get_map_matches()}
     * @return void
     */
    public function test_get_map_matches_with_invalid_key(): void {
        $text = "The new [course:fullname] had added to the new program [program:short~name]";
        $engine = engine::create(mock_notifiable_event_resolver::class, []);

        $ref_class = new ReflectionClass($engine);
        $method = $ref_class->getMethod('get_map_matches');
        $method->setAccessible(true);

        $result = $method->invokeArgs($engine, [$text]);
        $expected_map = [
            'course' => [
                'fullname' => '[course:fullname]',
            ],
        ];

        self::assertEquals($expected_map, $result);
    }

    /**
     * @return void
     */
    public function test_replace_with_invalid_map_matches(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');

        mock_placeholder::add_options(option::create('fullname', 'Full name'));
        mock_notifiable_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'course',
                mock_placeholder::class,
                $generator->give_my_mock_lang_string('boom'),
                function (array $event_data): mock_placeholder {
                    return new mock_placeholder($event_data);
                }
            )
        );

        $text = "The new [course:fullname] had added to the new program [program:shortname] and an invalid [program:+shortname]";
        $engine = engine::create(mock_notifiable_event_resolver::class, ['fullname' => 'Full name']);

        $admin = get_admin();
        self::assertEquals(
            "The new Full name had added to the new program [program:shortname] and an invalid [program:+shortname]",
            $engine->render_for_user($text, $admin->id)
        );

        $this->assertDebuggingCalled('The key prefix \'program\' does not exist in the list of available placeholder options');
    }

    /**
     * @return void
     */
    public function test_render_content_with_non_supported_placeholder_keys_in_content(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');

        mock_placeholder::add_options(
            option::create('fullname', 'fullname'),
            option::create('shortname', 'shortname')
        );

        mock_notifiable_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'course',
                mock_placeholder::class,
                $generator->give_my_mock_lang_string('cc'),
                function (array $data): mock_placeholder {
                    return new mock_placeholder($data);
                }
            )
        );

        $text = "The new [course:idnumber] has a full name as [course:fullname] and short name as [course:shortname]";
        $engine = engine::create(
            mock_notifiable_event_resolver::class,
            [
                'fullname' => 'Full name',
                'shortname' => 'Short name',
            ]
        );

        $admin = get_admin();
        self::assertEquals(
            "The new [course:idnumber] has a full name as Full name and short name as Short name",
            $engine->render_for_user($text, $admin->id)
        );

        $this->assertDebuggingCalled(
            "The placeholder key '[course:idnumber]' is not a valid placeholder key provided by the options list"
        );
    }

    /**
     * @return void
     */
    public function test_render_content_with_xss_content_of_user_fields(): void {
        /** @var generator $notification_generator */
        $notification_generator = self::getDataGenerator()->get_plugin_generator('totara_notification');

        mock_placeholder::add_options(option::create('firstname', 'First name'));
        mock_notifiable_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'user',
                mock_placeholder::class,
                $notification_generator->give_my_mock_lang_string('User'),
                function (array $event_data): mock_placeholder {
                    return new mock_placeholder($event_data);
                }
            )
        );

        $engine = engine::create(
            mock_notifiable_event_resolver::class,
            ['firstname' => /** @lang text */ '<script>alert("doom bringer")</script>']
        );

        $admin = get_admin();
        self::assertNotEquals(
            /** @lang text */ 'Hello <script>alert("doom")</script>',
            $engine->render_for_user(
                "Hello [user:firstname]",
                $admin->id
            )
        );

        // Mustache engine is escaping `<script/>` tag - hence we should be safe from xss. Unless we would want to strip out
        // the <script/> tag from the user's value.
        self::assertEquals(
            s(/** @lang text */ 'Hello <script>alert("doom bringer")</script>'),
            $engine->render_for_user(
                "Hello [user:firstname]",
                $admin->id
            )
        );
    }
}