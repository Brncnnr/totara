<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

use core\orm\query\builder;
use mod_facetoface\{attendees_helper, reservations, seminar_event, signup, signup_helper};
use mod_facetoface\signup\state\{attendance_state, booked, requested, requestedadmin, requestedrole, waitlisted};
use mod_facetoface\testing\generator;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests reservation functions
 */
class mod_facetoface_reservation_testcase extends advanced_testcase {
    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp(): void {
        parent::setUp();
        set_config('facetoface_allow_legacy_notifications', 1);
    }

    /**
     * Check that users deallocated correctly
     */
    public function test_facetoface_remove_allocations() {
        $manager = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        /** @var generator $facetofacegenerator */
        $facetofacegenerator = generator::instance();
        $facetoface = $facetofacegenerator->create_instance(array(
            'course' => $course->id,
            'multiplesessions' => 1,
            'managerreserve' => 1,
            'maxmanagerreserves' => 2
        ));
        // Create session.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 5,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $sessiondata['datetimeknown'] = '1';
        $seminarevent = new \mod_facetoface\seminar_event($sessionid);
        $seminar = $seminarevent->get_seminar();

        // Allocate to session by manager.
        $this->setUser($manager);
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($user1->id, $seminarevent));
        \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($user2->id, $seminarevent));

        $this->executeAdhocTasks();
        $sink = $this->redirectMessages();
        \mod_facetoface\reservations::remove_allocations($seminarevent, $seminar, array($user1->id), true, $manager->id);
        $this->executeAdhocTasks();
        $this->assertSame(1, $sink->count());
        $messages = $sink->get_messages();
        $sink->clear();

        $this->assertStringContainsString('BOOKING CANCELLED', $messages[0]->fullmessage);
        $this->assertEquals($user1->id, $messages[0]->useridto);

        $sink = $this->redirectMessages();
        \mod_facetoface\reservations::remove_allocations($seminarevent, $seminar, array($user2->id), false, $manager->id);
        $this->executeAdhocTasks();
        $this->assertSame(1, $sink->count());
        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertStringContainsString('BOOKING CANCELLED', $messages[0]->fullmessage);
        $this->assertEquals($user2->id, $messages[0]->useridto);
    }

    /**
     * @return array
     */
    public function data_closed_registrationtimeset() {
        $now = time();
        $monthsecs = 30 * DAYSECS;
        return [
            [$now + $monthsecs, 0],
            [0, $now - $monthsecs * 3],
            [$now - $monthsecs * 5, $now - $monthsecs * 4],
            [$now + $monthsecs * 6, $now + $monthsecs * 7]
        ];
    }

    /**
     * @param integer $registrationtimestart
     * @param integer $registrationtimefinish
     * @dataProvider data_closed_registrationtimeset
     */
    public function test_make_reservation_when_registration_is_closed($registrationtimestart, $registrationtimefinish) {
        $gen = $this->getDataGenerator();
        $manager = $gen->create_user();
        $staff = $gen->create_user();
        $student1 = $gen->create_user();
        $student2 = $gen->create_user();
        $student3 = $gen->create_user();
        $course = $gen->create_course();

        $managerja = job_assignment::create_default($manager->id);
        job_assignment::create_default($student1->id, ['managerjaid' => $managerja->id]);
        job_assignment::create_default($student2->id, ['managerjaid' => $managerja->id]);
        $staffja = job_assignment::create_default($staff->id);
        job_assignment::create_default($student1->id, ['managerjaid' => $staffja->id]);
        job_assignment::create_default($student2->id, ['managerjaid' => $staffja->id]);

        $gen->enrol_user($student1->id, $course->id);
        $gen->enrol_user($student3->id, $course->id);
        $gen->enrol_user($manager->id, $course->id, 'manager');
        $gen->enrol_user($staff->id, $course->id, 'staffmanager');

        /** @var generator $f2fgen */
        $f2fgen = generator::instance();
        $f2f = $f2fgen->create_instance([
            'course' => $course->id,
            'managerreserve' => 1,
            'maxmanagerreserves' => 2
        ]);
        $sessionid = $f2fgen->add_session([
            'facetoface' => $f2f->id,
            'sessiondates' => [time() + YEARSECS],
            'registrationtimestart' => $registrationtimestart,
            'registrationtimefinish' => $registrationtimefinish
        ]);

        $seminarevent = new seminar_event($sessionid);
        $signup1by1 = signup::create($student1->id, $seminarevent)->set_actorid($student1->id);
        $signup2by2 = signup::create($student2->id, $seminarevent)->set_actorid($student2->id);
        $signup3by3 = signup::create($student3->id, $seminarevent)->set_actorid($student3->id);
        foreach ([booked::class, waitlisted::class, requested::class, requestedrole::class, requestedadmin::class] as $stateclass) {
            $this->setUser($student1);
            $this->assertFalse($signup1by1->can_switch($stateclass), '#1 should not be able to switch to '.$stateclass);
            $this->setUser($student2);
            $this->assertFalse($signup2by2->can_switch($stateclass), '#2 should not be able to switch to '.$stateclass);
            $this->setUser($student3);
            $this->assertFalse($signup3by3->can_switch($stateclass), '#3 should not be able to switch to '.$stateclass);
        }

        $this->setUser($manager);
        $reservebymanager = signup::create(0, $seminarevent)->set_bookedby($manager->id);
        $this->assertTrue($reservebymanager->can_switch(booked::class), 'manager should be able to switch to '.booked::class);
        $this->assertFalse($reservebymanager->can_switch(waitlisted::class), 'manager should not be able to switch to '.waitlisted::class);

        $this->setUser($staff);
        $reservebystaff = signup::create(0, $seminarevent)->set_bookedby($staff->id);
        foreach ([booked::class, waitlisted::class] as $stateclass) {
            $this->assertFalse($reservebystaff->can_switch($stateclass), 'staff should not be able to switch to '.$stateclass);
        }
    }

    /**
     * @param integer $sessionid
     * @param integer[] $codes
     * @return builder
     */
    private static function attendees_builder_with_codes(int $sessionid, array $codes) {
        return builder::table('facetoface_signups', 'su')
            ->join(['facetoface_signups_status', 'sus'], 'id', '=', 'signupid', 'inner')
            ->where('su.sessionid', $sessionid)
            ->where('sus.superceded', 0)
            ->where('sus.statuscode', '=', $codes);
    }

    /**
     * Test current signup records including reservations against expected outcomes.
     * @param integer $sessionid
     * @param stdClass[] $expected
     */
    private static function assert_signups_of_event(int $sessionid, array $expected): void {
        $statcodes = attendance_state::get_all_attendance_code_with([booked::class, waitlisted::class]);
        $signups = self::attendees_builder_with_codes($sessionid, $statcodes)
            ->select(['su.id', 'su.userid', 'sus.statuscode'])
            ->order_by('su.id')
            ->get()
            ->all(false);
        // remove id
        $signups = array_map(function ($signup) {
            unset($signup->id);
            return $signup;
        }, $signups);
        self::assertEquals($expected, $signups);
    }

    public function test_make_reservation_in_overbooked_event() {
        $gen = $this->getDataGenerator();
        $manager = $gen->create_user();
        $student1 = $gen->create_user();
        $student2 = $gen->create_user();
        $student3 = $gen->create_user();
        $course = $gen->create_course();

        $managerja = job_assignment::create_default($manager->id);
        job_assignment::create_default($student1->id, ['managerjaid' => $managerja->id]);
        job_assignment::create_default($student2->id, ['managerjaid' => $managerja->id]);

        $gen->enrol_user($student1->id, $course->id);
        $gen->enrol_user($student3->id, $course->id);
        $gen->enrol_user($manager->id, $course->id, 'manager');

        /** @var generator $f2fgen */
        $f2fgen = generator::instance();
        $f2f = $f2fgen->create_instance([
            'course' => $course->id,
            'managerreserve' => 1,
            'maxmanagerreserves' => 2
        ]);
        $sessionid = $f2fgen->add_session([
            'facetoface' => $f2f->id,
            'capacity' => 2,
            'allowoverbook' => 1,
            'sessiondates' => [time() + YEARSECS]
        ]);

        $seminarevent = new seminar_event($sessionid);
        $helper = new attendees_helper($seminarevent);
        $bookedstatcodes = attendance_state::get_all_attendance_code_with([booked::class]);
        $waitliststatcodes = [waitlisted::get_code()];
        $this->setUser($manager);

        // Add student3
        $signup3 = signup_helper::signup(signup::create($student3->id, $seminarevent));
        $this->assertInstanceOf(booked::class, $signup3->get_state());
        $this->assertEquals(1, self::attendees_builder_with_codes($sessionid, $bookedstatcodes)->count());
        $this->assertEquals(0, self::attendees_builder_with_codes($sessionid, $waitliststatcodes)->count());
        $this->assertEquals(1, $helper->count_attendees_with_codes($bookedstatcodes, false, true));
        $this->assertEquals(1, $helper->count_attendees_with_codes($bookedstatcodes, false, false));
        $this->assertEquals(0, $helper->count_attendees_with_codes($waitliststatcodes, false, true));
        $this->assertEquals(0, $helper->count_attendees_with_codes($waitliststatcodes, false, false));

        // Make two reservations
        reservations::add($seminarevent, $manager->id, 0, 2);
        $this->assertEquals(2, self::attendees_builder_with_codes($sessionid, $bookedstatcodes)->count());
        $this->assertEquals(1, self::attendees_builder_with_codes($sessionid, $waitliststatcodes)->count());
        $this->assertEquals(2, $helper->count_attendees_with_codes($bookedstatcodes, false, true));
        $this->assertEquals(1, $helper->count_attendees_with_codes($bookedstatcodes, false, false));
        $this->assertEquals(1, $helper->count_attendees_with_codes($waitliststatcodes, false, true));
        $this->assertEquals(0, $helper->count_attendees_with_codes($waitliststatcodes, false, false));

        $this->waitForSecond();

        // Add student1
        $signup1 = signup_helper::signup(signup::create($student1->id, $seminarevent));
        $this->assertInstanceOf(waitlisted::class, $signup1->get_state());
        $this->assertEquals(2, self::attendees_builder_with_codes($sessionid, $bookedstatcodes)->count());
        $this->assertEquals(2, self::attendees_builder_with_codes($sessionid, $waitliststatcodes)->count());
        $this->assertEquals(2, $helper->count_attendees_with_codes($bookedstatcodes, false, true));
        $this->assertEquals(1, $helper->count_attendees_with_codes($bookedstatcodes, false, false));
        $this->assertEquals(2, $helper->count_attendees_with_codes($waitliststatcodes, false, true));
        $this->assertEquals(1, $helper->count_attendees_with_codes($waitliststatcodes, false, false));

        self::assert_signups_of_event($sessionid, [
            (object)['userid' => $student3->id, 'statuscode' => booked::get_code()],
            (object)['userid' => 0, 'statuscode' => booked::get_code()],
            (object)['userid' => 0, 'statuscode' => waitlisted::get_code()],
            (object)['userid' => $student1->id, 'statuscode' => waitlisted::get_code()],
        ]);

        $reservedsignupstats = builder::table('facetoface_signups_status', 'sus')
            ->join(['facetoface_signups', 'su'], 'signupid', '=', 'id', 'inner')
            ->where('su.sessionid', $sessionid)
            ->where('su.userid', '=', 0)
            ->select(['sus.id', 'sus.statuscode'])
            ->order_by('su.id')
            ->get()
            ->all();

        $this->assertCount(2, $reservedsignupstats);
        $this->assertEquals(booked::get_code(), $reservedsignupstats[0]->statuscode);
        $this->assertEquals(waitlisted::get_code(), $reservedsignupstats[1]->statuscode);

        // Remove student3
        signup_helper::user_cancel(signup::create($student3->id, $seminarevent));
        self::assert_signups_of_event($sessionid, [
            (object)['userid' => 0, 'statuscode' => booked::get_code()],
            (object)['userid' => 0, 'statuscode' => booked::get_code()],
            (object)['userid' => $student1->id, 'statuscode' => waitlisted::get_code()],
        ]);

        // Remove one reservation
        reservations::remove($seminarevent, $manager->id, 1);
        self::assert_signups_of_event($sessionid, [
            (object)['userid' => 0, 'statuscode' => booked::get_code()],
            (object)['userid' => $student1->id, 'statuscode' => booked::get_code()],
        ]);
    }

    public function test_notifications_after_remove() {
        $gen = $this->getDataGenerator();
        $manager_id = $gen->create_user()->id;
        $course_id = $gen->create_course()->id;

        $gen->enrol_user($manager_id, $course_id, 'manager');

        $f2fgen = generator::instance();
        $f2f = $f2fgen->create_instance([
            'course' => $course_id,
            'managerreserve' => 1,
            'maxmanagerreserves' => 5
        ]);
        $sessionid = $f2fgen->add_session([
            'facetoface' => $f2f->id,
            'capacity' => 200,
            'allowoverbook' => 1,
            'sessiondates' => [time() + YEARSECS]
        ]);

        $seminarevent = new seminar_event($sessionid);

        $this->setUser($manager_id);
        reservations::add($seminarevent, $manager_id, 2, 0);

        $reservation = (object)['userid' => 0, 'statuscode' => booked::get_code()];
        self::assert_signups_of_event($sessionid, [$reservation, $reservation]);

        // By default if the manager is the one cancelling reservations, he does
        // not need a notification to tell him he did so.
        $sink = $this->redirectEmails();
        reservations::remove($seminarevent, $manager_id, null);
        $this->executeAdhocTasks();

        self::assert_signups_of_event($sessionid, []);

        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);

        reservations::add($seminarevent, $manager_id, 3, 0);
        self::assert_signups_of_event($sessionid, [$reservation, $reservation, $reservation]);

        $this->setAdminUser();

        // But notifications could be forced if the manager is not the one cancelling.
        $sink = $this->redirectEmails();
        reservations::remove($seminarevent, $manager_id, null, true);
        $this->executeAdhocTasks();

        self::assert_signups_of_event($sessionid, []);

        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
    }
}
