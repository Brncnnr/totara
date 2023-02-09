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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/plan/development_plan.class.php');

class totara_plan_user_learning_item_testcase extends advanced_testcase {

    /**
     * @var \core\testing\generator
     */
    private $generator;

    /**
     * @var \totara_plan\testing\generator
     */
    private $plan_generator;

    private $course1;

    private $course2;

    private $plan1;

    private $plan2;

    private $user1;

    protected function tearDown(): void {
        $this->generator = null;
        $this->plan_generator = null;
        $this->course1 = null;
        $this->course2 = null;
        $this->plan1 = null;
        $this->plan2 = null;
        $this->user1 = null;
        parent::tearDown();
    }

    public function setUp(): void {
        parent::setUp();

        $this->generator = $this->getDataGenerator();
        $this->plan_generator = $this->generator->get_plugin_generator('totara_plan');

        $this->course1 = $this->generator->create_course();
        $this->course2 = $this->generator->create_course();

        $this->user1 = $this->getDataGenerator()->create_user(array('fullname' => 'user1'));
    }

    /**
     * Test the "one" static method for plans.
     */
    public function test_one() {
        $this->plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $this->user1->id));

        $plan = new development_plan($planrecord->id);

        $this->setAdminUser();

        $result = $this->plan_generator->add_learning_plan_course($plan->id, $this->course1->id);
        $result = $this->plan_generator->add_learning_plan_course($plan->id, $this->course2->id);

        $item = \totara_plan\user_learning\item::one($this->user1, $plan->id);

        $this->assertEquals($plan->name, $item->fullname);
        $this->assertEquals($this->user1->id, $plan->userid);
    }

    public function test_ensure_plan_content_loaded(): void {
        $planrecord = $this->plan_generator->create_learning_plan(['userid' => $this->user1->id]);

        $plan = new development_plan($planrecord->id);

        $this->setAdminUser();

        $this->plan_generator->add_learning_plan_course($plan->id, $this->course1->id);
        $this->plan_generator->add_learning_plan_course($plan->id, $this->course2->id);

        $completion_generator = $this->generator->get_plugin_generator('core_completion');
        $completion_generator->enable_completion_tracking($this->course1);
        $completion_generator->complete_course($this->course1, $this->user1);

        $item = \totara_plan\user_learning\item::one($this->user1, $plan->id);

        $item->ensure_plan_content_loaded();
        $courses = $item->get_courses();
        self::assertEquals(1, count($courses));

        $course = reset($courses);
        self::assertEquals($course->id, $this->course2->id);
    }
}