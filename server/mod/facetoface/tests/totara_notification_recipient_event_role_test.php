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
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

use core_phpunit\testcase;
use mod_facetoface\{seminar_event};
use mod_facetoface\seminar;
use mod_facetoface\totara_notification\recipient\event_role as recipient_group;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\trainer_helper;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_recipient_event_role_testcase extends testcase {

    /**
     * Test the function triggers an exception when no seminar event id is provided.
     */
    public function test_exception_get_user_ids_not_defined(): void {
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('missing seminar_event_id for event role seminar recipients');

        recipient_group::get_user_ids([]);
    }

    /**
     * Test the function returns an array with one user when expected.
     */
    public function test_one_event_with_one_role_return_ids_singular(): void {
        global $DB;
        $gen = $this->getDataGenerator();

        $f2f_gen = facetoface_generator::instance();

        // Create seminar
        $seminar = $this->create_seminar();

        // Add event
        $event = new seminar_event($f2f_gen->add_session(['facetoface' => $seminar->get_id(), 'sessiondates' => [time()]]));
        $event->set_facetoface($seminar->get_id());
        $event->save();

        // Assign teacher1 to course as trainer.
        $role = $DB->get_record('role', array('shortname' => 'teacher'));
        $teacher1 = $this->getDataGenerator()->create_user(['lastname' => 'trainer last name']);
        $gen->enrol_user($teacher1->id, $seminar->get_course(), $role->id);
        $teachers[] = $teacher1->id;
        $form[$role->id] = $teachers;

        $helper = new trainer_helper($event);
        foreach ($form as $roleid => $trainers) {
            $helper->add_trainers($roleid, $trainers);
        }

        $userids = recipient_group::get_user_ids(['seminar_event_id' => $event->get_id()]);
        $this->assertCount(1, $userids);
        $userid = reset($userids);
        $this->assertEquals($teacher1->id, $userid);
    }

    /**
     * Test the function returns an array with multiple user when expected.
     */
    public function test_one_event_with_multiple_trainer_return_multiple_users(): void {

        global $DB;
        $gen = $this->getDataGenerator();

        $f2f_gen = facetoface_generator::instance();

        // Create seminar
        $seminar = $this->create_seminar();

        // Add event
        $event = new seminar_event($f2f_gen->add_session(['facetoface' => $seminar->get_id(), 'sessiondates' => [time()]]));
        $event->set_facetoface($seminar->get_id());
        $event->save();

        // Assign teacher1 and teacher2 to thcourse as trainer.
        $role = $DB->get_record('role', array('shortname' => 'teacher'));
        $teacher1 = $this->getDataGenerator()->create_user(['lastname' => 'trainer1 last name']);
        $teacher2 = $this->getDataGenerator()->create_user(['lastname' => 'trainer2 last name']);
        $gen->enrol_user($teacher1->id, $seminar->get_course(), $role->id);
        $gen->enrol_user($teacher2->id, $seminar->get_course(), $role->id);
        $teachers1[] = $teacher1->id;
        $teachers2[] = $teacher2->id;
        $form[$role->id] = $teachers1;
        $form[$role->id + 1] = $teachers2;

        // Add trainers to event.
        $helper = new trainer_helper($event);
        foreach ($form as $roleid => $trainer) {
            $helper->add_trainers($roleid, $trainer);
        }

        $userids = recipient_group::get_user_ids(['seminar_event_id' => $event->get_id()]);
        $this->assertCount(2, $userids);
    }

    /**
     * Test the function returns an array with multiple user when multiple role added.
     */
    public function test_one_event_with_multiple_role_return_multiple_users(): void {
        global $DB;
        $gen = $this->getDataGenerator();

        $f2f_gen = facetoface_generator::instance();

        // Create seminar
        $seminar = $this->create_seminar();

        // Add event
        $event = new seminar_event($f2f_gen->add_session(['facetoface' => $seminar->get_id(), 'sessiondates' => [time()]]));
        $event->set_facetoface($seminar->get_id());
        $event->save();

        // Assign teacher1 to course as trainer.
        $roleteacher = $DB->get_record('role', array('shortname' => 'teacher'));
        $rolemanager = $DB->get_record('role', array('shortname' => 'manager'));
        $roleeditingteacher = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $rolecoursecreator = $DB->get_record('role', array('shortname' => 'coursecreator'));
        $teacher1 = $this->getDataGenerator()->create_user(['lastname' => 'trainer last name']);
        $manager1 = $this->getDataGenerator()->create_user(['lastname' => 'manager last name']);
        $editingteacher1 = $this->getDataGenerator()->create_user(['lastname' => 'editingteacher last name']);
        $coursecreator1 = $this->getDataGenerator()->create_user(['lastname' => 'coursecreator last name']);
        $gen->enrol_user($teacher1->id, $seminar->get_course(), $roleteacher->id);
        $gen->enrol_user($manager1->id, $seminar->get_course(), $rolemanager->id);
        $gen->enrol_user($editingteacher1->id, $seminar->get_course(), $roleeditingteacher->id);
        $gen->enrol_user($coursecreator1->id, $seminar->get_course(), $rolecoursecreator->id);
        $teachers[] = $teacher1->id;
        $managers[] = $manager1->id;
        $editingteacher[] = $editingteacher1->id;
        $coursecreator[] = $coursecreator1->id;
        $form[$roleteacher->id] = $teachers;
        $form[$rolemanager->id] = $managers;
        $form[$roleeditingteacher->id] = $editingteacher;
        $form[$rolecoursecreator->id] = $coursecreator;

        $helper = new trainer_helper($event);
        foreach ($form as $roleid => $trainers) {
            $helper->add_trainers($roleid, $trainers);
        }

        $userids = recipient_group::get_user_ids(['seminar_event_id' => $event->get_id()]);
        // teacher, manager, editingteacher and coursecreator
        $this->assertCount(4, $userids);
    }

    /**
     * @return seminar
     */
    private function create_seminar(): seminar {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $f2f_gen = facetoface_generator::instance();
        $f2f = $f2f_gen->create_instance(['course' => $course->id]);

        $s = new seminar($f2f->id);
        $s->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)->save();
        $s->set_approvalrole(seminar::APPROVAL_ROLE)->save();
        $s->set_approvaltype(seminar::APPROVAL_ROLE)->save();
        return $s;
    }

    /**
     * @param int $numberofusers
     * @return stdClass[]
     */
    private function create_users(int $numberofusers): array {
        $generator = $this->getDataGenerator();
        $manager = $generator->create_user();
        $managerja = job_assignment::create_default($manager->id);

        $users = [];
        for ($i = 0; $i < $numberofusers; $i++) {
            $user = $generator->create_user();
            job_assignment::create_default($user->id, ['managerjaid' => $managerja->id]);
            $users[] = $user;
        }

        return $users;
    }


}