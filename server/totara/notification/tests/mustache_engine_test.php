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
use totara_notification\placeholder\template_engine\mustache\engine as mustache_engine;
use totara_notification\testing\generator;
use totara_notification_mock_collection_placeholder as collection_placeholder;
use totara_notification_mock_invalid_placeholder as invalid_placeholder;
use totara_notification_mock_notifiable_event_resolver as mock_event_resolver;
use totara_notification_mock_single_placeholder as single_placeholder;

class totara_notification_mustache_engine_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
    }

    /**
     * Rendering content with single getter item.
     * @return void
     */
    public function test_render_mustache_content(): void {
        $generator = self::getDataGenerator();

        $user = $generator->create_user();
        $user->fullname = fullname($user);

        $notification_generator = generator::instance();
        $notification_generator->include_mock_single_placeholder();

        single_placeholder::add_options(
            option::create('fullname', 'Fullname'),
            option::create('firstname', 'Firstname'),
            option::create('lastname', 'Lastname'),
            option::create('email', 'Email'),
            option::create('city', 'City'),
        );

        mock_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'user',
                single_placeholder::class,
                $notification_generator->give_my_mock_lang_string('Lang string'),
                function (array $event_data): single_placeholder {
                    return new single_placeholder($event_data);
                }
            )
        );

        $user_data = get_object_vars($user);
        $engine = mustache_engine::create(mock_event_resolver::class, $user_data);

        // Normal rendering - this is the main behaviour from content parsing, which we are expecting
        // the content to be something like this.
        $admin = get_admin();
        self::assertEquals(
            "Hello {$user->fullname}, your email is {$user->email} and ur first name is {$user->firstname} " .
             "but there is no city ",
            $engine->render_for_user(
                "Hello {{user.fullname}}, your email is {{user.email}} and ur first name is {{user.firstname}} " .
                "but there is no city {{user.city}}",
                $admin->id
            )
        );

        // Rendering with group - this is discourage by us, however mustache is supporting it.
        // Hence here we are to keep it at least covered with test.
        self::assertEquals(
            "Hello {$user->fullname}, your email is {$user->email} and ur first name is {$user->firstname}",
            $engine->render_for_user(
                "Hello {{#user}}{{fullname}}{{/user}}, your email is " .
                "{{#user}}{{email}}{{/user}} and ur first name is {{#user}}{{firstname}}{{/user}}",
                $admin->id
            )
        );
    }

    /**
     * @return void
     */
    public function test_instantiate_invalid_instance(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The resolver class is not a valid notifiable event resolver");
        mustache_engine::create('invalid_class', []);
    }

    /**
     * @return void
     */
    public function test_rendering_invalid_placeholder(): void {
        $notification_generator = generator::instance();
        $notification_generator->include_mock_invalid_placeholder();

        mock_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'random',
                invalid_placeholder::class,
                $notification_generator->give_my_mock_lang_string('Lang string'),
                function (): invalid_placeholder {
                    return new invalid_placeholder();
                }
            )
        );

        $engine = mustache_engine::create(mock_event_resolver::class, []);
        $admin = get_admin();

        // We still able to render the content. However since the context variables are
        // not provided by placeholder (AKA invalid_placeholder) - then debugging is called.
        self::assertEquals(
            "Some random ",
            $engine->render_for_user("Some random {{stuff}}", $admin->id)
        );

        $this->assertDebuggingCalled("Invalid placeholder instance that is not either a collection or single getter");
    }

    /**
     * @return void
     */
    public function test_render_mustache_content_with_loop(): void {
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_one->fullname = fullname($user_one);

        $user_two = $generator->create_user();
        $user_two->fullname = fullname($user_two);

        $notification_generator = generator::instance();
        $notification_generator->include_mock_collection_placeholder();

        mock_event_resolver::add_placeholder_options(
            placeholder_option::create(
                'users',
                collection_placeholder::class,
                $notification_generator->give_my_mock_lang_string('Lang string'),
                function (array $event_data): collection_placeholder {
                    $user_one = core_user::get_user($event_data['user_one']);
                    $user_one->fullname = fullname($user_one);

                    $user_two = core_user::get_user($event_data['user_two']);
                    $user_two->fullname = fullname($user_two);

                    return new collection_placeholder([
                        get_object_vars($user_one),
                        get_object_vars($user_two),
                    ]);
                }
            )
        );

        $engine = mustache_engine::create(
            mock_event_resolver::class,
            [
                'user_one' => $user_one->id,
                'user_two' => $user_two->id,
            ]
        );

        $template = "
            This is whatever list of users are:
            {{#users}}
                + {{fullname}} details:
                    + {{firstname}}
                    + {{lastname}}
                    + {{email}}
            {{/users}}
        ";

        $expected_template = "
            This is whatever list of users are:
                + {$user_one->fullname} details:
                    + {$user_one->firstname}
                    + {$user_one->lastname}
                    + {$user_one->email}
                + {$user_two->fullname} details:
                    + {$user_two->firstname}
                    + {$user_two->lastname}
                    + {$user_two->email}
        ";

        $admin = get_admin();
        self::assertEquals($expected_template, $engine->render_for_user($template, $admin->id));

        // Invalid syntax of mustache.
        // Note that we cannot control the default value for invalid variable in mustache template,
        // hence it will fall to whatever the mustache engine is doing. However, this is quite
        // a rare case, because ideally what the notification would do is to go thru the square bracket engine
        // first and convert it to the mustache template then convert into a proper item.
        $actual = "
            This is whatever list of users are:
                +  details:
                    + 
                    + 
                    + 
                +  details:
                    + 
                    + 
                    + 
        ";

        // We only assert without the spaces here to keep the test simple.
        self::assertEquals(
            preg_replace('/\s+/', '', $actual),
            preg_replace('/\s+/', '',
                $engine->render_for_user(
                    "
                        This is whatever list of users are:
                        {{#users}}
                            + {{users.fullname}} details:
                                + {{users.firstname}}
                                + {{users.lastname}}
                                + {{users.email}}
                        {{/users}}
                    ",
                    $admin->id,
                )
            )
        );
    }
}