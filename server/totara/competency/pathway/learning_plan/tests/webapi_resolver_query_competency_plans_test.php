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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_learning_plan
 * @subpackage test
 */

use core_phpunit\testcase;
use core\orm\query\builder;
use core\webapi\execution_context;
use pathway_learning_plan\entity\plan_competency_value;
use pathway_learning_plan\models\competency_plan;
use pathway_learning_plan\webapi\resolver\query\competency_plans;
use totara_competency\expand_task;
use totara_hierarchy\entity\competency;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the query to get learning plans that a competency is linked to and its rating scale value
 *
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_competency_plans_testcase extends testcase {

    private $scalevalue1;

    private $scalevalue2;

    private $competency;

    private $user1_assignment;

    private $user1;

    private $user2;

    protected function setUp(): void {
        global $DB;

        $totara_hierarchy_generator = \totara_hierarchy\testing\generator::instance();

        /** @var \totara_competency\testing\assignment_generator $totara_competency_generator */
        $totara_competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency')->assignment_generator();

        $compfw = $totara_hierarchy_generator->create_comp_frame([]);
        $comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $this->competency = new competency($comp);

        $scale = $this->competency->scale;
        $values = $scale->sorted_values_high_to_low;

        $this->scalevalue1 = $values->first();

        $values->next();
        $this->scalevalue2 = $values->current();

        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();

        $this->user1_assignment = $totara_competency_generator->create_user_assignment($this->competency->id, $this->user1->id);

        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        $role = builder::table('role')->where('shortname', 'user')->value('id');
        $this->setUser($this->user2);
        assign_capability('totara/competency:view_other_profile', CAP_ALLOW, $role, context_user::instance($this->user1->id));
        $this->setUser($this->user1);
        assign_capability('totara/competency:view_own_profile', CAP_ALLOW, $role, context_user::instance($this->user1->id));

        $this->setAdminUser();
    }

    protected function tearDown(): void {
        $this->scalevalue1 = null;
        $this->scalevalue2 = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->competency = null;
        $this->user1_assignment = null;
    }

    /**
     * Resolve the query.
     *
     * @return competency_plan
     */
    private function resolve(): competency_plan {
        return competency_plans::resolve(
            ['user_id' => $this->user1->id, 'assignment_id' => $this->user1_assignment->id],
            execution_context::create('dev', null)
        );
    }

    /**
     * Make sure the user has the right permissions to view their ratings
     */
    public function test_view_profile_self_capability() {
        $role = builder::table('role')->where('shortname', 'user')->value('id');

        $this->setUser($this->user1);
        $this->assertNotNull($this->resolve());

        unassign_capability('totara/competency:view_own_profile', $role);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('View own competency profile');

        $this->resolve();
    }

    /**
     * Make sure the query cannot be accessed by another user who isn't the users manager
     */
    public function test_view_profile_manager_capability() {
        $role = builder::table('role')->where('shortname', 'user')->value('id');

        $this->setUser($this->user2);
        $this->assertNotNull($this->resolve());

        unassign_capability('totara/competency:view_other_profile', $role);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('View profile of other users');

        $this->resolve();
    }

    /**
     * Make sure that the latest scale rating is returned
     */
    public function test_latest_scale_value_returned() {
        $plan1 = $this->create_learning_plan($this->user1->id, [
            $this->competency->id => $this->scalevalue2->id,
        ]);

        /** @var plan_competency_value $rating_value */
        $rating_value = plan_competency_value::repository()->one();

        /** @var competency_plan $response */
        $response = $this->resolve();
        $this->assertEquals([$plan1], $response->get_plans());
        $this->assertEquals($this->scalevalue2->id, $response->get_scale_value()->id);
        $this->assertEquals($rating_value->date_assigned, $response->get_date_assigned());

        $this->waitForSecond();
        $this->waitForSecond();

        $plan2 = $this->create_learning_plan($this->user1->id, [
            $this->competency->id => $this->scalevalue1->id,
        ]);

        /** @var plan_competency_value $new_rating_value */
        $new_rating_value = plan_competency_value::repository()->one();
        $this->assertNotEquals($rating_value->date_assigned, $new_rating_value->date_assigned);

        $response = $this->resolve();
        $this->assertEquals([$plan1, $plan2], $response->get_plans());
        $this->assertEquals($this->scalevalue1->id, $response->get_scale_value()->id);
        $this->assertEquals($new_rating_value->date_assigned, $response->get_date_assigned());
    }

    /**
     * Create a learning plan, with competencies assigned including scale values.
     *
     * @param int $for_user
     * @param array $competencies
     * @return development_plan
     */
    private function create_learning_plan(int $for_user, array $competencies): development_plan {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/component.class.php');
        require_once($CFG->dirroot . '/totara/plan/components/competency/competency.class.php');

        /** @var \totara_plan\testing\generator $plan_generator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $plan = $plan_generator->create_learning_plan(['userid' => $for_user]);

        $plan = new development_plan($plan->id);

        foreach ($competencies as $competency => $scale_value) {
            $plan_generator->add_learning_plan_competency($plan->id, $competency);

            (new dp_competency_component($plan))
                ->set_value($competency, $for_user, $scale_value, (object) ['manual' => true]);
        }

        return $plan;
    }

}
