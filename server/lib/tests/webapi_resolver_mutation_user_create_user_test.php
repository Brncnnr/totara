<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */

use core\entity\user;
use core\format;
use core_phpunit\testcase;
use totara_tenant\exception\unresolved_tenant_reference;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_user\exception\create_user_exception;

/**
 * @group core_user
 */
class core_webapi_resolver_mutation_user_create_user_testcase extends testcase {
    use webapi_phpunit_helper;

    private const MUTATION = 'core_user_create_user';

    /**
     * @return void
     */
    public function test_create_user_with_success(): void {
        global $CFG;

        $CFG->allowuserthemes = 1;
        $this->setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');

        $text = $generator->create_custom_profile_field(['datatype' => 'text']);
        $checkbox = $generator->create_custom_profile_field(['datatype' => 'checkbox', 'defaultdata' => 0]);
        $date = $generator->create_custom_profile_field(['datatype' => 'date']);
        $menu = $generator->create_custom_profile_field(['datatype' => 'menu', 'param1' => ['xx', 'yy', 'zz']]);
        $textarea = $generator->create_custom_profile_field(['datatype' => 'textarea']);
        $datetime = $generator->create_custom_profile_field(['datatype' => 'datetime']);

        $event_sink = self::redirectEvents();
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'idnumber' => '09ioo',
                    'firstnamephonetic' => 'firstname',
                    'lastnamephonetic' => 'lastname',
                    'middlename' => 'Maria',
                    'alternatename' => 'Sara Kerrigan',
                    'city' => 'Kyiv',
                    'description' => 'Completely normal user',
                    'descriptionformat' => format::FORMAT_HTML,
                    'url' => 'http://www.example.com',
                    'skype' => 'derp',
                    'institution' => 'strange land',
                    'department' => 'video game/movie',
                    'address' => 'Didn\'t I mention silent hill already?',
                    'phone1' => '123',
                    'phone2' => '234',
                    'country' => 'NZ',
                    'timezone' => 'Australia/Perth',
                    'lang' => 'en',
                    'theme' => 'ventura',
                    'auth' => 'manual',
                    'calendartype' => 'gregorian',
                    'emailstop' => true,
                    'suspended' => true,
                    'custom_fields' => [
                        [
                            'shortname' => $textarea->shortname,
                            'data' => 'textarea',
                            'data_format' => 1
                        ],
                        [
                            'shortname' => $checkbox->shortname,
                            'data' => "0"
                        ],
                        [
                            'shortname' => $date->shortname,
                            'data' => '2014-1-21'
                        ],
                        [
                            'shortname' => $menu->shortname,
                            'data' => 'xx'
                        ],
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text'
                        ],
                        [
                            'shortname' => $datetime->shortname,
                            'data' => '2016-06-21'
                        ]
                    ]
                ]
            ]
        );

        $events = $event_sink->get_events();
        $event_names = array_map(function ($event){
            return $event->eventname;
        }, $events);
        self::assertFalse(in_array('\totara_core\event\user_suspended', $event_names));

        self::assertNotEmpty($result);
        self::assertEquals('user1', $result['user']->username);
        self::assertEquals('www@example.com', $result['user']->email);
        self::assertEquals('first name', $result['user']->firstname);
        self::assertEquals('last name', $result['user']->lastname);
        self::assertEquals('09ioo', $result['user']->idnumber);
        self::assertEquals('Maria', $result['user']->middlename);
        self::assertEquals('Sara Kerrigan', $result['user']->alternatename);
        self::assertEquals('Kyiv', $result['user']->city);
        self::assertEquals('Completely normal user', $result['user']->description);
        self::assertEquals(FORMAT_HTML, $result['user']->descriptionformat);
        self::assertEquals('http://www.example.com', $result['user']->url);
        self::assertEquals('derp', $result['user']->skype);
        self::assertEquals('strange land', $result['user']->institution);
        self::assertEquals('123', $result['user']->phone1);
        self::assertEquals('234', $result['user']->phone2);
        self::assertEquals('Didn\'t I mention silent hill already?', $result['user']->address);
        self::assertEquals('NZ', $result['user']->country);
        self::assertEquals('Australia/Perth', $result['user']->timezone);
        self::assertEquals('en', $result['user']->lang);
        self::assertEquals('ventura', $result['user']->theme);
        self::assertEquals('manual', $result['user']->auth);
        self::assertEquals('gregorian', $result['user']->calendartype);
        self::assertEquals(1, $result['user']->emailstop);
        self::assertEquals(1, $result['user']->suspended);

        // Let's create some user belongs to tenant
        $generator = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();


        $custom_fields = $this->resolve_graphql_type(
            'core_user',
            'custom_fields',
            $result['user']
        );

        self::assertCount(6, $custom_fields);
        $shortnames = array_map(function ($custom_field) use ($textarea) {
            if ($custom_field['shortname'] === $textarea->shortname) {
                self::assertEquals(1, $custom_field['data_format']);
            }
            return $custom_field['shortname'];
        }, $custom_fields);

        self::assertTrue(in_array($textarea->shortname, $shortnames));
        self::assertTrue(in_array($menu->shortname, $shortnames));
        self::assertTrue(in_array($date->shortname, $shortnames));
        self::assertTrue(in_array($text->shortname, $shortnames));
        self::assertTrue(in_array($checkbox->shortname, $shortnames));
        self::assertTrue(in_array($datetime->shortname, $shortnames));

        $data = array_column($custom_fields, 'data', 'shortname');
        self::assertEquals('textarea', $data[$textarea->shortname]);
        self::assertEquals('xx', $data[$menu->shortname]);
        self::assertEquals('1390305600', $data[$date->shortname]);
        self::assertEquals('text', $data[$text->shortname]);
        self::assertEquals('0', $data[$checkbox->shortname]);
        self::assertEquals('1466438400', $data[$datetime->shortname]);

        $data_types = array_map(function ($custom_field) {
            return $custom_field['data_type'];
        }, $custom_fields);

        self::assertTrue(in_array('TEXT', $data_types));
        self::assertTrue(in_array('TEXTAREA', $data_types));
        self::assertTrue(in_array('CHECKBOX', $data_types));
        self::assertTrue(in_array('DATE', $data_types));
        self::assertTrue(in_array('DATETIME', $data_types));
        self::assertTrue(in_array('MENU', $data_types));
    }

    /**
     * @return void
     */
    public function test_create_user_with_unwanted_custom_field_format(): void {
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $text = $generator->create_custom_profile_field(['datatype' => 'text']);

        self::expectErrorMessage('field1: data_format should not be passed for text fields.');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text',
                            'data_format' => 2
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_incorrect_custom_field_format(): void {
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $textarea = $generator->create_custom_profile_field(['datatype' => 'textarea']);

        self::expectErrorMessage($textarea->shortname . ': Unrecognised data_format: 99.');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $textarea->shortname,
                            'data' => 'text',
                            'data_format' => 99
                        ],
                    ]
                ]
            ]
        );
    }

    public function test_create_user_with_data_and_format_mismatch(): void {
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $textarea = $generator->create_custom_profile_field(['datatype' => 'textarea']);

        self::expectErrorMessage($textarea->shortname . ': data_format set to JSON but data does not appear to be in JSON format.');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $textarea->shortname,
                            'data' => '<p>This is HTML text <b>NOT</b> JSON</p>',
                            // Wrong format intentionally.
                            'data_format' => FORMAT_JSON_EDITOR
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_incorrect_text_field_length(): void {
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $text = $generator->create_custom_profile_field(['datatype' => 'text', 'param2' => 1]);

        self::expectErrorMessage('field1: The data must have less than 1 characters.');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text',
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * If a user tries to set the same field multiple times. Just save the last one.
     * @return void
     */
    public function test_create_user_with_multiple_same_custom_fields(): void {
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $text = $generator->create_custom_profile_field(['datatype' => 'text']);
        $menu = $generator->create_custom_profile_field(['datatype' => 'menu', 'param1' => ['xx', 'yy', 'zz']]);

        $last = 'text5';
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text1',
                        ],
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text2',
                        ],
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text3',
                        ],
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text4',
                        ],
                        [
                            'shortname' => $text->shortname,
                            'data' => $last,
                        ],
                        [
                            'shortname' => $menu->shortname,
                            'data' => 'xx',
                        ],
                    ]
                ]
            ]
        );
        $custom_fields = $this->resolve_graphql_type(
            'core_user',
            'custom_fields',
            $result['user']
        );

        self::assertCount(2, $custom_fields);

        $data = array_column($custom_fields, 'data', 'shortname');
        self::assertEquals($last, $data[$text->shortname]);
        self::assertEquals('xx', $data[$menu->shortname]);
    }

    /**
     * @return void
     */
    public function test_create_user_with_empty_date_custom_field(): void {
        self::setAdminUser();
        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $date = $generator->create_custom_profile_field(['datatype' => 'date']);

        self::expectErrorMessage('field1: You must provide date data in the format YYYY-MM-DD.');
        self::expectException(create_user_exception::class);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user2',
                    'password' => 'Password124.',
                    'firstname' => 'name first',
                    'lastname' => 'name last',
                    'email' => 'www.2@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $date->shortname,
                            'data' => ""
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_empty_datetime_custom_field(): void {
        self::setAdminUser();
        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $datetime = $generator->create_custom_profile_field(['datatype' => 'datetime']);

        self::expectErrorMessage('field1: Date must be provided in format YYYY-MM-DD-HH-MM-SS or YYYY-MM-DD.');
        self::expectException(create_user_exception::class);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $datetime->shortname,
                            'data' => ""
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_empty_menu_custom_field(): void {
        self::setAdminUser();
        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $menu = $generator->create_custom_profile_field(['datatype' => 'menu', 'defaultdata' => 'xx', 'param1' => ['xx', 'yy', 'zz']]);

        self::expectErrorMessage('field1: You must pass a valid menu option.');
        self::expectException(create_user_exception::class);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $menu->shortname,
                            'data' => ""
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_empty_checkbox_custom_field(): void {
        self::setAdminUser();
        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $checkbox = $generator->create_custom_profile_field(['datatype' => 'checkbox', 'defaultdata' => 0, 'shortname' => 'checkbox']);

        self::expectErrorMessage('checkbox: You must pass 0 or 1 for data for checkbox fields.');
        self::expectException(create_user_exception::class);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $checkbox->shortname,
                            'data' => ""
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_empty_custom_field(): void {
        self::setAdminUser();
        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');

        $text = $generator->create_custom_profile_field(['datatype' => 'text', 'defaultdata' => 'text', 'shortname' => 'text']);
        $textarea = $generator->create_custom_profile_field(['datatype' => 'textarea', 'defaultdata' => 'textarea', 'shortname' => 'textarea']);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user2',
                    'password' => 'Password123.',
                    'firstname' => 'name first',
                    'lastname' => 'name last',
                    'email' => 'www.2@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $textarea->shortname,
                            'data' => '',
                            'data_format' => 1
                        ],
                        [
                            'shortname' => $text->shortname,
                            'data' => ''
                        ],
                    ]
                ]
            ]
        );

        self::assertNotEmpty($result);

        $custom_fields = $this->resolve_graphql_type(
            'core_user',
            'custom_fields',
            $result['user']
        );

        $data = array_column($custom_fields, 'data', 'shortname');
        self::assertEquals('', $data[$text->shortname]);
        self::assertEquals('', $data[$textarea->shortname]);
    }

    /**
     * @return void
     */
    public function test_create_user_with_checkbox_field_with_exception(): void {
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $checkbox = $generator->create_custom_profile_field(['datatype' => 'checkbox', 'defaultdata' => 0, 'shortname' => 'checkbox']);

        // Test a range of bad string values (other types will be blocked by schema type checks).
        $bad_values = ["222", "1.9", "1.1", "1E3", "abc", " ", "", "-1"];

        foreach ($bad_values as $bad_value) {
            try {
                $this->resolve_graphql_mutation(
                    self::MUTATION,
                    [
                        'input' => [
                            'username' => 'user1',
                            'password' => 'Password123.',
                            'firstname' => 'first name',
                            'lastname' => 'last name',
                            'email' => 'www@example.com',
                            'custom_fields' => [
                                [
                                    'shortname' => $checkbox->shortname,
                                    'data' => $bad_value,
                                ],
                            ]
                        ]
                    ]
                );
                $this->fail('Exception expected for invalid checkbox string format but none thrown.');
            } catch (create_user_exception $exception) {
                $this->assertEquals('checkbox: You must pass 0 or 1 for data for checkbox fields.', $exception->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    public function test_create_user_with_date_field_with_wrong_format(): void {
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $date = $generator->create_custom_profile_field(['datatype' => 'date']);
        self::expectErrorMessage('Date format should be YYYY-MM-DD.');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'kukaracha',
                    'email' => 'www.2@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $date->shortname,
                            'data' => '22022-112-2',
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_without_passing_correct_tenant(): void {
        self::setAdminUser();
        $generator = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        self::expectExceptionMessage('Tenant reference must identify exactly one tenant.');
        self::expectException(unresolved_tenant_reference::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'kukaracha',
                    'email' => 'www.2@example.com',
                    'tenant' => ['id' => 98]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_datetime_field_with_wrong_format(): void {
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $date = $generator->create_custom_profile_field(['datatype' => 'datetime']);
        // This one has include time = Yes
        $datetime = $generator->create_custom_profile_field(['datatype' => 'datetime', 'param3' => 1]);

        // Some badly formatted dates to test. Non-string inputs will be caught by GraphQL schema validation.
        $bad_dates = ['22022-112-2', '', '2000-13-01', '2000-02-32', '2005-02-29', ' ', '--', 'abc'];
        foreach ($bad_dates as $bad_date) {
            try {
                $this->resolve_graphql_mutation(
                    self::MUTATION,
                    [
                        'input' => [
                            'username' => 'user1',
                            'password' => 'Password123.',
                            'firstname' => 'first name',
                            'lastname' => 'last name',
                            'email' => 'www@example.com',
                            'custom_fields' => [
                                [
                                    'shortname' => $date->shortname,
                                    'data' => $bad_date,
                                ],
                            ]
                        ]
                    ]
                );
                self::fail('Expected exception due to bad data format but no exception thrown.');
            } catch (create_user_exception $exception) {
                // Test error generically as there are a range of error strings for different situations.
                self::assertStringContainsStringIgnoringCase($date->shortname, $exception->getMessage());
                self::assertStringContainsStringIgnoringCase('date', $exception->getMessage());
            }
        }

        $bad_datetimes = ['2020-13-01-00-00-00', '2000-01-32-00-00-00', '2000-01-01-24-00-00',
            '2000-01-01-01-60-00', '2000-01-01-01-01-60', '2005-02-29-01-01-01', '2022-01-01-00',
            ' ', '--', 'abc'];
        foreach ($bad_datetimes as $bad_datetime) {
            try {
                $this->resolve_graphql_mutation(
                    self::MUTATION,
                    [
                        'input' => [
                            'username' => 'user1',
                            'password' => 'Password123.',
                            'firstname' => 'first name',
                            'lastname' => 'last name',
                            'email' => 'www@example.com',
                            'custom_fields' => [
                                [
                                    'shortname' => $datetime->shortname,
                                    'data' => $bad_datetime,
                                ],
                            ]
                        ]
                    ]
                );
                self::fail('Expected exception due to bad data format but no exception thrown.');
            } catch (create_user_exception $exception) {
                // Test error generically as there are a range of error strings for different situations.
                self::assertStringContainsStringIgnoringCase($datetime->shortname, $exception->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    public function test_create_user_with_menu_field_with_exception(): void {
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');
        $menu = $generator->create_custom_profile_field(['datatype' => 'menu', 'param1' => ['xx', 'yy', 'zz']]);

        self::expectErrorMessage('field1: You must pass a valid menu option.');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'custom_fields' => [
                        [
                            'shortname' => $menu->shortname,
                            'data' => '222',
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_without_passing_required_params(): void {
        self::setAdminUser();

        self::expectExceptionMessage("Required parameter - email not being passed");
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_valid_cap(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        $role_id = $generator->create_role();
        assign_capability('moodle/user:create', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $user->id, context_system::instance());

        self::setUser($user);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals('user1', $result['user']->username);
        self::assertEquals('www@example.com', $result['user']->email);
        self::assertEquals('first name', $result['user']->firstname);
        self::assertEquals('last name', $result['user']->lastname);
    }

    /**
     * @return void
     */
    public function test_create_user_by_authenticate_user(): void {
        $user = self::getDataGenerator()->create_user();

        self::setUser($user);

        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_by_tenant_user(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        // Make the External API act as if this is the API user, belonging to $tenant1
        $tenant1 = $tenant_generator->create_tenant();
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        assign_capability('totara/tenant:usercreate', CAP_ALLOW, $role->id, context_tenant::instance($tenant1->id));
        role_assign($role->id, $user->id, context_tenant::instance($tenant1->id));

        $tenant2 = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user->id, $tenant1->id);
        $user->tenantid = $tenant1->id;
        self::setUser($user);

        // Make a create user request containing the same tenantID for the user as the client tenant_id
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'tenant' => ['id' => $tenant1->id]
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals('user1', $result['user']->username);
        self::assertEquals($tenant1->id, $result['user']->tenantid);

        // With tenantisolation off tenant api user cannot create system user with no system capabilities
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'input' => [
                        'username' => 'angeluser',
                        'password' => '123Password.',
                        'firstname' => 'Ann',
                        'lastname' => 'Frank',
                        'email' => 'wwwuser@example.com',
                    ]
                ]
            );
            $this->fail('create_user_exception expected');
        } catch (create_user_exception $e) {
            $this->assertStringContainsString('You do not have capabilities to create a user', $e->getMessage());
        }

        assign_capability('moodle/user:create', CAP_ALLOW, $role->id, context_system::instance());
        role_assign($role->id, $user->id, context_system::instance());

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'angeluser',
                    'password' => '123Password.',
                    'firstname' => 'Ann',
                    'lastname' => 'Frank',
                    'email' => 'wwwuser@example.com',
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals('angeluser', $result['user']->username);
        self::assertEquals(null, $result['user']->tenantid);

        // User cannot create user for different tenant
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'input' => [
                        'username' => 'angeluser1',
                        'password' => '123Password.',
                        'firstname' => 'Ann',
                        'lastname' => 'Frank',
                        'email' => 'wwwuser1@example.com',
                        'tenant' => ['id' => $tenant2->id]
                    ]
                ]
            );
            $this->fail('create_user_exception expected');
        } catch (create_user_exception $e) {
            $this->assertStringContainsString('You do not have capabilities to create a user', $e->getMessage());
        }

        // If tenantisolated on you cannot create system user despite system caps
        set_config('tenantsisolated', 1);
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'input' => [
                        'username' => 'angeluser',
                        'password' => '123Password.',
                        'firstname' => 'Ann',
                        'lastname' => 'Frank',
                        'email' => 'wwwuser@example.com',
                    ]
                ]
            );
            $this->fail('create_user_exception expected');
        } catch (create_user_exception $e) {
            $this->assertStringContainsString('You do not have capabilities to create a user', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_create_user_username_blank(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Username can not be blank');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => '  ',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_lastname_blank(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Lastname can not be blank');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'usename',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => '  ',
                    'email' => 'www@example.com',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_username_not_unique(): void {
        self::setAdminUser();

        $name = 'username';
        self::getDataGenerator()->create_user(['username' => $name]);

        self::expectExceptionMessage('Username already exists: '.$name);
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => $name,
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_idnumber_not_unique(): void {
        self::setAdminUser();

        $idnumber = 'idnumber';
        self::getDataGenerator()->create_user(['idnumber' => $idnumber]);

        self::expectExceptionMessage('Idnumber already exists: '.$idnumber);
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'firstname',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'idnumber' => $idnumber,
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_lowercase(): void {
        self::setAdminUser();

        self::expectExceptionMessage('The username must be in lower case');
        self::expectException(moodle_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'USERNAME',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_invalid_email(): void {
        self::setAdminUser();

        $email = 'www.com';
        self::expectExceptionMessage('Invalid email format: '. $email);
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'username',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => $email,
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_invalid_url(): void {
        self::setAdminUser();

        $url = 'ww.com';
        self::expectExceptionMessage('Invalid url format: '. $url);
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'username',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'url' => $url
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_invalid_descriptionformat(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Unsupported descriptionformat');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'username',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'description' => 'Completely normal user',
                    'descriptionformat' => FORMAT_WIKI,
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_long_field(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Field 89765-8976-0987-324-98 should be less than 20 characters');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'username',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'phone1' => '89765-8976-0987-324-98',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_unique_email(): void {
        self::setAdminUser();

        $email = 'www@example.com';
        self::getDataGenerator()->create_user(['email' => $email]);
        self::expectExceptionMessage('Email address already exists: '. $email);
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'USERNAME',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => $email,
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_same_email(): void {
        global $CFG;
        $CFG->allowaccountssameemail = 1;

        self::setAdminUser();
        $email = 'www@example.com';

        self::getDataGenerator()->create_user(['email' => $email]);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'username',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => $email,
                ]
            ]
        );

        self::assertEquals($email, $result['user']->email);
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_default_city(): void {
        global $CFG;
        $defaultcity = $CFG->defaultcity = "Adelaida";

        self::setAdminUser();
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'username',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'somestramge@email.com',
                ]
            ]
        );

        self::assertEquals($defaultcity, $result['user']->city);
    }

    /**
     * @return void
     */
    public function test_create_user_with_invalid_password(): void {
        global $CFG;
        self::setAdminUser();
        $CFG->minpasswordlength = 12;
        $CFG->minpasswordlower = 10;
        $CFG->maxconsecutiveidentchars = 2;

        self::expectExceptionMessage('Passwords must be at least 12 characters long.'.
            ' Passwords must have at least 1 digit(s). Passwords must have at least '.
            '10 lower case letter(s). Passwords must have at least 1 upper case letter(s).'.
            ' Passwords must have at most 2 consecutive identical characters.');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'usernmae',
                    'password' => 'passs.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_empty_password(): void {
        global $CFG;
        self::setAdminUser();

        self::assertNotEmpty($CFG->passwordpolicy);

        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'input' => [
                        'username' => 'usernmae',
                        'password' => '',
                        'firstname' => 'first name',
                        'lastname' => 'last name',
                        'email' => 'www@example.com',
                    ]
                ]
            );
            self::fail('Expected exception not thrown.');
        } catch (create_user_exception $e) {
            self::assertStringContainsString('Password cannot be blank', $e->getMessage());
        }

        // Also try with password policy disabled.
        $CFG->passwordpolicy = false;
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'input' => [
                        'username' => 'usernmae',
                        'password' => '',
                        'firstname' => 'first name',
                        'lastname' => 'last name',
                        'email' => 'www@example.com',
                    ]
                ]
            );
            self::fail('Expected exception not thrown.');
        } catch (create_user_exception $e) {
            self::assertStringContainsString('Password cannot be blank', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_create_user_with_invalid_email(): void {
        global $CFG;
        $CFG->denyemailaddresses = '.www@example.com';

        self::expectExceptionMessage('Email addresses in these domains are not allowed');
        self::expectException(create_user_exception::class);
        self::setAdminUser();
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'username',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => '.www@example.com',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_without_allow_set_theme(): void {
        self::setAdminUser();

        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'country' => 'NZ',
                    'timezone' => 'Australia/Perth',
                    'lang' => 'en',
                    'theme' => 'ventura',
                    'auth' => 'manual'
                ]
            ]
        );
        self::assertDebuggingCalled('User not allow to set theme');
    }

    /**
     * @return void
     */
    public function test_create_user_with_default_value(): void {
        global $CFG;
        $CFG->timezone = 'Australia/Perth';
        $CFG->lang = 'en';
        $CFG->calendartype = 'gregorian';
        $CFG->country = 'NZ';

        self::setAdminUser();

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                ]
            ]
        );

        self::assertEquals($CFG->country, $result['user']->country);
        self::assertEquals($CFG->timezone, $result['user']->timezone);
        self::assertEquals($CFG->lang, $result['user']->lang);
        self::assertEquals('', $result['user']->theme);
        self::assertEquals('manual', $result['user']->auth);
        self::assertEquals($CFG->calendartype, $result['user']->calendartype);
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_default_calendartype(): void {
        self::setAdminUser();

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                ]
            ]
        );

        self::assertEquals('gregorian', $result['user']->calendartype);
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_force_timezone(): void {
        global $CFG;
        $CFG->forcetimezone = 'Australia/Perth';
        self::setAdminUser();

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                ]
            ]
        );

        self::assertEquals($CFG->forcetimezone, $result['user']->timezone);
        self::assertDebuggingCalled('Your input timezone has been overrided as forcetimezone is enabled');
    }

    /**
     * @return void
     */
    public function test_create_user_username_with_auth_disabled(): void {
        self::setAdminUser();

        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'auth' => 'approved'
                ]
            ]
        );

        self::assertDebuggingCalled('Auth plugin - approved is not being enabled');
    }

    /**
     * @return void
     */
    public function test_create_user_with_incorrect_lang_input(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Language does not exist: lang');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'lang' => 'lang'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_incorrect_auth_input(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Auth plugin does not exist: auth');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'auth' => 'auth'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_incorrect_theme_input(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Theme does not exist: theme');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'theme' => 'theme'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_incorrect_calendartype_input(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Calendartype does not exist: calendartype');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'calendartype' => 'calendartype'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_incorrect_emailstops_input(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Emailstop is not boolean type');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'emailstop' => 0
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_incorrect_timezone_input(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Timezone does not exist: timezone');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'timezone' => 'timezone'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_incorrect_country_input(): void {
        self::setAdminUser();

        self::expectExceptionMessage('Country does not exist: country');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'country' => 'country'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_suspend_tenant(): void {
        self::setAdminUser();

        $generator = self::getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant(['suspended' => 1]);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'tenant' => [
                        'id' => $tenant->id,
                        'idnumber' => $tenant->idnumber
                    ]
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals('user1', $result['user']->username);
        self::assertEquals('www@example.com', $result['user']->email);
        self::assertEquals($tenant->id, $result['user']->tenantid);
    }

    /**
     * @return void
     */
    public function test_create_user_with_idnumber(): void {
        self::setAdminUser();

        // Existing user with empty idnumber.
        $user = self::getDataGenerator()->create_user(['idnumber' => '']);
        self::assertEmpty($user->idnumber);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example.com',
                    'idnumber' => ''
                ]
            ]
        );

        self::assertEquals('', $result['user']->idnumber);
        self::assertEquals('user1', $result['user']->username);

        // Creating a user with an idnumber that is not empty.
        $user = self::getDataGenerator()->create_user(['idnumber' => 'idnumber']);
        self::assertEquals('idnumber', $user->idnumber);

        self::expectErrorMessage('Idnumber already exists: idnumber');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user2',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example1.com',
                    'idnumber' => 'idnumber'
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_user_with_generate_password(): void {
        self::setAdminUser();

        // User with generate_password and password in the same time.
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'input' => [
                        'username' => 'user1',
                        'password' => 'Password123.',
                        'firstname' => 'first name',
                        'lastname' => 'last name',
                        'email' => 'www@example.com',
                        'generate_password' => true
                    ]
                ]
            );
            $this->fail('create_user_exception expected');
        } catch (create_user_exception $e) {
            $this->assertStringContainsString('You cannot set password and generate password at the same time', $e->getMessage());
        }

        // User with generate_password = false.
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'input' => [
                        'username' => 'user1',
                        'firstname' => 'first name',
                        'lastname' => 'last name',
                        'email' => 'www@example.com',
                        'generate_password' => false
                    ]
                ]
            );
            $this->fail('create_user_exception expected');
        } catch (create_user_exception $e) {
            $this->assertStringContainsString('Required parameter - password not being passed', $e->getMessage());
        }

        // Existing user with generate_password
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user2',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example1.com',
                    'generate_password' => true
                ]
            ]
        );
        $user = new user($result['user']->id);
        self::assertNotEmpty($user->password);
        self::assertEquals(1, get_user_preferences('auth_forcepasswordchange', null, $result['user']->id));
    }

    /**
     * @return void
     */
    public function test_create_user_with_force_password_change(): void {
        self::setAdminUser();

        // User with force_password_change = true with no password.
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'input' => [
                        'username' => 'user1',
                        'firstname' => 'first name',
                        'lastname' => 'last name',
                        'email' => 'www@example.com',
                        'force_password_change' => false
                    ]
                ]
            );
            $this->fail('create_user_exception expected');
        } catch (create_user_exception $e) {
            $this->assertStringContainsString('Required parameter - password not being passed', $e->getMessage());
        }

        // Existing user with force_password_change
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user2',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'password' => 'Parrword123!',
                    'email' => 'www@example1.com',
                    'force_password_change' => true
                ]
            ]
        );
        $user = new user($result['user']->id);
        self::assertNotEmpty($user->password);
        self::assertEquals(1, get_user_preferences('auth_forcepasswordchange', null, $result['user']->id));
    }

    /**
     * @return void
     */
    public function test_can_not_create_custom_fields_without_capability(): void {
        $gen = self::getDataGenerator();
        $user = $gen->create_user();
        /** @var \totara_core\testing\generator $generator */
        $generator = $gen->get_plugin_generator('totara_core');

        $text = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_PRIVATE]);
        self::assertEquals(PROFILE_VISIBLE_PRIVATE, $text->visible);

        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $user->id, context_system::instance());

        self::setUser($user);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example1.com',
                    'custom_fields' => [
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text',
                        ],
                    ]
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals('user1', $result['user']->username);
        $custom_fields = $this->resolve_graphql_type(
            'core_user',
            'custom_fields',
            $result['user']
        );
        $custom_field = reset($custom_fields);
        self::assertEquals($text->shortname, $custom_field['shortname']);
        self::assertEquals('text', $custom_field['data']);
        self::assertEquals(0, $custom_field['data_format']);

        // Unassign the capability.
        $context = context_user::instance($user->id);
        assign_capability('moodle/user:viewalldetails', CAP_PREVENT, $role->id, $context);

        self::expectExceptionMessage('The custom field does not exist.');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user2',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example2.com',
                    'custom_fields' => [
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text',
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_create_custom_fields_with_delete_field(): void {
        $gen = self::getDataGenerator();

        self::setAdminUser();
        /** @var \totara_core\testing\generator $generator */
        $generator = $gen->get_plugin_generator('totara_core');

        $text = $generator->create_custom_profile_field(['datatype' => 'text']);

        // delete is false, api user can create user.
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example1.com',
                    'custom_fields' => [
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text',
                            'delete' => false
                        ],
                    ]
                ]
            ]
        );

        self::assertNotEmpty($result);

        self::expectExceptionMessage('Can not delete custom field on creating.');
        self::expectException(create_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user2',
                    'password' => 'Password123.',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => 'www@example2.com',
                    'custom_fields' => [
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text',
                            'delete' => true
                        ],
                    ]
                ]
            ]
        );
    }
}