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
 * @package totara_reportbuilder
 *
 * Unit/functional tests to check Record of Learning: Competencies reports caching
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot.'/totara/hierarchy/lib.php');

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_plan_competencies_embedded_cache_testcase extends reportcache_advanced_testcase {
    // testcase data
    protected $report_builder_data = array('id' => 10, 'fullname' => 'Record of Learning: Competencies', 'shortname' => 'plan_competencies',
                                           'source' => 'dp_competency', 'hidden' => 1, 'embedded' => 1);

    protected $report_builder_columns_data = array(
                        array('id' => 43, 'reportid' => 10, 'type' => 'plan', 'value' => 'planlink',
                              'heading' => 'A', 'sortorder' => 1),
                        array('id' => 44, 'reportid' => 10, 'type' => 'plan', 'value' => 'status',
                              'heading' => 'B', 'sortorder' => 2),
                        array('id' => 45, 'reportid' => 10, 'type' => 'competency', 'value' => 'fullname',
                              'heading' => 'C', 'sortorder' => 3),
                        array('id' => 46, 'reportid' => 10, 'type' => 'competency', 'value' => 'priority',
                              'heading' => 'D', 'sortorder' => 4),
                        array('id' => 47, 'reportid' => 10, 'type' => 'competency', 'value' => 'duedate',
                              'heading' => 'E', 'sortorder' => 5),
                        array('id' => 48, 'reportid' => 10, 'type' => 'competency', 'value' => 'proficiencyandapproval',
                              'heading' => 'F', 'sortorder' => 6));

    protected $report_builder_filters_data = array(
                        array('id' => 18, 'reportid' => 10, 'type' => 'competency', 'value' => 'fullname',
                              'sortorder' => 1, 'advanced' => 0),
                        array('id' => 19, 'reportid' => 10, 'type' => 'competency', 'value' => 'priority',
                              'sortorder' => 2, 'advanced' => 1),
                        array('id' => 20, 'reportid' => 10, 'type' => 'competency', 'value' => 'duedate',
                              'sortorder' => 3, 'advanced' => 1),
                        array('id' => 21, 'reportid' => 10, 'type' => 'plan', 'value' => 'name',
                              'sortorder' => 4, 'advanced' => 1));

    // Work data
    public static $ind = 0;
    protected $user1 = null;
    protected $user2 = null;
    protected $user3 = null;
    protected $user4 = null;
    protected $plan1 = null;
    protected $plan2 = null;
    protected $competency1 = null;
    protected $competency2 = null;
    protected $competency3 = null;


    protected function tearDown(): void {
        $this->report_builder_data = null;
        $this->report_builder_columns_data = null;
        $this->report_builder_filters_data = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->user4 = null;
        $this->plan1 = null;
        $this->plan2 = null;
        $this->competency1 = null;
        $this->competency2 = null;
        $this->competency3 = null;
        parent::tearDown();
    }

    /**
     * Prepare mock data for testing
     *
     * Common part of all test cases:
     * - Create 3 users
     * - Create plan1 by user1
     * - Create plan2 and plan3 by user2
     * - Add 2 competencies to each plan
     */
    protected function setUp(): void {
        global $DB;

        parent::setup();
        $this->setAdminUser();

        // Common parts of test cases:
        // Create report record in database
        $this->loadDataSet($this->createArrayDataSet(array('report_builder' => array($this->report_builder_data),
                                                           'report_builder_columns' => $this->report_builder_columns_data,
                                                           'report_builder_filters' => $this->report_builder_filters_data)));

        /** @var \totara_hierarchy\testing\generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_framework('competency');
        $this->competency1 = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency');
        $this->competency2 = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency');
        $this->competency3 = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency');

        /** @var \totara_plan\testing\generator $plangenerator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        /** @var \totara_job\testing\generator $plangenerator */
        $user_generator = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$this->user1, $ja1] = $user_generator->create_user_and_job([], null, null);
        [$this->user2, $ja2] = $user_generator->create_user_and_job([], null, null);
        [$this->user3, $ja3] = $user_generator->create_user_and_job([], null, null);
        [$this->user4, $ja4] = $user_generator->create_user_and_job([], null, null);
        $this->plan1 = $plan_generator->create_learning_plan(['userid' => $this->user1->id]);
        $this->plan2 = $plan_generator->create_learning_plan(['userid' => $this->user2->id]);
        $plan3 = $plan_generator->create_learning_plan(['userid' => $this->user2->id]);
        $sink = $this->redirectMessages();
        $plan_generator->add_learning_plan_competency($this->plan1->id, $this->competency1->id);
        $plan_generator->add_learning_plan_competency($this->plan2->id, $this->competency2->id);
        $plan_generator->add_learning_plan_competency($this->plan2->id, $this->competency3->id);
        $sink->close();

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

    /**
     * Test courses report
     * Test case:
     * - Common part (@see: self::setUp() )
     * - Common part (@see: self::setUp() )
     * - Check that user1 has competencies of plan1
     * - Check that user2 has competencies of plan2 and plan3
     * - Check that user3 has no plans
     *
     * @param int $usecache Use cache or not (1/0)
     * @dataProvider provider_use_cache
     */
    public function test_plan_competencies($usecache) {
        if ($usecache) {
            $this->enable_caching($this->report_builder_data['id']);
        }
        $planidalias = reportbuilder_get_extrafield_alias('plan', 'planlink', 'plan_id');
        $result = $this->get_report_result($this->report_builder_data['shortname'],
                            array('userid' => $this->user1->id), $usecache);
        $this->assertCount(1, $result);
        $r = array_shift($result);
        $this->assertEquals($this->plan1->id, $r->$planidalias);

        $result = $this->get_report_result($this->report_builder_data['shortname'],
                            array('userid' => $this->user2->id), $usecache);
        $this->assertCount(2, $result);
        $was = array('');
        foreach($result as $r) {
            $this->assertEquals($this->plan2->id, $r->$planidalias);
            $this->assertNotContainsEquals($r->competency_fullname, $was);
            $was[] = $r->competency_fullname;
        }

        $result = $this->get_report_result($this->report_builder_data['shortname'],
                            array('userid' => $this->user3->id), $usecache);
        $this->assertCount(0, $result);
    }

    /**
     * Create mock competency for plan
     *
     * @param stdClass|array $record
     *
     * @deprecated since Totara 13, please use \totara_plan\testing\generator.
     */
    public function create_competency($record = array()) {
        self::$ind++;

        $default = array(
            'shortname' => 'Competency ' . self::$ind,
            'fullname' => 'Comptenecy #' . self::$ind,
            'description' => 'This is test competency #' . self::$ind,
            'idnumber' => 'ID' . self::$ind,
            'timemodified' => time(),
            'usermodified' => 2,
            'proficiencyexpected' => 1,
            'evidencecount' => 0,
            'visible' => 1,
            'aggregationmethod' => 0,

        );
        $properties = array_merge($default, $record);

        $hierarchy = hierarchy::load_hierarchy('competency');
        $result = $hierarchy->add_hierarchy_item((object)$properties, 0, 1, false);
        return $result;
    }

    /**
     * Assign a competency to a plan
     * @param int $planid
     * @param int $competencyid
     *
     * @deprecated since Totara 13, please use \totara_plan\testing\generator.
     */
    public function assign_competency($planid, $competencyid) {
        $plan = new development_plan($planid);
        $component = $plan->get_component('competency');
        $component->assign_new_item($competencyid, false, true);
    }

    public function test_is_capable() {

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
