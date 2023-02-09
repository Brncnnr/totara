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

use core\format;
use core\orm\query\builder;
use core_phpunit\testcase;
use totara_tenant\exception\unresolved_tenant_reference;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core_user\exception\update_user_exception;

/**
 * @group core_user
 */
class core_webapi_resolver_mutation_user_update_user_testcase extends testcase {
    use webapi_phpunit_helper;

    private const MUTATION = 'core_user_update_user';

    /**
     * @return void
     */
    public function test_update_user_with_success(): void {
        global $CFG;

        $CFG->allowuserthemes = 1;
        self::setAdminUser();

        /** @var \totara_core\testing\generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_core');

        $text = $generator->create_custom_profile_field(['datatype' => 'text']);
        $checkbox = $generator->create_custom_profile_field(['datatype' => 'checkbox', 'defaultdata' => 0]);
        $date = $generator->create_custom_profile_field(['datatype' => 'date']);
        $menu = $generator->create_custom_profile_field(['datatype' => 'menu', 'param1' => ['xx', 'yy', 'zz']]);
        $textarea = $generator->create_custom_profile_field(['datatype' => 'textarea']);
        $datetime = $generator->create_custom_profile_field(['datatype' => 'datetime']);

        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com',
                'description' => 'description',
                'descriptionformat' => FORMAT_PLAIN,
                'city' => 'Sydney',
                'country' => 'AU',
                'institution' => "Psyc",
                'department' => "Ward",
            ]
        );
        self::assertEquals(0, $user->suspended);
        $this->set_profile_field_value($user, $text, 'text');
        $this->set_profile_field_value($user, $textarea, 'textarea', FORMAT_HTML);
        $this->set_profile_field_value($user, $checkbox, 0);
        $this->set_profile_field_value($user, $date, time());
        $this->set_profile_field_value($user, $menu, 'xx');
        $this->set_profile_field_value($user, $datetime, time());

        $generator = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();

        $event_sink = self::redirectEvents();
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname
                ],
                'input' => [
                    'username' => 'phpstorm',
                    'firstname' => 'Tom',
                    'lastname' => 'Mike',
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
                    'tenant' => ['idnumber' => $tenant1->idnumber, 'id' => $tenant1->id],
                    'custom_fields' => [
                        [
                            'shortname' => $textarea->shortname,
                            'data' => 'textarea updated',
                            'data_format' => 1
                        ],
                        [
                            'shortname' => $checkbox->shortname,
                            'data' => "1"
                        ],
                        [
                            'shortname' => $date->shortname,
                            'data' => '2014-1-21'
                        ],
                        [
                            'shortname' => $menu->shortname,
                            'data' => 'yy'
                        ],
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text updated'
                        ],
                        [
                            'shortname' => $datetime->shortname,
                            'data' => '2014-06-22'
                        ],
                    ]
                ]
            ]
        );

        self::assertEquals(5, $event_sink->count());
        $events = $event_sink->get_events();

        $event_names = array_map(function ($event){
            return $event->eventname;
        }, $events);

        self::assertTrue(in_array('\core\event\user_updated', $event_names));
        self::assertTrue(in_array('\totara_core\event\user_suspended', $event_names));
        self::assertTrue(in_array('\core\event\user_tenant_membership_changed', $event_names));
        self::assertTrue(in_array('\core\event\cohort_member_added', $event_names));

        self::assertNotEmpty($result);
        self::assertEquals('phpstorm', $result['user']->username);
        self::assertEquals('Tom', $result['user']->firstname);
        self::assertEquals('Mike', $result['user']->lastname);
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
        self::assertEquals($tenant1->id, $result['user']->tenantid);

        $custom_fields = $this->resolve_graphql_type(
            'core_user',
            'custom_fields',
            $result['user']
        );

        self::assertCount(6, $custom_fields);

        $data = array_column($custom_fields, 'data', 'shortname');
        self::assertEquals('textarea updated', $data[$textarea->shortname]);
        self::assertEquals('1390305600', $data[$date->shortname]);
        self::assertEquals('1', $data[$checkbox->shortname]);
        self::assertEquals('text updated', $data[$text->shortname]);
        self::assertEquals('yy', $data[$menu->shortname]);
        self::assertEquals('1403366400', $data[$datetime->shortname]);
    }

    /**
     * @param stdClass $user
     * @param stdClass $field
     * @param string $data
     * @param int $dataformat
     *
     * @return int
     */
    private function set_profile_field_value(stdClass $user, stdClass $field, string $data, int $dataformat = 0): int {
        global $DB;

        $record = new stdClass();
        $record->fieldid = $field->id;
        $record->userid = $user->id;
        $record->data = $data;
        $record->dataformat = $dataformat;

        return $DB->insert_record('user_info_data', $record);
    }

    /**
     * @return void
     */
    public function test_update_suspended_user(): void {
        $apiuser = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'email' => 'www@example.com']);
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $apiuser->id, context_system::instance());

        // Login as api user
        self::setUser($apiuser);

        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'suspended' => 1,
                'password' => 'Sjewnwicnqn.',
            ]
        );
        self::assertEquals(1, $user->suspended);

        $event_sink = self::redirectEvents();
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                ],
                'input' => [
                    'suspended' => false
                ]
            ]
        );

        self::assertEquals(2, $event_sink->count());

        // user_updated event triggered by user_update_user() and user_unsuspend_user().
        foreach ($event_sink->get_events() as $event) {
            self::assertEquals('\core\event\user_updated', $event->eventname);
        }
        self::assertEquals(0, $result['user']->suspended);

        // Not updating suspended, it keep original value.
        $user = self::getDataGenerator()->create_user(
            [
                'suspended' => 1,
                'password' => 'Sjewnwicnqn.',
            ]
        );

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                ],
                'input' => [
                    'username' => 'aaaaaa'
                ]
            ]
        );
        self::assertEquals(1, $result['user']->suspended);

        // Cannot suspend admin user
        $adminuser = get_admin();

        self::expectExceptionMessage("The admin user cannot be suspended");
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $adminuser->id,
                ],
                'input' => [
                    'suspended' => true
                ]
            ]
        );


    }

    /**
     * @return void
     */
    public function test_update_user_without_passing_required_params(): void {
        self::setAdminUser();

        self::expectExceptionMessage("Required parameter 'target_user' not being passed");
        self::expectException(coding_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'input' => [
                    'username' => 'user1',
                    'firstname' => 'first name',
                ],
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_with_tenant_not_found(): void {
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = self::getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        self::setAdminUser();

        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com'
            ]
        );

        self::expectExceptionMessage("Tenant reference must identify exactly one tenant.");
        self::expectException(unresolved_tenant_reference::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                ],
                'input' => [
                    'username' => 'phpstorm',
                    'firstname' => 'Tom',
                    'lastname' => 'Mike',
                    'tenant' => ['idnumber' => 'idnumber']
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_with_valid_cap(): void {
        $gen = self::getDataGenerator();
        $user = $gen->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com'
            ]
        );

        $role_id = $gen->create_role();
        assign_capability('moodle/user:update', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $user->id, context_system::instance());

        $target_user = $gen->create_user(['password' => 'Sjewnwicnqn.']);
        self::setUser($user);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                    'username' => $target_user->username,
                    'firstname' => $target_user->firstname,
                ],
                'input' => [
                    'username' => 'phpstorm',
                    'firstname' => 'Tom',
                    'lastname' => 'Mike',
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals('phpstorm', $result['user']->username);
        self::assertEquals('Tom', $result['user']->firstname);
        self::assertEquals('Mike', $result['user']->lastname);
    }

    /**
     * @return void
     */
    public function test_update_user_by_authenticate_user(): void {
        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com'
            ]
        );

        self::setUser($user);

        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname
                ],
                'input' => []
            ]
        );
    }

    /**
     * @return void
     */
    public function test_updated_user_with_invalid_email(): void {
        global $CFG;
        $CFG->denyemailaddresses = '.www@example.com';

        self::setAdminUser();
        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com'
            ]
        );

        self::expectExceptionMessage('Email addresses in these domains are not allowed');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname
                ],
                'input' => [
                    'username' => 'lilei',
                    'firstname' => 'first name',
                    'lastname' => 'last name',
                    'email' => $CFG->denyemailaddresses,
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_with_blank_email(): void {
        self::setAdminUser();
        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com'
            ]
        );

        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $user->id,
                    ],
                    'input' => [
                        'username' => 'usename',
                        'firstname' => 'first name',
                        'lastname' => 'last name',
                        'email' => '',
                    ]
                ]
            );
            self::fail('Expected exception not thrown.');
        } catch (update_user_exception $e) {
            self::assertStringContainsString('Email can not be blank', $e->getMessage());
        }

        // Also test null.
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $user->id,
                    ],
                    'input' => [
                        'username' => 'useaname',
                        'firstname' => 'first aname',
                        'lastname' => 'last aname',
                        'email' => null,
                    ]
                ]
            );
            self::fail('Expected exception not thrown.');
        } catch (update_user_exception $e) {
            self::assertStringContainsString('Email can not be blank', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_update_user_with_record_not_found(): void {
        self::setAdminUser();

        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com'
            ]
        );

        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => 'test',
                    'firstname' => 'fname',
                    'lastname' => $user->lastname
                ],
                'input' => []
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_without_passing_param(): void {
        self::setAdminUser();

        self::expectExceptionMessage('There was a problem finding a single user record match or you do not have sufficient capabilities.');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [],
                'input' => []
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_lastname_blank(): void {
        self::setAdminUser();
        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com'
            ]
        );

        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $user->id,
                    ],
                    'input' => [
                        'username' => 'usename',
                        'firstname' => 'first name',
                        'lastname' => '  ',
                        'email' => 'www@example.com',
                    ]
                ]
            );
            self::fail('Expected exception not thrown.');
        } catch (update_user_exception $e) {
            self::assertStringContainsString('Lastname can not be blank', $e->getMessage());
        }

        // But 0 is a valid lastname.
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $user->id,
                    ],
                    'input' => [
                        'username' => 'usename',
                        'firstname' => 'first name',
                        'lastname' => '0',
                        'email' => 'www@example.com',
                    ]
                ]
            );
            // We DO NOT expect an exception here.
        } catch (Exception $e) {
            self::fail('Did not expect an exception when setting lastname to "0"');
        }
    }

    /**
     * @return void
     */
    public function test_update_user_lastname_null(): void {
        self::setAdminUser();
        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com'
            ]
        );

        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $user->id,
                    ],
                    'input' => [
                        'username' => 'usename',
                        'firstname' => 'first name',
                        'lastname' => null,
                        'email' => 'www@example.com',
                    ]
                ]
            );
            self::fail('Expected exception not thrown.');
        } catch (update_user_exception $e) {
            self::assertStringContainsString('Lastname can not be blank', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_update_user_not_found_by_same_email(): void {
        global $CFG;
        $CFG->allowaccountssameemail = 1;

        self::setAdminUser();
        $user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'email' => 'www@example.com']);
        self::getDataGenerator()->create_user(['email' => 'www@example.com', 'password' => 'Sjewnwicnqn.',]);

        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'email' => $user->email
                ],
                'input' => [
                    'username' => 'usename',
                    'firstname' => 'first name',
                    'email' => 'www@example.com',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_with_editprofile_cap(): void {
        $user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'email' => 'www@example.com']);
        $user2 = self::getDataGenerator()->create_user();
        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:editprofile', CAP_ALLOW, $role_id, context_user::instance($user2->id));
        role_assign($role_id, $user->id, context_system::instance());

        self::setUser($user);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user2->id,
                ],
                'input' => [
                    'city' => 'Wellington',
                ]
            ]
        );
        self::assertNotEmpty($result);
        self::assertEquals('Wellington', $result['user']->city);

        // This capability not enough to change username
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user2->id,
                ],
                'input' => [
                    'username' => 'phpstorm',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_with_managelogin_cap(): void {
        $user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'email' => 'www@example.com']);
        $role_id = self::getDataGenerator()->create_role();
        assign_capability('moodle/user:managelogin', CAP_ALLOW, $role_id, context_user::instance($user->id));
        role_assign($role_id, $user->id, context_system::instance());

        self::setUser($user);

        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                ],
                'input' => [
                    'username' => 'phpstorm',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_by_apiuser(): void {
        $apiuser = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'email' => 'www@example.com']);
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $apiuser->id, context_system::instance());

        // Login as api user
        self::setUser($apiuser);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                    'username' => $target_user->username,
                    'firstname' => $target_user->firstname,
                ],
                'input' => [
                    'username' => 'phpstorm',
                    'firstname' => 'Tom',
                    'lastname' => 'Mike',
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals('phpstorm', $result['user']->username);
        self::assertEquals('Tom', $result['user']->firstname);
        self::assertEquals('Mike', $result['user']->lastname);
    }

    /**
     * @return void
     */
    public function test_update_user_by_apiuser_with_incorrect_context(): void {
        $apiuser = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'email' => 'www@example.com']);
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);
        $target_user2 = self::getDataGenerator()->create_user();
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $apiuser->id, context_user::instance($target_user2->id));

        // Login as api user
        self::setUser($apiuser);

        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                    'username' => $target_user->username,
                    'firstname' => $target_user->firstname,
                ],
                'input' => [
                    'username' => 'phpstorm',
                    'firstname' => 'Tom',
                    'lastname' => 'Mike',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_with_invalid_descriptionformat(): void {
        self::setAdminUser();
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);

        self::expectExceptionMessage('Unsupported descriptionformat');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                    'username' => $target_user->username,
                    'firstname' => $target_user->firstname,
                ],
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
    public function test_update_user_with_long_field(): void {
        self::setAdminUser();
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);

        self::expectExceptionMessage('Field 89765-8976-0987-324-98 should be less than 20 characters');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                    'username' => $target_user->username,
                    'firstname' => $target_user->firstname,
                ],
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
    public function test_update_password_by_admin(): void {
        self::setAdminUser();
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'password' => 'Password123.',
                ]
            ]
        );

        self::assertNotEquals('Sjewnwicnqn.', $result['user']->password);
    }

    /**
     * @return void
     */
    public function test_update_password_by_authenticated_user(): void {
        $gen = self::getDataGenerator();
        $user = $gen->create_user();

        // Login as user.
        self::setUser($user);
        $target_user = $gen->create_user(['password' => 'Sjewnwicnqn.']);

        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'password' => 'Password123.',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_password_and_suspension_by_user_with_valid_cap(): void {
        $gen = self::getDataGenerator();
        $apiuser = $gen->create_user();
        $target_user = $gen->create_user(['password' => 'Sjewnwicnqn.']);

        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $apiuser->id, context_system::instance());
        assign_capability('moodle/user:managelogin', CAP_ALLOW, $role->id, context_system::instance());

        // Login as user.
        self::setUser($apiuser);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'password' => 'Password123.',
                    'suspended' => true
                ]
            ]
        );

        self::assertNotEquals('Sjewnwicnqn.', $result['user']->password);
        self::assertEquals(true, $result['user']->suspended);
    }

    /**
     * @return void
     */
    public function test_update_password_not_for_admin(): void {
        $gen = self::getDataGenerator();
        $apiuser = $gen->create_user();
        $target_user = get_admin();

        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $apiuser->id, context_system::instance());

        // Login as user.
        self::setUser($apiuser);

        self::expectErrorMessage('You can not update password for the admin user');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'password' => 'Password123.',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_password_with_auth_plugin(): void {
        self::setAdminUser();
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'auth' => 'oauth2']);

        self::expectErrorMessage('The authentication method does not support password changes.');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'password' => 'Password123.',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_password_with_invalid_password(): void {
        self::setAdminUser();
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);

        self::expectErrorMessage(
            'Passwords must be at least 8 characters long. '
            . 'Passwords must have at least 1 digit(s). '
            . 'Passwords must have at least 1 lower case letter(s). '
            . 'Passwords must have at least 1 upper case letter(s).'
        );
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'password' => ' ',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_password_with_empty_password(): void {
        global $CFG;
        self::setAdminUser();
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);

        self::assertNotEmpty($CFG->passwordpolicy);

        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $target_user->id,
                    ],
                    'input' => [
                        'password' => '',
                    ]
                ]
            );
            self::fail('Expected exception not thrown.');
        } catch (update_user_exception $e) {
            self::assertStringContainsString('Password cannot be blank', $e->getMessage());
        }

        // Also try with password policy disabled.
        $CFG->passwordpolicy = false;
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $target_user->id,
                    ],
                    'input' => [
                        'password' => '',
                    ]
                ]
            );
            self::fail('Expected exception not thrown.');
        } catch (update_user_exception $e) {
            self::assertStringContainsString('Password cannot be blank', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_update_password_with_null_password(): void {
        global $CFG;
        self::setAdminUser();
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);

        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $target_user->id,
                    ],
                    'input' => [
                        'lastname' => 'Changed',
                        'password' => null,
                    ]
                ]
            );
            self::fail('Expected exception not thrown.');
        } catch (update_user_exception $e) {
            self::assertStringContainsString('Password cannot be blank', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_update_user_from_tenant(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user->id, $tenant1->id);
        $user->tenantid = $tenant1->id;

        self::setUser($user);
        // Update user from tenant user
        $user_for_update = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_for_update->id, $tenant1->id);
        $user_for_update->tenantid = $tenant1->id;
        $role_id = $generator->create_role();
        assign_capability('moodle/user:editprofile', CAP_ALLOW, $role_id, context_user::instance($user_for_update->id));
        role_assign($role_id, $user->id, context_user::instance($user_for_update->id));
        assign_capability('moodle/user:update', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $user->id, context_system::instance());

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user_for_update->id,
                    'username' => $user_for_update->username,
                    'email' => $user_for_update->email,
                    'idnumber' => $user_for_update->idnumber
                ],
                'input' => [
                    'firstname' => 'Tom',
                    'lastname' => 'Mike',
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals($user_for_update->id, $result['user']->id);
        self::assertEquals($tenant1->id, $result['user']->tenantid);

        // And you cannot migrate user to another tenant
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $user_for_update->id,
                        'username' => $user_for_update->username,
                        'email' => $user_for_update->email,
                        'idnumber' => $user_for_update->idnumber
                    ],
                    'input' => [
                        'tenant' => ['id' => $tenant2->id]
                    ]
                ]
            );
            $this->fail('create_user_exception expected');
        } catch (update_user_exception $e) {
            $this->assertStringContainsString('You do not have capabilities to update user tenancy membership.', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_update_system_user_by_tenant_api_user(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $system_user_to_update = $generator->create_user([
            'firstname' => uniqid('system_user_'),
            'lastname' => uniqid('system_user_')
        ]);

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user->id, $tenant1->id);
        $user->tenantid = $tenant1->id;

        self::setUser($user);
        $role_id = $generator->create_role();
        assign_capability('moodle/user:editprofile', CAP_ALLOW, $role_id, context_user::instance($system_user_to_update->id));
        role_assign($role_id, $user->id, context_user::instance($system_user_to_update->id));
        assign_capability('moodle/user:update', CAP_ALLOW, $role_id, context_system::instance());
        role_assign($role_id, $user->id, context_system::instance());

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $system_user_to_update->id,
                    'username' => $system_user_to_update->username,
                    'email' => $system_user_to_update->email,
                    'idnumber' => $system_user_to_update->idnumber
                ],
                'input' => [
                    'firstname' => 'Marticia',
                    'lastname' => 'Addams',
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals($system_user_to_update->id, $result['user']->id);
        self::assertEquals(null, $result['user']->tenantid);

        // You cannot update system user if tenantisolated is on
        set_config('tenantsisolated', 1);
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $system_user_to_update->id,
                        'username' => $system_user_to_update->username,
                        'email' => $system_user_to_update->email,
                        'idnumber' => $system_user_to_update->idnumber
                    ],
                    'input' => [
                        'firstname' => 'Wednesday',
                        'lastname' => 'Addams',
                    ]
                ]
            );
            $this->fail('create_user_exception expected');
        } catch (update_user_exception $e) {
            $this->assertStringContainsString('There was a problem finding a single user record match or you do not have sufficient capabilities.', $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function test_update_user_from_another_tenant(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user->id, $tenant1->id);
        $user->tenantid = $tenant1->id;
        $user_for_update2 = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_for_update2->id, $tenant2->id);
        $user_for_update2->tenantid = $tenant2->id;

        self::setUser($user);
        // Update user from tenant user
        $role_id = $generator->create_role();
        assign_capability('moodle/user:editprofile', CAP_ALLOW, $role_id, context_user::instance($user_for_update2->id));
        role_assign($role_id, $user->id, context_user::instance($user_for_update2->id));

        // And you cannot update user from other tenant
        self::expectExceptionMessage('There was a problem finding a single user record match or you do not have sufficient capabilities.');
        self::expectException(update_user_exception::class);
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user_for_update2->id,
                    'username' => $user_for_update2->username,
                    'email' => $user_for_update2->email,
                    'idnumber' => $user_for_update2->idnumber
                ],
                'input' => [
                    'username' => 'phpstorm',
                    'firstname' => 'Tom',
                    'lastname' => 'Mike',
                ]
            ]
        );
    }

    /**
     * Data provider for unique validation tests.
     * @return array[]
     */
    public function get_unique_validation_data(): array {
        return [
            [
                'field' => 'text',
                'duplicate_value' => 'text updated',
                'allowed_value' => 'text updated 2'
            ],
            [
                'field' => 'textarea',
                'duplicate_value' => 'textarea updated',
                'allowed_value' => 'textarea updated 2'
            ],
            [
                'field' => 'checkbox',
                'duplicate_value' => '1',
                'allowed_value' => '0'
            ],
            [
                'field' => 'menu',
                'duplicate_value' => 'yy',
                'allowed_value' => 'zz'
            ],
            [
                'field' => 'date',
                'duplicate_value' => '2014-01-06',
                'allowed_value' => '2014-01-07'
            ],
            [
                'field' => 'datetime',
                'duplicate_value' => '2014-05-12',
                'allowed_value' => '2014-05-13'
            ]
        ];
    }

    /**
     * @dataProvider get_unique_validation_data
     * @param string $field
     * @param string $duplicate_value
     * @param string $allowed_value
     * @return void
     */
    public function test_update_user_with_custom_fields_with_unique_flag(string $field, string $duplicate_value, string $allowed_value): void {
        global $CFG;

        $CFG->allowuserthemes = 1;
        self::setAdminUser();

        $generator = $this->getDataGenerator();

        /** @var \totara_core\testing\generator $generator */
        $totara_core_generator = $generator->get_plugin_generator('totara_core');

        $text = $totara_core_generator->create_custom_profile_field(['shortname' => 'text', 'datatype' => 'text', 'forceunique' => 1]);
        $checkbox = $totara_core_generator->create_custom_profile_field(['shortname' => 'checkbox', 'datatype' => 'checkbox', 'forceunique' => 1]);
        $date = $totara_core_generator->create_custom_profile_field(['shortname' => 'date', 'datatype' => 'date', 'forceunique' => 1]);
        $menu = $totara_core_generator->create_custom_profile_field(['shortname' => 'menu', 'datatype' => 'menu', 'param1' => ['xx', 'yy', 'zz'], 'forceunique' => 1]);
        $textarea = $totara_core_generator->create_custom_profile_field(['shortname' => 'textarea', 'datatype' => 'textarea', 'forceunique' => 1]);
        $datetime = $totara_core_generator->create_custom_profile_field(['shortname' => 'datetime', 'datatype' => 'datetime', 'forceunique' => 1]);

        $generator->create_user(['username' => 'one']);
        $generator->create_user(['username' => 'two']);

        $custom_field_data = [
            'shortname' => $field,
            'data' => $duplicate_value,
        ];
        if ($field == 'textarea') {
            $custom_field_data['data_format'] = 1;
        }

        // Set the value that we will attempt to duplicate on user one
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'username' => 'one'
                ],
                'input' => [
                    'custom_fields' => [
                        $custom_field_data
                    ]
                ]
            ]
        );

        // Attempt to set user two to the same value as user two for the field.
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'username' => 'two'
                    ],
                    'input' => [
                        'custom_fields' => [
                            $custom_field_data
                        ]
                    ]
                ]
            );
            self::fail('Expected exception when attempting to duplicate unique field value for ' . $field . ' field.');
        } catch (moodle_exception $e) {
            self::assertStringContainsStringIgnoringCase('This value has already been used', $e->getMessage());
        }

        // Set user two to a different value and no exception should occur:

        $custom_field_data['data'] = $allowed_value;
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'username' => 'two'
                ],
                'input' => [
                    'custom_fields' => [
                        $custom_field_data
                    ]
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_with_unique_fields(): void {
        self::setAdminUser();
        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'email' => 'www@example.com',
                'idnumber' => 'idnumber',
            ]
        );

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'idnumber' => $user->idnumber,
                    'email' => $user->email
                ],
                'input' => [
                    'username' => 'username',
                    'email' => 'www@example.com',
                    'idnumber' => 'idnumber',
                ]
            ]
        );

        self::assertNotEmpty($result);
        self::assertEquals('username', $result['user']->username);
        self::assertEquals('www@example.com', $result['user']->email);
        self::assertEquals('idnumber', $result['user']->idnumber);
    }


    /**
     * @return void
     */
    public function test_update_user_with_idnumber(): void {
        self::setAdminUser();

        // Existing user with empty idnumber.
        self::getDataGenerator()->create_user(['idnumber' => '']);
        $user = self::getDataGenerator()->create_user(['idnumber' => 'idnumber']);
        self::assertNotEmpty($user->idnumber);

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'idnumber' => $user->idnumber,
                    'email' => $user->email
                ],
                'input' => [
                    'idnumber' => '',
                ]
            ]
        );

        self::assertEquals('', $result['user']->idnumber);

        $user1 = self::getDataGenerator()->create_user(['idnumber' => 'idnumber1']);
        $user2 = self::getDataGenerator()->create_user(['idnumber' => 'idnumber2']);

        self::expectExceptionMessage('Idnumber already exists: idnumber2');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user1->id,
                    'username' => $user1->username,
                    'idnumber' => $user1->idnumber,
                    'email' => $user1->email
                ],
                'input' => [
                    'idnumber' => $user2->idnumber,
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_username_blank(): void {
        self::setAdminUser();
        $user = self::getDataGenerator()->create_user(
            [
                'username' => 'username',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'password' => 'Sjewnwicnqn.',
                'email' => 'www@example.com'
            ]
        );

        self::expectExceptionMessage('Username can not be blank');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $user->id,
                ],
                'input' => [
                    'username' => '  ',
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_api_user_cannot_suspend_themself(): void {
        $apiuser = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.', 'email' => 'www@example.com']);
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $apiuser->id, context_system::instance());

        // Login as api user
        self::setUser($apiuser);

        self::expectException(update_user_exception::class);
        self::expectExceptionMessage('A service account user is not allowed to suspend itself when making a request.');

        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $apiuser->id
                ],
                'input' => [
                    'suspended' => true
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_update_user_with_generate_password(): void {
        self::setAdminUser();
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);
        $old_password = $target_user->password;
        self::assertEquals(0, builder::get_db()->count_records('user_password_history', array('userid' => $target_user->id)));
        // User with generate_password and password in the same time.
        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $target_user->id,
                    ],
                    'input' => [
                        'password' => 'Password123.',
                        'generate_password' => true
                    ]
                ]
            );
            $this->fail('update_user_exception expected');
        } catch (update_user_exception $e) {
            $this->assertStringContainsString('You cannot set new password and generate password at the same time', $e->getMessage());
        }

        // Existing user with generate_password
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'generate_password' => true
                ]
            ]
        );

        self::assertNotEquals($old_password, $result['user']->password);
        self::assertEquals(1, get_user_preferences('auth_forcepasswordchange', null, $result['user']->id));
    }

    /**
     * @return void
     */
    public function test_update_user_with_force_password_change(): void {
        self::setAdminUser();
        $target_user = self::getDataGenerator()->create_user(['password' => 'Sjewnwicnqn.']);

        // Existing user with force_password_change
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'force_password_change' => true
                ]
            ]
        );
        self::assertEquals(1, get_user_preferences('auth_forcepasswordchange', null, $result['user']->id));

        // Existing user with force_password_change
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'password' => 'Password345-',
                    'force_password_change' => false
                ]
            ]
        );
        self::assertEquals(0, get_user_preferences('auth_forcepasswordchange', null, $result['user']->id));
    }

    /**
     * @return void
     */
    public function test_can_not_update_custom_fields_without_capability(): void {
        $gen = self::getDataGenerator();
        $user = $gen->create_user();
        $target_user = $gen->create_user();

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
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'username' => 'username',
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
        self::assertEquals('username', $result['user']->username);
        $custom_fields = $this->resolve_graphql_type(
            'core_user',
            'custom_fields',
            $result['user']
        );
        $custom_field = reset($custom_fields);
        self::assertEquals($text->shortname, $custom_field['shortname']);
        self::assertEquals('text', $custom_field['data']);
        self::assertEquals(0, $custom_field['data_format']);

        // Unassigned the capability.
        $context = context_user::instance($user->id);
        assign_capability('moodle/user:viewalldetails', CAP_PREVENT, $role->id, $context);

        self::expectExceptionMessage('The custom field does not exist.');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'username' => 'username',
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
    public function test_update_custom_fields_with_delete_field(): void {
        $gen = self::getDataGenerator();
        self::setAdminUser();
        $target_user = $gen->create_user();

        /** @var \totara_core\testing\generator $generator */
        $generator = $gen->get_plugin_generator('totara_core');

        $text = $generator->create_custom_profile_field(['datatype' => 'text']);
        $checkbox = $generator->create_custom_profile_field(['datatype' => 'checkbox', 'defaultdata' => 0]);
        $date = $generator->create_custom_profile_field(['datatype' => 'date']);
        $textarea = $generator->create_custom_profile_field(['datatype' => 'textarea']);

        // delete is false, api user can update user.
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'username' => 'username',
                    'custom_fields' => [
                        [
                            'shortname' => $text->shortname,
                            'data' => 'text',
                            'delete' => false
                        ],
                        [
                            'shortname' => $checkbox->shortname,
                            'data' => "1"
                        ],
                        [
                            'shortname' => $date->shortname,
                            'data' => '2014-1-21'
                        ],
                    ]
                ]
            ]
        );

        self::assertEquals(3,
            builder::table('user_info_data')
            ->where('userid', $target_user->id)
            ->count()
        );

        self::assertNotEmpty($result);
        $custom_fields = $this->resolve_graphql_type(
            'core_user',
            'custom_fields',
            $result['user']
        );
        self::assertCount(3, $custom_fields);

        try {
            $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $target_user->id,
                    ],
                    'input' => [
                        'username' => 'username',
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
            $this->fail("update_user_exception expected");
        } catch (update_user_exception $exception) {
            self::assertEquals("Can not set data or data_format with deleting custom field on updating.", $exception->getMessage());
        }

        // Delete two custom fields and update one.
        $result = $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'username' => 'username',
                    'custom_fields' => [
                        [
                            // Delete the text
                            'shortname' => $text->shortname,
                            'delete' => true
                        ],
                        [
                            'shortname' => $checkbox->shortname,
                            'delete' => true
                        ],
                        [
                            'shortname' => $date->shortname,
                            'data' => '2014-2-21'
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

        self::assertCount(3, $custom_fields);
        $text_record = builder::table('user_info_data')->select('data')->where('fieldid', $text->id)->one();
        self::assertEmpty($text_record->data);
        $date_record = builder::table('user_info_data')->select('data')->where('fieldid', $date->id)->one();
        self::assertNotEmpty($date_record->data);
        $checkbox_record = builder::table('user_info_data')->select('data')->where('fieldid', $checkbox->id)->one();
        self::assertEmpty($checkbox_record->data);

        // Deleting custom fields that the specific user doesn't have.
        self::expectExceptionMessage('Can not delete the custom field value, as the value has not been set for target user.');
        self::expectException(update_user_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            [
                'target_user' => [
                    'id' => $target_user->id,
                ],
                'input' => [
                    'username' => 'username',
                    'custom_fields' => [
                        [
                            'shortname' => $textarea->shortname,
                            'delete' => true
                        ],
                        [
                            'shortname' => $date->shortname,
                            'data' => '2016-2-21'
                        ],
                    ]
                ]
            ]
        );
    }

    /**
     * @return string[]
     */
    private function get_auth_plugins(): array {
        return [
            'cas', 'db', 'ldap', 'shibboleth',
            'approved', 'email',
            'manual', 'oauth2'
        ];
    }

    /**
     * @return void
     */
    public function test_update_locked_field_with_valid_cap(): void {
        $gen = self::getDataGenerator();
        $apiuser = $gen->create_user();
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        role_assign($role->id, $apiuser->id, context_system::instance());

        // Login as api user
        self::setUser($apiuser);

        foreach ($this->get_auth_plugins() as $plugin) {
            $target_user = $gen->create_user(['auth' => $plugin]);
            set_config('field_lock_firstname','locked', 'auth_' . $plugin);
            $result = $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $target_user->id,
                    ],
                    'input' => [
                        'firstname' => 'firstname'
                    ]
                ]
            );
            self::assertNotEmpty($result);
            self::assertEquals('firstname', $result['user']->firstname);
        }
    }

    /**
     * @return void
     */
    public function test_can_not_update_locked_field(): void {
        $gen = self::getDataGenerator();
        $apiuser = $gen->create_user();
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        unassign_capability('moodle/user:update', $role->id);
        role_assign($role->id, $apiuser->id, context_system::instance());

        // Login as api user
        self::setUser($apiuser);

        foreach ($this->get_auth_plugins() as $plugin) {
            $target_user = $gen->create_user(['lastname' => 'Wert', 'auth' => $plugin]);
            set_config('field_lock_firstname','locked', 'auth_' . $plugin);
            try {
                $this->resolve_graphql_mutation(
                    self::MUTATION,
                    [
                        'target_user' => [
                            'id' => $target_user->id,
                        ],
                        'input' => [
                            'firstname' => 'firstname'
                        ]
                    ]
                );
            } catch (update_user_exception $exception) {
                self::assertEquals('The firstname is Locked and can not be updated', $exception->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    public function test_can_not_update_unlockedifempty_field(): void {
        $gen = self::getDataGenerator();
        $apiuser = $gen->create_user();
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        unassign_capability('moodle/user:update', $role->id);
        role_assign($role->id, $apiuser->id, context_system::instance());

        // Login as api user
        self::setUser($apiuser);

        foreach ($this->get_auth_plugins() as $plugin) {
            // Can update user, if field is empty.
            $target_user = $gen->create_user(['firstname' => '', 'auth' => $plugin]);
            set_config('field_lock_firstname','unlockedifempty', 'auth_' . $plugin);

            $result = $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $target_user->id,
                    ],
                    'input' => [
                        'firstname' => 'firstname'
                    ]
                ]
            );
            self::assertNotEmpty($result);
            self::assertEquals('firstname', $result['user']->firstname);
        }

        foreach ($this->get_auth_plugins() as $plugin) {
            // Can not update user, if field is not empty.
            $target_user = $gen->create_user(['lastname' => 'Wert', 'auth' => $plugin]);
            set_config('field_lock_lastname','unlockedifempty', 'auth_' . $plugin);
            try {
                $this->resolve_graphql_mutation(
                    self::MUTATION,
                    [
                        'target_user' => [
                            'id' => $target_user->id,
                        ],
                        'input' => [
                            'lastname' => 'lastname'
                        ]
                    ]
                );
            } catch (update_user_exception $exception) {
                self::assertEquals('The lastname is Locked and can not be updated', $exception->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    public function test_can_not_update_locked_custom_field(): void {
        $gen = self::getDataGenerator();
        $apiuser = $gen->create_user();
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        unassign_capability('moodle/user:update', $role->id);
        role_assign($role->id, $apiuser->id, context_system::instance());

        /** @var \totara_core\testing\generator $generator */
        $generator = $gen->get_plugin_generator('totara_core');
        $datetime = $generator->create_custom_profile_field(['datatype' => 'datetime']);

        // Login as api user
        self::setUser($apiuser);

        foreach (['cas', 'db', 'ldap', 'shibboleth'] as $plugin) {
            $target_user = $gen->create_user(['auth' => $plugin]);
            set_config('field_lock_profile_field_' . $datetime->shortname,'locked', 'auth_' . $plugin);

            try {
                $this->resolve_graphql_mutation(
                    self::MUTATION,
                    [
                        'target_user' => [
                            'id' => $target_user->id,
                        ],
                        'input' => [
                            'lastname' => 'lastname',
                            'custom_fields' => [
                                [
                                    'shortname' => $datetime->shortname,
                                    'data' => '2014-06-22'
                                ]
                            ]
                        ]
                    ]
                );
            } catch (update_user_exception $exception) {
                self::assertEquals(
                    "Custom field for {$datetime->shortname} is Locked and can not be updated",
                    $exception->getMessage()
                );
            }
        }
    }

    /**
     * @return void
     */
    public function test_can_not_update_unlockedifempty_custom_field(): void {
        $gen = self::getDataGenerator();
        $apiuser = $gen->create_user();
        $roles = get_archetype_roles('apiuser');
        $role = reset($roles);
        unassign_capability('moodle/user:update', $role->id);
        role_assign($role->id, $apiuser->id, context_system::instance());

        /** @var \totara_core\testing\generator $generator */
        $generator = $gen->get_plugin_generator('totara_core');
        $text = $generator->create_custom_profile_field(['datatype' => 'text']);

        // Login as api user
        self::setUser($apiuser);
        foreach (['cas', 'db', 'ldap', 'shibboleth'] as $plugin) {
            // Custom field is empty and we can update it.
            $target_user = $gen->create_user(['auth' => $plugin]);
            set_config('field_lock_profile_field_' . $text->shortname,'unlockedifempty', 'auth_' . $plugin);
            $result = $this->resolve_graphql_mutation(
                self::MUTATION,
                [
                    'target_user' => [
                        'id' => $target_user->id,
                    ],
                    'input' => [
                        'lastname' => 'lastname',
                        'custom_fields' => [
                            [
                                'shortname' => $text->shortname,
                                'data' => 'text'
                            ]
                        ]
                    ]
                ]
            );
            self::assertNotEmpty($result);
        }

        foreach (['cas', 'db', 'ldap', 'shibboleth'] as $plugin) {
            // Custom field is empty and we can update it.
            $target_user = $gen->create_user(['auth' => $plugin]);
            set_config('field_lock_profile_field_' . $text->shortname,'unlockedifempty', 'auth_' . $plugin);
            $this->set_profile_field_value($target_user, $text, 'text');

            try {
                $this->resolve_graphql_mutation(
                    self::MUTATION,
                    [
                        'target_user' => [
                            'id' => $target_user->id,
                        ],
                        'input' => [
                            'lastname' => 'lastname',
                            'custom_fields' => [
                                [
                                    'shortname' => $text->shortname,
                                    'data' => ''
                                ]
                            ]
                        ]
                    ]
                );
            } catch (update_user_exception $exception) {
                self::assertEquals(
                    "Custom field for {$text->shortname} is Locked and can not be updated",
                    $exception->getMessage()
                );
            }
        }
    }
}