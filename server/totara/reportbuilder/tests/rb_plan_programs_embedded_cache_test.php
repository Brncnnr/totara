<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 *
 * Unit/functional tests to check Record of Learning: Programs reports caching
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_plan_programs_embedded_cache_testcase extends reportcache_advanced_testcase {
    // testcase data
    protected $report_builder_data = array('id' => 13, 'fullname' => 'Record of Learning: Programs', 'shortname' => 'plan_programs',
                                           'source' => 'dp_program', 'hidden' => 1, 'embedded' => 1);


    protected $report_builder_columns_data = array(
        array(
            'id' => 61, 'reportid' => 13, 'type' => 'program', 'value' => 'proglinkicon',
            'heading' => 'A', 'sortorder' => 1
        ),
        array(
            'id' => 62, 'reportid' => 13, 'type' => 'program', 'value' => 'assigned',
            'heading' => 'B', 'sortorder' => 2
        ),
        array(
            'id' => 63, 'reportid' => 13, 'type' => 'program', 'value' => 'recurring',
            'heading' => 'C', 'sortorder' => 3
        ),
        array(
            'id' => 64, 'reportid' => 13, 'type' => 'program', 'value' => 'timedue',
            'heading' => 'D', 'sortorder' => 4
        ),
        array(
            'id' => 65, 'reportid' => 13, 'type' => 'program_completion', 'value' => 'status',
            'heading' => 'E', 'sortorder' => 5
        )
    );

    protected $report_builder_filters_data = array(
                        array('id' => 29, 'reportid' => 13, 'type' => 'program', 'value' => 'fullname',
                              'sortorder' => 2, 'advanced' => 1),
                        array('id' => 30, 'reportid' => 13, 'type' => 'course_category', 'value' => 'id',
                              'sortorder' => 3, 'advanced' => 1));

    // Work data
    protected $user1 = null;
    protected $user2 = null;
    protected $user3 = null;
    protected $program1 = null;
    protected $program2 = null;
    protected $program3 = null;
    protected $program4 = null;

    /**
     * Prepare mock data for testing
     *
     * Common part of all test cases:
     * - Add 3 users
     * - Create 4 programs
     * - Enrol user1 to program1,3
     * - Enrol user2 to program2,3,4
     * - User3 is not added to any programs
     *
     */
    protected function setUp(): void {
        global $DB;

        parent::setup();
        $this->setAdminUser();
        $this->cleanup();

        $program_generator = \totara_program\testing\generator::instance();

        // Common parts of test cases:
        // Create report record in database
        $this->loadDataSet($this->createArrayDataSet(array('report_builder' => array($this->report_builder_data),
                                                           'report_builder_columns' => $this->report_builder_columns_data,
                                                           'report_builder_filters' => $this->report_builder_filters_data)));
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->user3 = $this->getDataGenerator()->create_user();
        $this->user4 = $this->getDataGenerator()->create_user();

        $this->program1 = $program_generator->create_program(['fullname' => 'Program 1']);
        $this->program2 = $program_generator->create_program(['fullname' => 'Program 2']);
        $this->program3 = $program_generator->create_program(['fullname' => 'Program 3']);
        $this->program4 = $program_generator->create_program(['fullname' => 'Program 4']);

        $program_generator->assign_program($this->program1->id, array($this->user1->id));
        $program_generator->assign_program($this->program2->id, array($this->user2->id));
        $program_generator->assign_program($this->program3->id, array($this->user1->id, $this->user2->id));
        $program_generator->assign_program($this->program4->id, array($this->user2->id));

        $syscontext = context_system::instance();

        // Assign user2 to be user1's manager and remove viewallmessages from manager role.
        $managerja = \totara_job\job_assignment::create_default($this->user2->id);
        \totara_job\job_assignment::create_default($this->user1->id, array('managerjaid' => $managerja->id));
        $rolemanager = $DB->get_record('role', array('shortname'=>'manager'));
        assign_capability('totara/plan:accessanyplan', CAP_PROHIBIT, $rolemanager->id, $syscontext);

        // Assign user3 to course creator role and add viewallmessages to course creator role.
        $rolecoursecreator = $DB->get_record('role', array('shortname'=>'coursecreator'));
        role_assign($rolecoursecreator->id, $this->user3->id, $syscontext);
        assign_capability('totara/plan:accessanyplan', CAP_ALLOW, $rolecoursecreator->id, $syscontext);
    }

    protected function tearDown(): void {
        $this->report_builder_data = null;
        $this->report_builder_columns_data = null;
        $this->report_builder_filters_data = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->program1 = null;
        $this->program2 = null;
        $this->program3 = null;
        $this->program4 = null;
        parent::tearDown();
    }

    protected function cleanup() {
        global $DB;
        $DB->execute('DELETE FROM {report_builder} WHERE 1=1');
        $DB->execute('DELETE FROM {report_builder_columns} WHERE 1=1');
        $DB->execute('DELETE FROM {report_builder_filters} WHERE 1=1');
        $DB->execute('DELETE FROM {prog_assignment} WHERE 1=1');
        $DB->execute('DELETE FROM {prog_user_assignment} WHERE 1=1');
        $DB->execute('DELETE FROM {prog_completion} WHERE 1=1');
        $DB->execute('DELETE FROM {prog} WHERE 1=1');
    }

    /**
     * Test courses report
     * Test case:
     * - Common part (@see: self::setUp() )
     * - Check that user1 has two courses (1 and 3)
     * - Check that user2 has three course (2,3,4)
     * - Check that user3 doesn't have any courses
     */
    public function test_plan_programs() {
        $programidalias = reportbuilder_get_extrafield_alias('program', 'proglinkicon', 'programid');
        $result = $this->get_report_result($this->report_builder_data['shortname'], array('userid' => $this->user1->id,), false);
        $this->assertCount(2, $result);
        $was = array();
        foreach($result as $r) {
            $this->assertContainsEquals($r->$programidalias, array($this->program1->id, $this->program3->id));
            $this->assertNotContainsEquals($r->program_proglinkicon, $was);
            $was[] = $r->program_proglinkicon;
        }

        $result = $this->get_report_result($this->report_builder_data['shortname'], array('userid' => $this->user2->id,), false);
        $this->assertCount(3, $result);
        $was = array();
        foreach($result as $r) {
            $this->assertContainsEquals($r->$programidalias, array($this->program2->id, $this->program3->id, $this->program4->id));
            $this->assertNotContainsEquals($r->program_proglinkicon, $was);
            $was[] = $r->program_proglinkicon;
        }

        $result = $this->get_report_result($this->report_builder_data['shortname'], array('userid' => $this->user3->id,), false);
        $this->assertCount(0, $result);
    }

    public function test_is_capable() {
        $this->setAdminUser();

        // Set up report and embedded object for is_capable checks.
        $shortname = $this->report_builder_data['shortname'];
        $config = (new rb_config())->set_embeddata(array('userid' => $this->user1->id));
        $report = reportbuilder::create_embedded($shortname, $config);
        $embeddedobject = $report->embedobj;

        // Test admin can access report.
        $this->assertTrue($embeddedobject->is_capable(2, $report),
                'admin cannot access report');

        // Test user1 can access report for self.
        $this->assertTrue($embeddedobject->is_capable($this->user1->id, $report),
                'user cannot access their own report');

        // Test user1's manager can access report (we have removed accessanyplan from manager role).
        $this->assertTrue($embeddedobject->is_capable($this->user2->id, $report),
                'manager cannot access report');

        // Test user3 can access report using accessanyplan (we give 'coursecreator' role access to accessanyplan).
        $this->assertTrue($embeddedobject->is_capable($this->user3->id, $report),
                'user with accessanyplan cannot access report');

        // Test that user4 cannot access the report for another user.
        $this->assertFalse($embeddedobject->is_capable($this->user4->id, $report),
                'user should not be able to access another user\'s report');
    }
}
