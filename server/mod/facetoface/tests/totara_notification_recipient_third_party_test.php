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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

use core_phpunit\testcase;
use mod_facetoface\seminar;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\totara_notification\recipient\third_party as recipient_group;
use totara_core\totara_user as ext_user;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_recipient_third_party_test extends testcase {

    /**
     * Test the function fails with invalid args
     */
    public function test_get_user_objects_missing_args(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("missing seminar_id for third party seminar recipients");

        recipient_group::get_user_objects([]);
    }

    /**
     * Test the function fails with invalid args
     */
    public function test_get_user_objects_invalid_args(): void {
        $gen = $this->getDataGenerator();
        $f2f_gen = facetoface_generator::instance();

        $course = $gen->create_course();
        $f2f = $f2f_gen->create_instance(['course' => $course->id]);

        $this->expectException(dml_missing_record_exception::class);
        $this->expectExceptionMessage("Can not find data record in database.");

        recipient_group::get_user_objects(['seminar_id' => $f2f->id * 3]);
    }

    /**
     * Test the function returns an empty array when expected.
     */
    public function test_get_user_objects_empty(): void {
        $gen = $this->getDataGenerator();
        $f2f_gen = facetoface_generator::instance();

        $course = $gen->create_course();
        $f2f = $f2f_gen->create_instance(['course' => $course->id]);

        $users = recipient_group::get_user_objects(['seminar_id' => $f2f->id]);
        $this->assertEmpty($users);
    }

    /**
     * Test the function returns an array with a single user object as expected.
     */
    public function test_get_user_objects_singular(): void {
        $gen = $this->getDataGenerator();
        $f2f_gen = facetoface_generator::instance();

        $course = $gen->create_course();
        $f2f = $f2f_gen->create_instance(['course' => $course->id]);
        $seminar = new seminar($f2f->id);
        $seminar->set_thirdparty('noreply@example.com');
        $seminar->save();

        $users = recipient_group::get_user_objects(['seminar_id' => $f2f->id]);
        $this->assertCount(1, $users);

        $user = array_pop($users);
        $this->assertInstanceOf('stdClass', $user);
        $this->assertEquals(ext_user::EXTERNAL_USER, $user->id);
        $this->assertEquals('noreply@example.com', $user->email);
    }

    /**
     * Test the function returns an array with multiple correct user objects when expected.
     */
    public function test_get_user_objects_multiple(): void {
        $gen = $this->getDataGenerator();
        $f2f_gen = facetoface_generator::instance();

        $course = $gen->create_course();
        $f2f = $f2f_gen->create_instance(['course' => $course->id]);
        $seminar = new seminar($f2f->id);
        $third_party = ['noreply1@example.com', 'noreply2@example.com', 'noreply3@example.com'];
        $seminar->set_thirdparty(implode(',', $third_party));
        $seminar->save();

        $users = recipient_group::get_user_objects(['seminar_id' => $f2f->id]);
        $this->assertCount(3, $users);

        $emails = [];
        foreach ($users as $user) {
            $this->assertInstanceOf('stdClass', $user);
            $this->assertEquals(ext_user::EXTERNAL_USER, $user->id);
            $emails[] = $user->email;
        }

        $this->assertEqualsCanonicalizing($third_party, $emails);
    }

    /**
     * Test the function returns an array with the correct users when there are multiple seminars.
     */
    public function test_get_user_objects_module(): void {
        $gen = $this->getDataGenerator();
        $f2f_gen = facetoface_generator::instance();

        $course = $gen->create_course();
        $f2f1 = $f2f_gen->create_instance(['course' => $course->id]);
        $seminar1 = new seminar($f2f1->id);
        $third_party1 = ['noreply1@example.com', 'noreply2@example.com', 'noreply3@example.com'];
        $seminar1->set_thirdparty(implode(',', $third_party1));
        $seminar1->save();

        $f2f2 = $f2f_gen->create_instance(['course' => $course->id]);
        $seminar2 = new seminar($f2f2->id);
        $third_party2 = ['noreply4@example.com', 'noreply5@example.com', 'noreply6@example.com'];
        $seminar2->set_thirdparty(implode(',', $third_party2));
        $seminar2->save();

        // Check the user objects returned for the first seminar are correct
        $users1 = recipient_group::get_user_objects(['seminar_id' => $f2f1->id]);
        $this->assertCount(3, $users1);

        $emails1 = [];
        foreach ($users1 as $user) {
            $this->assertInstanceOf('stdClass', $user);
            $this->assertEquals(ext_user::EXTERNAL_USER, $user->id);
            $emails1[] = $user->email;
        }

        $this->assertEqualsCanonicalizing($third_party1, $emails1);

        // Check the user objects returned for the first seminar are correct
        $users2 = recipient_group::get_user_objects(['seminar_id' => $f2f2->id]);
        $this->assertCount(3, $users2);

        $emails2 = [];
        foreach ($users2 as $user) {
            $this->assertInstanceOf('stdClass', $user);
            $this->assertEquals(ext_user::EXTERNAL_USER, $user->id);
            $emails2[] = $user->email;
        }

        $this->assertEqualsCanonicalizing($third_party2, $emails2);
    }
}
