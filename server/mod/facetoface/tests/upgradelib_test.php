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
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_helper;
use mod_facetoface\seminar;
use mod_facetoface\signup;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\signup_helper;
use mod_facetoface\signup\state\requestedrole;
use mod_facetoface\signup\state\requested;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\fully_attended;
use mod_facetoface\signup\state\partially_attended;
use mod_facetoface\signup\state\no_show;
use mod_facetoface\signup\state\unable_to_attend;
use totara_core\http\clients\simple_mock_client;
use totara_core\virtualmeeting\virtual_meeting as virtual_meeting_model;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

/**
 * Test facetoface upgradelib related functions
 */
class mod_facetoface_upgradelib_testcase extends advanced_testcase {
    /**
     * @group virtualmeeting
     */
    public function test_facetoface_upgradelib_upgrade_existing_virtual_meetings(): void {
        /** @var mod_facetoface_generator */
        $f2fgen = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $user = $this->getDataGenerator()->create_user();
        // sitewide phys
        // sitewide vc
        // ad-hoc phys
        // ad-hoc vc
        // ad-hoc vm status = null, vm = null
        // ad-hoc vm status = null, vm != null
        // ad-hoc vm status != null, vm = null
        // ad-hoc vm status != null, vm != null
        $course = $this->getDataGenerator()->create_course();
        $seminar = new seminar();
        $seminar->set_course($course->id)->save();
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($seminar->get_id())->save();
        $seminarsession = new seminar_session();
        $seminarsession->set_sessionid($seminarevent->get_id())->set_timestart(time() + HOURSECS)->set_timefinish(time() + HOURSECS * 2)->set_sessiontimezone('99')->save();
        $room_sitewide_physical = (new room())->from_record($f2fgen->add_site_wide_room(['name' => 'virtual class room']));
        $room_sitewide_custom = (new room())->from_record($f2fgen->add_site_wide_room(['name' => 'virtual class room', 'url' => 'https://example.com?q=kia+ora#koutou']));
        $room_adhoc_physical = (new room())->from_record($f2fgen->add_custom_room(['name' => 'virtual class room']));
        $room_adhoc_custom = (new room())->from_record($f2fgen->add_custom_room(['name' => 'virtual class room', 'url' => 'https://example.com?q=kia+ora#koutou']));
        $room_virtual_no_date = $this->add_virtualmeeting('virtual meeting room', $seminarsession, $user->id, false, false, -42);
        $room_legacy_no_vm = $this->add_virtualmeeting('virtual meeting room', $seminarsession, $user->id, true, false, room_dates_virtualmeeting::STATUS_LEGACY);
        $room_legacy_vm = $this->add_virtualmeeting('virtual meeting room', $seminarsession, $user->id, true, true, room_dates_virtualmeeting::STATUS_LEGACY);
        $room_available_no_vm = $this->add_virtualmeeting('virtual meeting room', $seminarsession, $user->id, true, false, room_dates_virtualmeeting::STATUS_AVAILABLE);
        $room_available_vm = $this->add_virtualmeeting('virtual meeting room', $seminarsession, $user->id, true, true, room_dates_virtualmeeting::STATUS_AVAILABLE);
        $room_unavailable_no_vm = $this->add_virtualmeeting('virtual meeting room', $seminarsession, $user->id, true, false, room_dates_virtualmeeting::STATUS_UNAVAILABLE);
        $room_unavailable_vm = $this->add_virtualmeeting('virtual meeting room', $seminarsession, $user->id, true, true, room_dates_virtualmeeting::STATUS_UNAVAILABLE);
        room_helper::sync(
            $seminarsession->get_id(),
            [
                $room_sitewide_physical->get_id(),
                $room_sitewide_custom->get_id(),
                $room_adhoc_physical->get_id(),
                $room_adhoc_custom->get_id(),
                $room_virtual_no_date->get_id(),
                $room_legacy_no_vm->get_id(),
                $room_legacy_vm->get_id(),
                $room_available_no_vm->get_id(),
                $room_available_vm->get_id(),
                $room_unavailable_no_vm->get_id(),
                $room_unavailable_vm->get_id(),
            ]
        );

        [$room_deleting_no_vm, $roomdate_deleting_no_vm1] = $this->add_orphaned_virtualmeeting('virtual meeting room', $seminarsession, $user->id, false, room_dates_virtualmeeting::STATUS_LEGACY);
        [$room_deleting_vm, $roomdate_deleting_vm1] = $this->add_orphaned_virtualmeeting('Virtual meeting room', $seminarsession, $user->id, true, room_dates_virtualmeeting::STATUS_LEGACY);

        $roomdate_sitewide_physical1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_sitewide_physical);
        $roomdate_sitewide_custom1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_sitewide_custom);
        $roomdate_adhoc_physical1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_adhoc_physical);
        $roomdate_adhoc_custom1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_adhoc_custom);
        $roomdate_virtual_no_date1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_virtual_no_date);
        $roomdate_legacy_no_vm1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_legacy_no_vm);
        $roomdate_legacy_vm1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_legacy_vm);
        $roomdate_available_no_vm1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_available_no_vm);
        $roomdate_available_vm1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_available_vm);
        $roomdate_unavailable_no_vm1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_unavailable_no_vm);
        $roomdate_unavailable_vm1 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_unavailable_vm);
        $this->assertFalse($roomdate_sitewide_physical1->exists());
        $this->assertFalse($roomdate_sitewide_custom1->exists());
        $this->assertFalse($roomdate_adhoc_physical1->exists());
        $this->assertFalse($roomdate_adhoc_custom1->exists());
        $this->assertFalse($roomdate_virtual_no_date1->exists());
        $this->assertTrue($roomdate_legacy_no_vm1->exists());
        $this->assertTrue($roomdate_legacy_vm1->exists());
        $this->assertTrue($roomdate_available_no_vm1->exists());
        $this->assertTrue($roomdate_available_vm1->exists());
        $this->assertTrue($roomdate_unavailable_no_vm1->exists());
        $this->assertTrue($roomdate_unavailable_vm1->exists());
        $this->assertTrue($roomdate_deleting_no_vm1->exists());
        $this->assertTrue($roomdate_deleting_vm1->exists());

        $this->assertSame(room_dates_virtualmeeting::STATUS_LEGACY, $roomdate_legacy_no_vm1->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_LEGACY, $roomdate_legacy_vm1->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_AVAILABLE, $roomdate_available_no_vm1->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_AVAILABLE, $roomdate_available_vm1->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_UNAVAILABLE, $roomdate_unavailable_no_vm1->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_UNAVAILABLE, $roomdate_unavailable_vm1->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_LEGACY, $roomdate_deleting_no_vm1->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_LEGACY, $roomdate_deleting_vm1->get_status());

        $this->assertNull($roomdate_legacy_no_vm1->get_virtualmeetingid());
        $this->assertNotNull($roomdate_legacy_vm1->get_virtualmeetingid());
        $this->assertNull($roomdate_available_no_vm1->get_virtualmeetingid());
        $this->assertNotNull($roomdate_available_vm1->get_virtualmeetingid());
        $this->assertNull($roomdate_unavailable_no_vm1->get_virtualmeetingid());
        $this->assertNotNull($roomdate_unavailable_vm1->get_virtualmeetingid());
        $this->assertNull($roomdate_deleting_no_vm1->get_virtualmeetingid());
        $this->assertNotNull($roomdate_deleting_vm1->get_virtualmeetingid());

        facetoface_upgradelib_upgrade_existing_virtual_meetings();

        $roomdate_sitewide_physical2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_sitewide_physical);
        $roomdate_sitewide_custom2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_sitewide_custom);
        $roomdate_adhoc_physical2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_adhoc_physical);
        $roomdate_adhoc_custom2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_adhoc_custom);
        $roomdate_virtual_no_date2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_virtual_no_date);
        $roomdate_legacy_no_vm2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_legacy_no_vm);
        $roomdate_legacy_vm2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_legacy_vm);
        $roomdate_available_no_vm2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_available_no_vm);
        $roomdate_available_vm2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_available_vm);
        $roomdate_unavailable_no_vm2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_unavailable_no_vm);
        $roomdate_unavailable_vm2 = room_dates_virtualmeeting::load_by_session_room($seminarsession, $room_unavailable_vm);
        $roomdate_deleting_no_vm2 = new room_dates_virtualmeeting($roomdate_deleting_no_vm1->get_id());
        $roomdate_deleting_vm2 = new room_dates_virtualmeeting($roomdate_deleting_vm1->get_id());

        $this->assertFalse($roomdate_sitewide_physical2->exists());
        $this->assertFalse($roomdate_sitewide_custom2->exists());
        $this->assertFalse($roomdate_adhoc_physical2->exists());
        $this->assertFalse($roomdate_adhoc_custom2->exists());
        $this->assertTrue($roomdate_virtual_no_date2->exists());
        $this->assertTrue($roomdate_legacy_no_vm2->exists());
        $this->assertTrue($roomdate_legacy_vm2->exists());
        $this->assertTrue($roomdate_available_no_vm2->exists());
        $this->assertTrue($roomdate_available_vm2->exists());
        $this->assertTrue($roomdate_unavailable_no_vm2->exists());
        $this->assertTrue($roomdate_unavailable_vm2->exists());
        $this->assertTrue($roomdate_deleting_no_vm2->exists());
        $this->assertTrue($roomdate_deleting_vm2->exists());

        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $roomdate_virtual_no_date2->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $roomdate_legacy_no_vm2->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_AVAILABLE, $roomdate_legacy_vm2->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_AVAILABLE, $roomdate_available_no_vm2->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_AVAILABLE, $roomdate_available_vm2->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_UNAVAILABLE, $roomdate_unavailable_no_vm2->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_UNAVAILABLE, $roomdate_unavailable_vm2->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_DELETION, $roomdate_deleting_no_vm2->get_status());
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_DELETION, $roomdate_deleting_vm2->get_status());

        $this->assertNull($roomdate_virtual_no_date2->get_virtualmeetingid());
        $this->assertNull($roomdate_legacy_no_vm2->get_virtualmeetingid());
        $this->assertSame($roomdate_legacy_vm1->get_virtualmeetingid(), $roomdate_legacy_vm2->get_virtualmeetingid());
        $this->assertNull($roomdate_available_no_vm2->get_virtualmeetingid());
        $this->assertSame($roomdate_available_vm1->get_virtualmeetingid(), $roomdate_available_vm2->get_virtualmeetingid());
        $this->assertNull($roomdate_unavailable_no_vm2->get_virtualmeetingid());
        $this->assertSame($roomdate_unavailable_vm1->get_virtualmeetingid(), $roomdate_unavailable_vm2->get_virtualmeetingid());
        $this->assertNull($roomdate_deleting_no_vm2->get_virtualmeetingid());
        $this->assertSame($roomdate_deleting_vm1->get_virtualmeetingid(), $roomdate_deleting_vm2->get_virtualmeetingid());
    }

    /**
     * Create a virtual meeting in a seminar session.
     *
     * @param string $name
     * @param seminar_session $session
     * @param integer $userid
     * @param boolean $create_roomdate
     * @param boolean $create_virtualmeeting
     * @param integer|null $status
     * @return room
     */
    private function add_virtualmeeting(string $name, seminar_session $session, int $userid, bool $create_roomdate, bool $create_virtualmeeting, ?int $status): room {
        /** @var mod_facetoface_generator */
        $f2fgen = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $room = new room($f2fgen->add_virtualmeeting_room(['name' => $name], ['userid' => $userid, 'plugin' => 'poc_app', 'status' => null])->id);
        if ($create_roomdate) {
            $roomdate_vm = new room_dates_virtualmeeting();
            $roomdate_vm->set_roomid($room->get_id())->set_sessionsdateid($session->get_id());
            if ($create_virtualmeeting) {
                $client = new simple_mock_client();
                $vm = virtual_meeting_model::create('poc_app', $userid, "<POC: $name>", DateTime::createFromFormat('U', $session->get_timestart()), DateTime::createFromFormat('U', $session->get_timefinish()), $client);
                $roomdate_vm->set_virtualmeetingid($vm->id);
            } else {
                $roomdate_vm->set_virtualmeetingid(null);
            }
            if ($status !== null) {
                $roomdate_vm->set_status($status);
            }
            $roomdate_vm->save();
        }
        return $room;
    }

    /**
     * @param string $name
     * @param seminar_session $session
     * @param integer $userid
     * @param boolean $create_virtualmeeting
     * @param integer|null $status
     * @return array
     */
    private function add_orphaned_virtualmeeting(string $name, seminar_session $session, int $userid, bool $create_virtualmeeting, ?int $status): array {
        /** @var mod_facetoface_generator */
        $f2fgen = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $room = new room($f2fgen->add_virtualmeeting_room(['name' => $name], ['userid' => $userid, 'plugin' => 'poc_app', 'status' => null])->id);
        $record = [];
        if ($create_virtualmeeting) {
            $client = new simple_mock_client();
            $vm = virtual_meeting_model::create('poc_app', $userid, "<POC: $name>", DateTime::createFromFormat('U', $session->get_timestart()), DateTime::createFromFormat('U', $session->get_timefinish()), $client);
            $record['virtualmeetingid'] = $vm->id;
        } else {
            $record['virtualmeetingid'] = null;
        }
        if ($status !== null) {
            $record['status'] = $status;
        }
        $id = builder::table(room_dates_virtualmeeting::DBTABLE)->insert($record);
        $roomdate_vm = new room_dates_virtualmeeting($id);
        return [$room, $roomdate_vm];
    }

    public function test_facetoface_upgradelib_migrate_reoportbuilder_date_fields() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/totara/reportbuilder/db/upgradelib.php');

        $this->setAdminUser();
        $this->user = $user = get_admin();

        $report = new stdclass();
        $report->fullname = 'Test Seminar Sign-in';
        $report->shortname = 'f2f_signin';
        $report->source = 'facetoface_signin';
        $report->hidden = 0;
        $report->accessmode = 0;
        $report->contentmode = 0;
        $report->description = '';
        $report->recordsperpage = 40;
        $report->defaultsortcolumn = 'date_sessiondate';
        $report->defaultsortorder = 4;
        $report->embedded = 0;
        $report->id = $DB->insert_record('report_builder', $report);

        $rbcolumn = new stdClass();
        $rbcolumn->reportid = $report->id;
        $rbcolumn->type = 'date';
        $rbcolumn->value = 'sessiondate';
        $rbcolumn->heading = 'Session Start Date';
        $rbcolumn->sortorder = 1;
        $rbcolumn->hidden = 0;
        $rbcolumn->customheading = 1;
        $rbcolumn->id = $DB->insert_record('report_builder_columns', $rbcolumn);

        $contcol = new stdClass();
        $contcol->reportid = $report->id;
        $contcol->type = 'date';
        $contcol->value = 'datefinish';
        $contcol->heading = 'Session Finish Date';
        $contcol->sortorder = 1;
        $contcol->hidden = 0;
        $contcol->customheading = 1;
        $contcol->id = $DB->insert_record('report_builder_columns', $contcol);

        $rbfilter = new stdClass();
        $rbfilter->reportid = $report->id;
        $rbfilter->type = 'date';
        $rbfilter->value = 'sessiondate';
        $rbfilter->filtername = 'Session Start Date';
        $rbfilter->advanced = 0;
        $rbfilter->sortorder = 1;
        $rbfilter->id = $DB->insert_record('report_builder_filters', $rbfilter);

        $contfil = new stdClass();
        $contfil->reportid = $report->id;
        $contfil->type = 'date';
        $contfil->value = 'datefinish';
        $contfil->filtername = 'Session Finish Date';
        $contfil->advanced = 0;
        $contfil->sortorder = 1;
        $contfil->id = $DB->insert_record('report_builder_filters', $contfil);

        $rbsaved = new stdClass();
        $rbsaved->reportid = $report->id;
        $rbsaved->userid = $user->id;
        $rbsaved->name = 'Saved Search';

        $rbsaved->search = serialize(
            array(
                'date-sessiondate' => array(
                    'after' => 0,
                    'before' => 1633950000,
                    'before_applied' => true,
                    'daysafter' => 0,
                    'daysbefore' => 0,
                    'notset' => 0
                )
            )
        );
        $rbsaved->ispublic = 1;
        $rbsaved->id = $DB->insert_record('report_builder_saved', $rbsaved);

        $contsave = new stdClass();
        $contsave->reportid = $report->id;
        $contsave->userid = $user->id;
        $contsave->name = 'Control Saved';
        $contsave->search = serialize(
            array(
                'date-datefinish' => array(
                    'after' => 0,
                    'before' => 1633950000,
                    'before_applied' => true,
                    'daysafter' => 0,
                    'daysbefore' => 0,
                    'notset' => 0
                )
            )
        );
        $contsave->ispublic = 1;
        $contsave->id = $DB->insert_record('report_builder_saved', $contsave);

        $rbgraph = new stdClass();
        $rbgraph->reportid = $report->id;
        $rbgraph->type = 'column';
        $rbgraph->stacked = false;
        $rbgraph->maxrecords = 500;
        $rbgraph->category = 'date-datefinish';
        $rbgraph->series = json_encode(['session-capacity']);
        $rbgraph->settings = '';
        $rbgraph->id = $DB->insert_record('report_builder_graph', $rbgraph);

        /**
         * Now test replace 'sessiondate' with 'sessionstartdate' and 'datefinish' with 'sessionfinishdate' column values
         * for 'rb_source_facetoface_signin' seminar report source
         */
        facetoface_upgradelib_migrate_reoportbuilder_date_fields();

        $rbreport = $DB->get_record('report_builder', array('id' => $report->id));
        $this->assertEquals('date_sessionstartdate', $rbreport->defaultsortcolumn);

        $column = $DB->get_record('report_builder_columns', array('id' => $rbcolumn->id));
        $this->assertEquals('sessionstartdate', $column->value);

        $control = $DB->get_record('report_builder_columns', array('id' => $contcol->id));
        $this->assertEquals('sessionfinishdate', $control->value);

        $filter = $DB->get_record('report_builder_filters', array('id' => $rbfilter->id));
        $this->assertEquals('sessionstartdate', $filter->value);

        $control = $DB->get_record('report_builder_filters', array('id' => $contfil->id));
        $this->assertEquals('sessionfinishdate', $control->value);

        $saved = $DB->get_record('report_builder_saved', array('id' => $rbsaved->id));
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('date-sessionstartdate', $key);
        }

        $control = $DB->get_record('report_builder_saved', array('id' => $contsave->id));
        $search = unserialize($control->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('date-sessionfinishdate', $key);
        }

        $graph = $DB->get_record('report_builder_graph', array('id' => $rbgraph->id));
        $this->assertEquals('date-sessionfinishdate', $graph->category);
    }
}
