<?php
/*
 * This file is part of Totara Learn
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_rb_sessions_attendees_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Create course, seminar and a seminar session and add users to the session.
     */
    public function setUp(): void {
        $this->setAdminUser();
        $time = time();

        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => "test",
            'lastname' => "one"
        ]);

        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => "test",
            'lastname' => "two"
        ]);

        $user3 = $this->getDataGenerator()->create_user([
            'firstname' => "test",
            'lastname' => "three"
        ]);

        $gen = $this->getDataGenerator();
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');

        $course = $gen->create_course();
        $f2f = $f2fgen->create_instance(['course' => $course->id]);
        $seminar = new \mod_facetoface\seminar($f2f->id);

        $seminarevent = new \mod_facetoface\seminar_event();
        $seminarevent->set_facetoface($seminar->get_id());
        $seminarevent->set_capacity(1);
        $seminarevent->set_allowcancellations(1);
        $seminarevent->set_allowoverbook(1);
        $seminarevent->save();

        $seminarsession = new \mod_facetoface\seminar_session();
        $seminarsession->set_sessionid($seminarevent->get_id());
        $seminarsession->set_timestart($time + 100);
        $seminarsession->set_timefinish($time + 200);
        $seminarsession->save();

        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);

        $signup = \mod_facetoface\signup::create($user1->id, $seminarevent);
        $signup->save();
        $signup->switch_state(\mod_facetoface\signup\state\booked::class);

        $signup = \mod_facetoface\signup::create($user2->id, $seminarevent);
        $signup->save();
        $signup->switch_state(\mod_facetoface\signup\state\waitlisted::class);

        $signup = \mod_facetoface\signup::create($user3->id, $seminarevent);
        $signup->save();
        $signup->switch_state(\mod_facetoface\signup\state\waitlisted::class);
        $signup->switch_state(\mod_facetoface\signup\state\user_cancelled::class);
    }

    /**
     * Add a filter to the attendees embedded report.
     */
    public function test_attendees_filter() {
        global $DB;
        $shortname = 'facetoface_sessions';

        // Create report record.
        $reportbuilder = new \stdClass();
        $reportbuilder->fullname = 'Seminars: Event attendees';
        $reportbuilder->shortname = $shortname;
        $reportbuilder->source = 'facetoface_sessions';
        $reportbuilder->hidden = 0;
        $reportbuilder->recordsperpage = 40;
        $reportbuilder->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        $reportbuilder->embedded = 1;
        $reportbuilder->showtotalcount = 0;
        $reportbuilder->id = $DB->insert_record('report_builder', $reportbuilder);

        // Set up access permissions.
        reportbuilder_set_default_access($reportbuilder->id);

        // Create username filter record.
        $filter = new stdClass();
        $filter->reportid = $reportbuilder->id;
        $filter->advanced = 0;
        $filter->region = 0;
        $filter->type = 'user';
        $filter->value = 'username';
        $filter->filtername = 'Username';
        $filter->customname = 0;
        $filter->sortorder = 1;
        $DB->insert_record('report_builder_filters', $filter);

        // Create embedded report.
        $config = new \rb_config();
        $report = \reportbuilder::create_embedded($shortname, $config);

        // Test if output is correct.
        $this->assertInstanceOf('reportbuilder', $report);
        $this->assertIsArray($report->filters);
        $this->assertArrayHasKey('user-username', $report->filters);
    }

    /**
     * Add a filter to the cancellations embedded report.
     */
    public function test_cancellations_filter() {
        global $DB;
        $shortname = 'facetoface_cancellations';

        // Create report record.
        $reportbuilder = new \stdClass();
        $reportbuilder->fullname = 'Seminars: Event cancelled attendees';
        $reportbuilder->shortname = $shortname;
        $reportbuilder->source = 'facetoface_sessions';
        $reportbuilder->hidden = 0;
        $reportbuilder->recordsperpage = 40;
        $reportbuilder->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        $reportbuilder->embedded = 1;
        $reportbuilder->showtotalcount = 0;
        $reportbuilder->id = $DB->insert_record('report_builder', $reportbuilder);

        // Set up access permissions.
        reportbuilder_set_default_access($reportbuilder->id);

        // Create username filter record.
        $filter = new stdClass();
        $filter->reportid = $reportbuilder->id;
        $filter->advanced = 0;
        $filter->region = 0;
        $filter->type = 'user';
        $filter->value = 'username';
        $filter->filtername = 'Username';
        $filter->customname = 0;
        $filter->sortorder = 1;
        $DB->insert_record('report_builder_filters', $filter);

        // Create embedded report.
        $config = new \rb_config();
        $report = \reportbuilder::create_embedded($shortname, $config);

        // Test if output is correct.
        $this->assertInstanceOf('reportbuilder', $report);
        $this->assertIsArray($report->filters);
        $this->assertArrayHasKey('user-username', $report->filters);
    }

    /**
     * Add a filter to the waitlist embedded report.
     */
    public function test_waitlist_filter() {
        global $DB;
        $shortname = 'facetoface_waitlist';

        // Create report record.
        $reportbuilder = new \stdClass();
        $reportbuilder->fullname = 'Seminars: Event wait-list attendees';
        $reportbuilder->shortname = $shortname;
        $reportbuilder->source = 'facetoface_sessions';
        $reportbuilder->hidden = 0;
        $reportbuilder->recordsperpage = 40;
        $reportbuilder->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        $reportbuilder->embedded = 1;
        $reportbuilder->showtotalcount = 0;
        $reportbuilder->id = $DB->insert_record('report_builder', $reportbuilder);

        // Set up access permissions.
        reportbuilder_set_default_access($reportbuilder->id);

        // Create username filter record.
        $filter = new stdClass();
        $filter->reportid = $reportbuilder->id;
        $filter->advanced = 0;
        $filter->region = 0;
        $filter->type = 'user';
        $filter->value = 'username';
        $filter->filtername = 'Username';
        $filter->customname = 0;
        $filter->sortorder = 1;
        $DB->insert_record('report_builder_filters', $filter);

        // Create embedded report.
        $config = new \rb_config();
        $report = \reportbuilder::create_embedded($shortname, $config);

        // Test if output is correct.
        $this->assertInstanceOf('reportbuilder', $report);
        $this->assertIsArray($report->filters);
        $this->assertArrayHasKey('user-username', $report->filters);
    }

}