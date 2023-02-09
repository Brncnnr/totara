<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author David Curry <david.curry@totaralearning.com>
 * @package core_user
 */

defined('MOODLE_INTERNAL') || die();

use core_user\access_controller;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core\format;
use core\date_format;
use core_user\profile\card_display;
use totara_core\hook\manager as hook_manager;

/**
 * Tests the user type
 */
class core_webapi_resolver_type_user_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    public function setUp(): void {
        parent::setUp();

        hook_manager::phpunit_replace_watchers([]);
        access_controller::clear_instance_cache();
    }

    public function test_resolver_id() {
        $user = $this->getDataGenerator()->create_user();
        self::assertSame($user->id, $this->resolve_graphql_type('core_user', 'id', $user, []));
    }

    public function test_resolver_password() {
        $user = new stdClass();
        $user->id = '100';
        $user->password = 'test';
        self::assertNull($this->resolve_graphql_type('core_user', 'password', $user, []));
    }

    public function test_resolver_secret() {
        $user = new stdClass();
        $user->id = '100';
        $user->secret = 'test';
        self::assertNull($this->resolve_graphql_type('core_user', 'secret', $user, []));
    }

    public function test_resolver_fake_set_field() {
        $user = new stdClass();
        $user->id = '100';
        $user->blah = 'test';
        $this->expectExceptionMessage('Coding error detected, it must be fixed by a programmer: Unknown user field');
        $this->resolve_graphql_type('core_user', 'blah', $user, []);
    }

    public function test_resolver_fake_unset_field() {
        $user = new stdClass();
        $user->id = '100';
        $this->expectExceptionMessage('Coding error detected, it must be fixed by a programmer: Unknown user field');
        $this->resolve_graphql_type('core_user', 'blah', $user, []);
    }

    public function test_inaccessible_field() {
        $user = $this->getDataGenerator()->create_user();
        $user->emailstop = '1';
        self::assertNull($this->resolve_graphql_type('core_user', 'emailstop', $user, []));
    }

    public function test_resolver_idnumber() {
        global $CFG;
        $CFG->showuseridentity = 'idnumber';
        $value = 'test';
        $user = $this->getDataGenerator()->create_user(['idnumber' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'idnumber', $user, []));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'idnumber', $user, []));
    }

    public function test_resolver_fullname() {
        $args = ['format' => format::FORMAT_PLAIN];
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Joe', 'lastname' => "O'Malley"]);

        try {
            $this->resolve_graphql_type('core_user', 'fullname', $user, $args);
            $this->fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('You did not check you can view a user before resolving them', $exception->getMessage());
        }

        $this->setAdminUser();
        self::assertSame(
            html_to_text(fullname($user), 0, false),
            $this->resolve_graphql_type('core_user', 'fullname', $user, $args)
        );
    }

    public function test_resolver_firstname() {
        $args = ['format' => format::FORMAT_PLAIN];
        $user = $this->getDataGenerator()->create_user(['firstname' => 'test']);
        self::assertNull($this->resolve_graphql_type('core_user', 'firstname', $user, $args));

        $this->setAdminUser();
        self::assertSame('test', $this->resolve_graphql_type('core_user', 'firstname', $user, $args));
    }

    public function test_resolver_lastname() {
        $args = ['format' => format::FORMAT_PLAIN];
        $user = $this->getDataGenerator()->create_user(['lastname' => 'test']);
        self::assertNull($this->resolve_graphql_type('core_user', 'lastname', $user, $args));

        $this->setAdminUser();
        self::assertSame('test', $this->resolve_graphql_type('core_user', 'lastname', $user, $args));
    }

    public function test_resolver_firstnamephonetic() {
        $user = $this->getDataGenerator()->create_user(['lastname' => 'test', 'firstnamephonetic' => 'leao']);
        self::assertNull($this->resolve_graphql_type('core_user', 'firstnamephonetic', $user));

        $this->setAdminUser();
        self::assertSame('leao', $this->resolve_graphql_type('core_user', 'firstnamephonetic', $user));
    }

    public function test_resolver_lastnamephonetic() {
        $user = $this->getDataGenerator()->create_user(['lastname' => 'test', 'lastnamephonetic' => 'bunty']);
        self::assertNull($this->resolve_graphql_type('core_user', 'lastnamephonetic', $user));

        $this->setAdminUser();
        self::assertSame('bunty', $this->resolve_graphql_type('core_user', 'lastnamephonetic', $user));
    }

    public function test_resolver_email() {
        global $CFG;
        $this->setAdminUser();
        $value = 'email@example.com';
        $user = $this->getDataGenerator()->create_user(['email' => $value]);
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'email', $user, []));
        $CFG->showuseridentity = 'idnumber';
        $user->maildisplay = 0;
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'email', $user, []));

        $this->setUser($this->getDataGenerator()->create_user());
        self::assertNull($this->resolve_graphql_type('core_user', 'email', $user, []));

        $this->setGuestUser();
        self::assertNull($this->resolve_graphql_type('core_user', 'email', $user, []));
    }

    public function test_resolver_address() {
        $args = ['format' => format::FORMAT_PLAIN];
        $value = 'Example address goes here';
        $user = $this->getDataGenerator()->create_user(['address' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'address', $user, $args));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'address', $user, $args));
    }

    public function test_resolver_phone1() {
        $args = ['format' => format::FORMAT_PLAIN];
        $value = '444';
        $user = $this->getDataGenerator()->create_user(['phone1' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'phone1', $user, $args));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'phone1', $user, $args));
    }

    public function test_resolver_phone2() {
        $args = ['format' => format::FORMAT_PLAIN];
        $value = '555';
        $user = $this->getDataGenerator()->create_user(['phone2' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'phone2', $user, $args));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'phone2', $user, $args));
    }

    public function test_resolver_department() {
        $args = ['format' => format::FORMAT_PLAIN];
        $value = 'Development';
        $user = $this->getDataGenerator()->create_user(['department' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'department', $user, $args));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'department', $user, $args));
    }

    public function test_resolver_institution() {
        $args = ['format' => format::FORMAT_PLAIN];
        $value = 'Totara Learning Solutions';
        $user = $this->getDataGenerator()->create_user(['institution' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'institution', $user, $args));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'institution', $user, $args));
    }

    public function test_resolver_city() {
        $args = ['format' => format::FORMAT_PLAIN];
        $value = 'Wellington';
        $user = $this->getDataGenerator()->create_user(['city' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'city', $user, $args));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'city', $user, $args));
    }

    public function test_resolver_country() {
        $value = 'NZ';
        $user = $this->getDataGenerator()->create_user(['country' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'country', $user, []));

        $this->setAdminUser();
        self::assertSame(get_string($value, 'countries'), $this->resolve_graphql_type('core_user', 'country', $user, []));
    }

    public function test_resolver_lang() {
        $user1 = $this->getDataGenerator()->create_user(['lang' => 'en']);
        self::assertNull($this->resolve_graphql_type('core_user', 'lang', $user1, [])); // Can't see if not logged in.
        $this->setUser($user1);
        self::assertSame('en', $this->resolve_graphql_type('core_user', 'lang', $user1, []));

        $user2 = $this->getDataGenerator()->create_user(['lang' => 'fr']);
        self::assertNull($this->resolve_graphql_type('core_user', 'lang', $user2, [])); // User 1 can't see user 2s value.
        $this->setUser($user2);
        self::assertSame('fr', $this->resolve_graphql_type('core_user', 'lang', $user2, []));

        $this->setAdminUser();
        self::assertSame('en', $this->resolve_graphql_type('core_user', 'lang', $user1, []));
        self::assertSame('fr', $this->resolve_graphql_type('core_user', 'lang', $user2, []));
    }

    public function test_resolver_timezone() {
        $value = 'Pacific/Auckland';
        $user = $this->getDataGenerator()->create_user(['timezone' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'timezone', $user, []));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'timezone', $user, []));
    }

    public function test_resolver_theme() {
        $value = 'ventura';
        $user = $this->getDataGenerator()->create_user(['theme' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'theme', $user, []));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'theme', $user, []));
    }

    public function test_resolver_interests() {
        $args = ['format' => format::FORMAT_PLAIN];
        $value = 'dancing';
        $user = $this->getDataGenerator()->create_user(['interests' => $value]);
        self::assertNull($this->resolve_graphql_type('core_user', 'interests', $user, $args));

        $this->setAdminUser();
        self::assertSame($value, $this->resolve_graphql_type('core_user', 'interests', $user, $args));
    }

    public function test_resolver_description() {
        global $CFG;

        $user = $this->getDataGenerator()->create_user(['description' => '<p>This is a test</p>']);
        $this->setUser($user);
        self::assertSame('<p>This is a test</p>', $this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_HTML]));
        self::assertSame("This is a test\n", $this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame('<p>This is a test</p>', $this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_RAW]));

        $context = \context_user::instance($user->id);
        $roleid = $this->getDataGenerator()->create_role([]);
        role_assign($roleid, $user->id, $context);
        assign_capability('moodle/user:editownprofile', CAP_PROHIBIT, $roleid, $context);
        self::assertSame('<p>This is a test</p>', $this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_HTML]));
        self::assertSame("This is a test\n", $this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_PLAIN]));
        self::assertNull($this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_RAW]));

        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);
        self::assertNull($this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_HTML]));
        self::assertNull($this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_PLAIN]));
        self::assertNull($this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_RAW]));

        $CFG->forceloginforprofiles = false;
        self::assertSame('<p>This is a test</p>', $this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_HTML]));
        self::assertSame("This is a test\n", $this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_PLAIN]));
        self::assertSame(null, $this->resolve_graphql_type('core_user', 'description', $user, ['format' => \core\format::FORMAT_RAW]));
    }

    public function test_resolver_descriptionformat() {
        $user = $this->getDataGenerator()->create_user(['description' => 'test', 'descriptionformat' => FORMAT_HTML]);
        self::assertNull($this->resolve_graphql_type('core_user', 'descriptionformat', $user, []));

        $this->setAdminUser();
        self::assertSame('HTML', $this->resolve_graphql_type('core_user', 'descriptionformat', $user, []));
    }

    public function test_resolver_profileimageurl() {
        $user = $this->getDataGenerator()->create_user([]);
        self::assertNull($this->resolve_graphql_type('core_user', 'profileimageurl', $user, []));

        $this->setAdminUser();
        self::assertStringContainsString('theme/image.php/_s/ventura/core/1/u/f1', $this->resolve_graphql_type('core_user', 'profileimageurl', $user, []));
    }

    public function test_resolver_profileimageurlsmall() {
        $user = $this->getDataGenerator()->create_user([]);
        self::assertNull($this->resolve_graphql_type('core_user', 'profileimageurlsmall', $user, []));

        $this->setAdminUser();
        self::assertStringContainsString('theme/image.php/_s/ventura/core/1/u/f2', $this->resolve_graphql_type('core_user', 'profileimageurlsmall', $user, []));
    }

    public function test_resolver_profileimagealt() {
        $args = ['format' => format::FORMAT_PLAIN];
        $user = $this->getDataGenerator()->create_user(['imagealt' => 'test']);
        // This user does not have a alt text so it should fall back to the user's full name
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Firstname', 'lastname' => 'Lastname']);
        self::assertNull($this->resolve_graphql_type('core_user', 'profileimagealt', $user, $args));
        self::assertNull($this->resolve_graphql_type('core_user', 'profileimagealt', $user2, $args));

        $this->setAdminUser();
        self::assertSame('test', $this->resolve_graphql_type('core_user', 'profileimagealt', $user, $args));
        self::assertSame('Firstname Lastname', $this->resolve_graphql_type('core_user', 'profileimagealt', $user2, $args));
    }

    public function test_resolver_firstaccess() {
        global $DB;
        $args = ['format' => date_format::FORMAT_TIMESTAMP];
        $value = time();
        $user = $this->getDataGenerator()->create_user([]);
        self::assertNull($this->resolve_graphql_type('core_user', 'firstaccess', $user, $args));
        $DB->set_field('user', 'firstaccess', $value, ['id' => $user->id]);
        $user = $DB->get_record('user', ['id' => $user->id]);
        self::assertEquals(null, $this->resolve_graphql_type('core_user', 'firstaccess', $user, $args));

        $this->setAdminUser();
        self::assertEquals($value, $this->resolve_graphql_type('core_user', 'firstaccess', $user, $args));
    }

    public function test_resolver_lastaccess() {
        global $DB;
        $args = ['format' => date_format::FORMAT_TIMESTAMP];
        $value = time();
        $user = $this->getDataGenerator()->create_user([]);
        self::assertNull($this->resolve_graphql_type('core_user', 'lastaccess', $user, $args));
        $DB->set_field('user', 'lastaccess', $value, ['id' => $user->id]);
        $user = $DB->get_record('user', ['id' => $user->id]);
        self::assertEquals(null, $this->resolve_graphql_type('core_user', 'lastaccess', $user, $args));

        $this->setAdminUser();
        self::assertEquals($value, $this->resolve_graphql_type('core_user', 'lastaccess', $user, $args));
    }

    public function test_resolver_guest_user() {
        global $DB;
        $args = ['format' => date_format::FORMAT_TIMESTAMP];
        $this->setAdminUser();
        $value = time();
        $user = guest_user();
        self::assertNull($this->resolve_graphql_type('core_user', 'firstaccess', $user, $args));

        $DB->set_field('user', 'firstaccess', $value, ['id' => $user->id]);
        $user = $DB->get_record('user', ['id' => $user->id]);
        self::assertEquals($user->firstaccess, $this->resolve_graphql_type('core_user', 'firstaccess', $user, $args));
    }

    public function test_resolver_deleted_user() {
        global $DB;
        $args = ['format' => date_format::FORMAT_TIMESTAMP];
        $this->setAdminUser();
        $value = time();
        $user = $this->getDataGenerator()->create_user([]);
        self::assertNull($this->resolve_graphql_type('core_user', 'firstaccess', $user, $args));
        $DB->set_field('user', 'firstaccess', $value, ['id' => $user->id]);
        $user = $DB->get_record('user', ['id' => $user->id]);
        self::assertEquals($value, $this->resolve_graphql_type('core_user', 'firstaccess', $user, $args));

        delete_user($user);

        $user = $DB->get_record('user', ['id' => $user->id]);
        self::assertEquals(null, $this->resolve_graphql_type('core_user', 'firstaccess', $user, $args));
    }

    public function test_resolver_hidden_user_fields() {
        global $CFG;
        $args = ['format' => format::FORMAT_HTML];
        $CFG->hiddenuserfields = 'description';
        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user(['description' => 'test']);
        self::assertSame('test', $this->resolve_graphql_type('core_user', 'description', $user1, $args));

        $this->setUser($this->getDataGenerator()->create_user());
        self::assertNull($this->resolve_graphql_type('core_user', 'description', $user1, $args));
    }

    /**
     * @return void
     */
    public function test_resolver_display_field(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $display = $this->resolve_graphql_type('core_user', 'card_display', $user_one, []);
        $this->assertInstanceOf(card_display::class, $display);
    }

    /**
     * @return void
     */
    public function test_can_not_views_custom_fields_without_capability(): void {
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

        $taget_user = \core_user\external\user_helper::create_user([
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
        ]);

        $custom_fields = $this->resolve_graphql_type(
            'core_user',
            'custom_fields',
            $taget_user
        );

        $custom_field = reset($custom_fields);
        self::assertEquals($text->shortname, $custom_field['shortname']);
        self::assertEquals('text', $custom_field['data']);
        self::assertEquals('TEXT', $custom_field['data_type']);
        self::assertEquals(0, $custom_field['data_format']);

        // Unassign the capability.
        $context = context_user::instance($user->id);
        assign_capability( 'moodle/user:viewalldetails', CAP_PREVENT, $role->id, $context);

        $custom_fields = $this->resolve_graphql_type(
            'core_user',
            'custom_fields',
            $taget_user
        );

       self::assertEmpty($custom_fields);
    }
}