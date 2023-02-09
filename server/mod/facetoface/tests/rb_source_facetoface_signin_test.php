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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 * @category test
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_rb_source_facetoface_signin_testcase extends advanced_testcase {
    public function test_get_custom_export_header_with_multiple_rooms() {
        $time = time();
        $gen = $this->getDataGenerator();
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $cusgen = $gen->get_plugin_generator('totara_customfield');

        $user1 = $gen->create_user();
        $course = $gen->create_course();
        $f2f = $f2fgen->create_instance(['course' => $course->id]);
        $seminar = new \mod_facetoface\seminar($f2f->id);
        $event = (new \mod_facetoface\seminar_event())
            ->set_facetoface($seminar->get_id());
        $event->save();
        $session = (new \mod_facetoface\seminar_session())
            ->set_sessionid($event->get_id())
            ->set_timestart($time + DAYSECS)->set_timefinish($time + DAYSECS + HOURSECS);
        $session->save();
        $room1 = (new \mod_facetoface\room())
            ->set_name('Big room')
            ->set_capacity(100);
        $room1->save();
        $room2 = (new \mod_facetoface\room())
            ->set_name('Small room')
            ->set_capacity(10);
        $room2->save();
        $room3 = (new \mod_facetoface\room())
            ->set_name('Video conference')
            ->set_capacity(200)
            ->set_url('https://totara.example.com/123456');
        $room3->save();
        $room1 = $room1->to_record();
        $room2 = $room2->to_record();
        $room3 = $room3->to_record();

        $cfids = [];
        foreach (customfield_get_fields_definition('facetoface_room') as $cfdef) {
            $cfids[$cfdef->shortname] = $cfdef->id;
        }
        $cusgen->set_text($room1, $cfids['building'], 'Giant Building', 'facetofaceroom', 'facetoface_room');
        $cusgen->set_location_address($room1, $cfids['location'], '123 Here Street', 'facetofaceroom', 'facetoface_room');
        $cusgen->set_text($room2, $cfids['building'], 'Tiny Building', 'facetofaceroom', 'facetoface_room');
        $cusgen->set_location_address($room2, $cfids['location'], '456 There Street', 'facetofaceroom', 'facetoface_room');

        \mod_facetoface\room_helper::sync($session->get_id(), [$room1->id, $room2->id, $room3->id]);

        $gen->enrol_user($user1->id, $course->id);
        $signup = \mod_facetoface\signup::create($user1->id, $event)
            ->save()->switch_state(\mod_facetoface\signup\state\booked::class);

        self::setAdminUser();
        $shortname = 'facetoface_signin';
        $reportrecord = \core\orm\query\sql\sql::get_db()->get_record('report_builder', array('shortname' => $shortname));
        $globalrestrictionset = \rb_global_restriction_set::create_from_page_parameters($reportrecord);

        $reportparams = array(
            'facetofaceid' => $seminar->get_id(),
            'sessionid' => $event->get_id(),
            'sessiondateid' => $session->get_id(),
        );
        $config = (new rb_config())->set_global_restriction_set($globalrestrictionset)->set_embeddata($reportparams);
        $report = reportbuilder::create_embedded($shortname, $config);

        // Just export the sign-in sheet as all possible file format.
        $sched = (object)['userid' => 2]; // admin
        $filename = reportbuilder_export_schduled_report($sched, $report, \tabexport_excel\writer::class);
        $this->assertFileExists($filename);
        @unlink($filename);

        $filename = reportbuilder_export_schduled_report($sched, $report, \tabexport_csv\writer::class);
        $this->assertFileExists($filename);
        @unlink($filename);

        $filename = reportbuilder_export_schduled_report($sched, $report, \tabexport_ods\writer::class);
        $this->assertFileExists($filename);
        @unlink($filename);

        $filename = reportbuilder_export_schduled_report($sched, $report, \tabexport_pdflandscape\writer::class);
        $this->assertFileExists($filename);
        @unlink($filename);

        $filename = reportbuilder_export_schduled_report($sched, $report, \tabexport_pdfportrait\writer::class);
        $this->assertFileExists($filename);
        @unlink($filename);
    }
}
