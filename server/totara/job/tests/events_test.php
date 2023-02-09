<?php
/*
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_job
 */

use core\event\base;
use core_phpunit\testcase;
use totara_job\event\job_assignment_created;
use totara_job\event\job_assignment_deleted;
use totara_job\event\job_assignment_updated;
use totara_job\job_assignment;

class totara_job_events_testcase extends testcase {

    public function test_created_event(): void {
        self::setAdminUser();
        [
            $user,
            $manager,
            $temp_manager,
            $appraiser,
       ] = $this->create_users(4);

        /** @var job_assignment $manager_ja */
        $manager_ja = job_assignment::create_default($manager->id);
        /** @var job_assignment $temp_manager_ja */
        $temp_manager_ja = job_assignment::create_default($temp_manager->id);

        $event_sink = self::redirectEvents();
        self::assertEquals(0, $event_sink->count());

        // Create job assignment
        /** @var job_assignment $ja */
        $ja = job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'test user ja',
            'managerjaid' => $manager_ja->id,
            'positionid' => '1111',
            'organisationid' => '2222',
            'appraiserid' => $appraiser->id,
            'tempmanagerjaid' => $temp_manager_ja->id,
            'tempmanagerexpirydate' => time() + WEEKSECS,
        ]);

        $events = array_filter($event_sink->get_events(), static function (base $event) {
            return $event instanceof job_assignment_created;
        });
        self::assertCount(1, $events);
        $event = reset($events);

        $event_data = $event->get_data();
        self::assertEquals($ja->id, $event_data['objectid']);
        self::assertEquals(context_system::instance()->id, $event_data['contextid']);
        self::assertEquals($ja->userid, $event_data['relateduserid']);
        self::assertEqualsCanonicalizing([
            'oldmanagerjaid' => null,
            'oldmanagerjapath' => $ja->managerjapath,
            'oldpositionid' => null,
            'oldorganisationid' => null,
            'oldappraiserid' => null,
            'oldtempmanagerjaid' => null,
            'newmanagerjaid' => $manager_ja->id,
            'newmanagerjapath' => $ja->managerjapath,
            'newpositionid' => '1111',
            'neworganisationid' => '2222',
            'newappraiserid' => $appraiser->id,
            'newtempmanagerjaid' => $temp_manager_ja->id,
        ], $event_data['other']);
    }

    public function test_updated_event(): void {
        self::setAdminUser();

        [
            $user,
            $manager1,
            $manager2,
            $temp_manager1,
            $temp_manager2,
            $appraiser1,
            $appraiser2,
        ] = $this->create_users(7);

        /** @var job_assignment $manager1_ja */
        $manager1_ja = job_assignment::create_default($manager1->id);
        /** @var job_assignment $manager2_ja */
        $manager2_ja = job_assignment::create_default($manager2->id);
        /** @var job_assignment $temp_manager1_ja */
        $temp_manager1_ja = job_assignment::create_default($temp_manager1->id);
        /** @var job_assignment $temp_manager2_ja */
        $temp_manager2_ja = job_assignment::create_default($temp_manager2->id);

        // Create job assignment
        /** @var job_assignment $ja */
        $ja = job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'test user ja',
            'managerjaid' => $manager1_ja->id,
            'positionid' => '1111',
            'organisationid' => '2222',
            'appraiserid' => $appraiser1->id,
            'tempmanagerjaid' => $temp_manager1_ja->id,
            'tempmanagerexpirydate' => time() + WEEKSECS,
        ]);

        $event_sink = self::redirectEvents();
        self::assertEquals(0, $event_sink->count());

        // Update job assignment
        $old_manager_ja_path = $ja->managerjapath;
        $ja->update(
            [
                'managerjaid' => $manager2_ja->id,
                'positionid' => '8888',
                'organisationid' => '9999',
                'appraiserid' => $appraiser2->id,
                'tempmanagerjaid' => $temp_manager2_ja->id,
                'tempmanagerexpirydate' => time() + WEEKSECS,
            ]
        );

        self::assertNotEquals($old_manager_ja_path, $ja->managerjapath);

        $events = array_filter($event_sink->get_events(), static function (base $event) {
            return $event instanceof job_assignment_updated;
        });
        self::assertCount(1, $events);
        $event = reset($events);

        $event_data = $event->get_data();
        self::assertEquals($ja->id, $event_data['objectid']);
        self::assertEquals(context_system::instance()->id, $event_data['contextid']);
        self::assertEquals($ja->userid, $event_data['relateduserid']);
        self::assertEqualsCanonicalizing([
            'oldmanagerjaid' => $manager1_ja->id,
            'oldmanagerjapath' => $old_manager_ja_path,
            'oldpositionid' => '1111',
            'oldorganisationid' => '2222',
            'oldappraiserid' => $appraiser1->id,
            'oldtempmanagerjaid' => $temp_manager1_ja->id,
            'newmanagerjaid' => $manager2_ja->id,
            'newmanagerjapath' => $ja->managerjapath,
            'newpositionid' => '8888',
            'neworganisationid' => '9999',
            'newappraiserid' => $appraiser2->id,
            'newtempmanagerjaid' => $temp_manager2_ja->id,
        ], $event_data['other']);
    }

    /**
     * job_assignment::update_to_empty_by_criteria() triggers the 'updated' event separately from the usual update()
     * method, so we have to test it separately as well to make sure it includes the right data.
     */
    public function test_update_to_empty_by_criteria(): void {
        self::setAdminUser();

        [
            $user,
            $manager1,
            $temp_manager1,
            $appraiser1,
        ] = $this->create_users(4);

        /** @var job_assignment $manager1_ja */
        $manager1_ja = job_assignment::create_default($manager1->id);
        /** @var job_assignment $temp_manager1_ja */
        $temp_manager1_ja = job_assignment::create_default($temp_manager1->id);

        // Create job assignment
        /** @var job_assignment $ja */
        $ja = job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'test user ja',
            'managerjaid' => $manager1_ja->id,
            'positionid' => '1111',
            'organisationid' => '2222',
            'appraiserid' => $appraiser1->id,
            'tempmanagerjaid' => $temp_manager1_ja->id,
            'tempmanagerexpirydate' => time() + WEEKSECS,
        ]);

        $event_sink = self::redirectEvents();
        self::assertEquals(0, $event_sink->count());

        job_assignment::update_to_empty_by_criteria('appraiserid', $appraiser1->id);

        $events = array_filter($event_sink->get_events(), static function (base $event) {
            return $event instanceof job_assignment_updated;
        });
        self::assertCount(1, $events);
        $event = reset($events);

        $event_data = $event->get_data();
        self::assertEquals($ja->id, $event_data['objectid']);
        self::assertEquals(context_system::instance()->id, $event_data['contextid']);
        self::assertEquals($ja->userid, $event_data['relateduserid']);
        self::assertEqualsCanonicalizing([
            'oldmanagerjaid' => $manager1_ja->id,
            'oldmanagerjapath' => $ja->managerjapath,
            'oldpositionid' => '1111',
            'oldorganisationid' => '2222',
            'oldappraiserid' => $appraiser1->id,
            'oldtempmanagerjaid' => $temp_manager1_ja->id,
            'newmanagerjaid' => $manager1_ja->id,
            'newmanagerjapath' => $ja->managerjapath,
            'newpositionid' => '1111',
            'neworganisationid' => '2222',
            'newappraiserid' => null,
            'newtempmanagerjaid' => $temp_manager1_ja->id,
        ], $event_data['other']);
    }

    public function test_deleted(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();

        [
            $user,
            $manager1,
            $temp_manager1,
            $appraiser1,
        ] = $this->create_users(4);

        /** @var job_assignment $manager1_ja */
        $manager1_ja = job_assignment::create_default($manager1->id);
        /** @var job_assignment $temp_manager1_ja */
        $temp_manager1_ja = job_assignment::create_default($temp_manager1->id);

        // Create job assignment
        /** @var job_assignment $ja */
        $ja = job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'test user ja',
            'managerjaid' => $manager1_ja->id,
            'positionid' => '1111',
            'organisationid' => '2222',
            'appraiserid' => $appraiser1->id,
            'tempmanagerjaid' => $temp_manager1_ja->id,
            'tempmanagerexpirydate' => time() + WEEKSECS,
        ]);

        $event_sink = self::redirectEvents();
        self::assertEquals(0, $event_sink->count());

        // Create a clone because $ja is destroyed when passed in to delete() method.
        $ja_clone = clone $ja;

        job_assignment::delete($ja);
        $events = array_filter($event_sink->get_events(), static function(base $event) {
            return $event instanceof job_assignment_deleted;
        });
        self::assertCount(1, $events);

        $event = reset($events);

        $event_data = $event->get_data();
        self::assertEquals($ja_clone->id, $event_data['objectid']);
        self::assertEquals(context_system::instance()->id, $event_data['contextid']);
        self::assertEquals($ja_clone->userid, $event_data['relateduserid']);
        self::assertEqualsCanonicalizing([
            'oldmanagerjaid' => $manager1_ja->id,
            'oldmanagerjapath' => $ja_clone->managerjapath,
            'oldpositionid' => '1111',
            'oldorganisationid' => '2222',
            'oldappraiserid' => $appraiser1->id,
            'oldtempmanagerjaid' => $temp_manager1_ja->id,
            'newmanagerjaid' => null,
            'newmanagerjapath' => null,
            'newpositionid' => null,
            'neworganisationid' => null,
            'newappraiserid' => null,
            'newtempmanagerjaid' => null,
        ], $event_data['other']);
    }

    /**
     * @param int $number_of_users
     * @return array
     */
    private function create_users(int $number_of_users): array {
        $users = [];
        for ($i = 0; $i < $number_of_users; $i ++) {
            $users[] = self::getDataGenerator()->create_user();
        }
        return $users;
    }
}
