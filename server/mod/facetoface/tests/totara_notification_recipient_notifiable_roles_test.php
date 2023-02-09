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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_facetoface\totara_notification\recipient\notifiable_roles as recipient_group;
use mod_facetoface\testing\generator;


defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_recipient_notifiable_role_testcase extends testcase {

    private $users = [];
    private $course = null;
    private $seminar = null;
    private $seminarevent = null;
    private $roles = [];

    public function setUp(): void {
        parent::setUp();

        $generator = $this->getDataGenerator();

        /** @var generator $f2f_generator */
        $f2f_generator = $generator->get_plugin_generator('mod_facetoface');

        // Users:
        //    trainer - assigned to the course
        //    learner - assigned to the course
        //    staff manager - not assigned to the course
        // Using old role names as keys for ease of use
        $this->users['student'] = $generator->create_user(['lastname' => 'Learner lastname']);
        $this->users['teacher'] = $generator->create_user(['lastname' => 'Trainer lastname']);
        $this->users['staffmanager'] = $generator->create_user(['lastname' => 'Manager lastname']);
        $roles = builder::table('role')
            ->select('id')
            ->add_select('shortname')
            ->where_in('shortname',['teacher', 'student', 'staffmanager'])
            ->fetch();
        foreach ($roles as $role) {
            $this->roles[$role->shortname] = $role->id;
        }

        // Create a course.
        $this->course = $generator->create_course(['fullname' => 'The test course']);

        // Enrol learner and trainer
        foreach (['student', 'teacher'] as $role) {
            $generator->enrol_user($this->users[$role]->id, $this->course->id, $this->roles[$role]);
        }

        $this->seminarevent = $f2f_generator->create_session_for_course($this->course);
        $this->seminar = $this->seminarevent->get_seminar();
        $this->seminar->set_name('Seminar 1')->save();
    }

    public function tearDown(): void {
        $this->users = [];
        $this->course = null;
        $this->seminar = null;
        $this->seminarevent = null;
        $this->roles = [];

        parent::tearDown();
    }

    /**
     * Test the function triggers an exception when no seminar event id is provided.
     */
    public function test_exception_get_user_ids_not_defined(): void {
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Missing seminar_event_id for notifiable role recipients');

        recipient_group::get_user_ids([]);
    }

    public function test_notifiable_roles(): void {
        global $CFG;

        // No roles
        $userids = recipient_group::get_user_ids(['seminar_event_id' => $this->seminarevent->get_id()]);
        $this->assertEmpty($userids);

        // Role set, no user with the role
        set_config('facetoface_session_rolesnotify', $this->roles['staffmanager']);
        $userids = recipient_group::get_user_ids(['seminar_event_id' => $this->seminarevent->get_id()]);
        $this->assertEmpty($userids);

        // Now assign the staffmanager role to the user not enrolled in the course
        static::getDataGenerator()->role_assign($this->roles['staffmanager'], $this->users['staffmanager']->id, context_system::instance());
        $userids = recipient_group::get_user_ids(['seminar_event_id' => $this->seminarevent->get_id()]);
        $expected = [$this->users['staffmanager']->id];
        $this->assertEquals($expected, array_values($userids));

        // Now also add the trainer role as a notifier role
        set_config('facetoface_session_rolesnotify', $this->roles['staffmanager'] . ',' . $this->roles['teacher']);
        $userids = recipient_group::get_user_ids(['seminar_event_id' => $this->seminarevent->get_id()]);
        $expected = [$this->users['staffmanager']->id, $this->users['teacher']->id];
        $this->assertEquals($expected, array_values($userids));
    }

}