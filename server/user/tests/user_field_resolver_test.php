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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_user
 */
defined('MOODLE_INTERNAL') || die();

use core_user\access_controller;
use core_user\profile\user_field_resolver;
use totara_core\hook\manager as hook_manager;

class core_user_user_field_resolver_testcase extends advanced_testcase {

    public function setUp(): void {
        parent::setUp();

        // Remove hook's watchers so that we can have more accurate tests.
        hook_manager::phpunit_replace_watchers([]);
        access_controller::clear_instance_cache();
    }

    /**
     * @return void
     */
    public function test_get_mail_to_field(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Log in as user two and check if the user-two is able to see user's one mail-to url.
        $this->setUser($user_two);

        $resolver = user_field_resolver::from_record($user_one);
        $this->assertNull($resolver->get_field_value('mailtourl'));

        // Enrol these two users within a same course.
        $course = $generator->create_course();

        $generator->enrol_user($user_one->id, $course->id);
        $generator->enrol_user($user_two->id, $course->id);

        // Reset resolver, as access_controller is not designed to reuse the instance with on-the-fly calculation
        $resolver = user_field_resolver::from_record($user_one, $course->id);
        $this->assertEquals("mailto:{$user_one->email}", $resolver->get_field_value('mailtourl'));
    }

    /**
     * @return void
     */
    public function test_get_profile_url_field(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Log in as user two and check if the user-two is able to see user's one mail-to url.
        $this->setUser($user_two);

        $resolver = user_field_resolver::from_record($user_one);
        $this->assertNull($resolver->get_field_value('profileurl'));

        // Enrol these two users within a same course.
        $course = $generator->create_course();

        $generator->enrol_user($user_one->id, $course->id);
        $generator->enrol_user($user_two->id, $course->id);

        // Reset resolver, as access_controller is not designed to reuse the instance with on-the-fly calculation
        $resolver = user_field_resolver::from_record($user_one, $course->id);
        $expected_url = new moodle_url('/user/profile.php', ['id' => $user_one->id]);

        $this->assertEquals($expected_url->out(false), $resolver->get_field_value('profileurl'));
    }

    /**
     * @return void
     */
    public function test_get_invalid_field(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_two);
        $resolver = user_field_resolver::from_record($user_one);

        $this->expectException(coding_exception::class);
        $resolver->get_field_value('something_invalid');
    }

    /**
     * @return void
     */
    public function test_check_computed_fields(): void {
        $this->assertTrue(user_field_resolver::is_computed_field('fullname'));
        $this->assertTrue(user_field_resolver::is_computed_field('mailtourl'));
        $this->assertTrue(user_field_resolver::is_computed_field('profileurl'));

        $this->assertfalse(user_field_resolver::is_computed_field('skypeid'));
        $this->assertFalse(user_field_resolver::is_computed_field('something_invalid_computed'));
        $this->assertFalse(user_field_resolver::is_computed_field('1'));
        $this->assertFalse(user_field_resolver::is_computed_field('something_i2nvalid_computed'));
        $this->assertFalse(user_field_resolver::is_computed_field('335r'));
        $this->assertFalse(user_field_resolver::is_computed_field('something_invalid_computed3'));
    }

    /**
     * @return void
     */
    public function test_check_db_fields(): void {
        $this->assertTrue(user_field_resolver::is_db_field('firstname'));
        $this->assertTrue(user_field_resolver::is_db_field('lastname'));
        $this->assertTrue(user_field_resolver::is_db_field('id'));
        $this->assertTrue(user_field_resolver::is_db_field('skype'));
        $this->assertTrue(user_field_resolver::is_db_field('department'));
        $this->assertTrue(user_field_resolver::is_db_field('address'));

        $this->assertFalse(user_field_resolver::is_db_field('fullname'));
        $this->assertFalse(user_field_resolver::is_db_field('mailtourl'));
        $this->assertFalse(user_field_resolver::is_db_field('profileurl'));
        $this->assertFalse(user_field_resolver::is_db_field('skypeid'));
    }

    /**
     * @return void
     */
    public function test_get_skypeid(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user(['skype' => 'skype_id_with_me']);

        $this->setUser($user_one);
        $resolver = user_field_resolver::from_record($user_one);

        $this->assertEquals('skype_id_with_me', $resolver->get_field_value('skype'));
    }

    /**
     * @return void
     */
    public function test_get_profileimagealt(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user(['imagealt' => 'Test alt text']);

        $this->setUser($user_one);
        $resolver = user_field_resolver::from_record($user_one);

        $this->assertEquals('Test alt text', $resolver->get_field_value('profileimagealt'));

        $user_two = $generator->create_user(['firstname' => 'Firstname', 'lastname' => 'Lastname']);

        $this->setUser($user_two);
        $resolver = user_field_resolver::from_record($user_two);

        $this->assertEquals('Firstname Lastname', $resolver->get_field_value('profileimagealt'));
    }

    /**
     * @return void
     */
    public function test_get_decription_format(): void {
        $generator =  self::getDataGenerator();
        $user = $generator->create_user(['descriptionformat' => FORMAT_HTML]);
        $user1 = $generator->create_user();

        // Login as self
        self::setUser($user);
        $resolver = user_field_resolver::from_record($user);
        $this->assertEquals('HTML', $resolver->get_field_value('descriptionformat'));

        self::setAdminUser();
        $resolver = user_field_resolver::from_record($user);
        $this->assertEquals('HTML', $resolver->get_field_value('descriptionformat'));

        // Login as other and can not view 'descriptionformat'
        self::setUser($user1);
        $resolver = user_field_resolver::from_record($user);
        self::assertNull($resolver->get_field_value('descriptionformat'));

        $db = \core\orm\query\builder::get_db();
        $role = $db->get_record('role', ['archetype' => 'apiuser']);
        $user2 = $generator->create_user();
        role_assign($role->id, $user2->id, context_system::instance());

        // Login as apiuser
        self::setUser($user2);
        $resolver = user_field_resolver::from_record($user);

        // User does not enrol any course, so api user can not view 'descriptionformat'
        self::assertNull($resolver->get_field_value('descriptionformat'));

        // Disabled the setting.
        set_config('profilesforenrolledusersonly', 0);
        $resolver = user_field_resolver::from_record($user);
        $this->assertEquals('HTML', $resolver->get_field_value('descriptionformat'));
    }
}