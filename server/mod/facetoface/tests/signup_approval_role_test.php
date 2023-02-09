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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

use mod_facetoface\{role, seminar, seminar_event, signup, seminar_session};
use mod_facetoface\signup\state\{declined, requestedrole, requested};
use totara_job\job_assignment;

/**
 * Class mod_facetoface_signup_approval_role_testcase
 */
class mod_facetoface_signup_approval_role_testcase extends advanced_testcase {
    /**
     * Creating a seminar with approval type as the managers and administrative approval here, with the admin Id
     * @param int $roleid
     * @return seminar
     * @throws coding_exception
     */
    private function create_facetoface(int $roleid): seminar {
        $generator = $this->getDataGenerator();

        $course = $this->getDataGenerator()->create_course(null, ['createsections' => true]);

        /** @var \mod_facetoface\testing\generator $f2fgenerator */
        $f2fgenerator = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgenerator->create_instance([
            'course' => $course->id,
            'approvaltype' => seminar::APPROVAL_ROLE,
            'approvalrole' => $roleid
        ]);

        return new seminar($f2f->id);
    }

    /**
     * @param int $numberofusers
     * @return stdClass[]
     */
    private function create_users(int $numberofusers): array {
        $generator = $this->getDataGenerator();

        $users = [];
        for ($i = 0; $i < $numberofusers; $i++) {
            $user = $generator->create_user();
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @param int $roleid
     * @return seminar
     */
    private function get_facetoface(int $roleid): seminar {
        $f2f = $this->create_facetoface($roleid);

        // Create event here
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($f2f->get_id())->save();

        // Create session dates here
        $time = time() + 3600;
        $session = new seminar_session();
        $session->set_sessionid($seminarevent->get_id())
            ->set_timestart($time)
            ->set_timefinish($time + 7200)
            ->save();

        return $f2f;
    }

    /**
     * @param int $numberofsignups
     * @param seminar $seminar
     * @param int $roleid
     * @param string $state
     * @return array of [ signups, users ]
     */
    private function create_signups(int $numberofsignups, seminar $seminar, int $roleid, string $state): array {
        $users = $this->create_users($numberofsignups);
        $roleusers = $this->create_users($numberofsignups);
        $generator = $this->getDataGenerator();
        /** @var seminar_event $seminarevent */
        $seminarevent = $seminar->get_events()->current();

        $signups = [];
        $allusers = [];
        /** @var stdClass $user */
        foreach ($users as $user) {
            $generator->enrol_user($user->id, $seminar->get_course());
            $roleuser = current($roleusers);
            next($roleusers);
            $role = new role();
            $role->set_sessionid($seminarevent->get_id());
            $role->set_roleid($roleid);
            $role->set_userid($roleuser->id);
            $role->save();
            $signup = new signup();
            $signup->set_sessionid($seminarevent->get_id())->set_userid($user->id);
            $signup->save();
            $signup->switch_state($state);
            $signups[] = $signup;
            $allusers[] = $user->id;
            $allusers[] = $roleuser->id;
        }
        return [$signups, $allusers];
    }

    /**
     * @return array of non-manager approval types
     */
    public function data_approval_types() {
        return [[seminar::APPROVAL_NONE], [seminar::APPROVAL_SELF]];
    }

    /**
     * Make sure the requestedrole state is able to be switched to declined after manager approval is turned off.
     * @dataProvider data_approval_types
     */
    public function test_requested_state_can_be_switched_to_declined($approvaltype) {
        global $DB;
        $this->setAdminUser();
        $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $seminar = $this->get_facetoface($trainerrole->id);
        [$signups, $users] = $this->create_signups(2, $seminar, $trainerrole->id, requestedrole::class);
        $seminar->set_approvaltype($approvaltype)->save();
        /** @var signup $signup */
        foreach ($signups as $signup) {
            // See if all admin, manager, learner and another learner can decline this sign-up.
            $this->setAdminUser();
            $this->assertTrue($signup->can_switch(declined::class));
            foreach ($users as $userid) {
                $this->setUser($userid);
                $this->assertTrue($signup->can_switch(declined::class));
            }
        }
    }
}
