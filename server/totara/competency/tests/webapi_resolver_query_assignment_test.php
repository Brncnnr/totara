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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 * @subpackage test
 */

use core\webapi\execution_context;
use totara_competency\models\assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the query resolver for competency assignment (singular), this service only returns one assignment by id.
 *
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_assignment_testcase extends advanced_testcase {

    /**
     * Helper to get execution context
     *
     * @param string $type
     * @param string|null $operation
     * @return execution_context
     */
    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return execution_context::create($type, $operation);
    }

    /**
     * @test_it_returns_assignment
     */
    public function test_it_returns_assignment() {
        $this->setAdminUser();

        $data = $this->create_data();

        // User assignment
        $expected = $data['user_assignment'];
        $args = [
            'assignment_id' => $expected->id
        ];

        $assignment = totara_competency\webapi\resolver\query\assignment::resolve($args, $this->get_execution_context());

        $this->assertInstanceOf(assignment::class, $assignment);

        $this->assertEquals($assignment->get_id(), $expected->id);


        // Position assignment
        $expected = $data['position_assignment'];
        $args = [
            'assignment_id' => $expected->id
        ];

        $assignment = totara_competency\webapi\resolver\query\assignment::resolve($args, $this->get_execution_context());

        $this->assertInstanceOf(assignment::class, $assignment);

        $this->assertEquals($assignment->get_id(), $expected->id);

        // Organisation assignment
        $expected = $data['organisation_assignment'];
        $args = [
            'assignment_id' => $expected->id
        ];

        $assignment = totara_competency\webapi\resolver\query\assignment::resolve($args, $this->get_execution_context());

        $this->assertInstanceOf(assignment::class, $assignment);

        $this->assertEquals($assignment->get_id(), $expected->id);

        // Cohort assignment
        $expected = $data['cohort_assignment'];
        $args = [
            'assignment_id' => $expected->id
        ];

        $assignment = totara_competency\webapi\resolver\query\assignment::resolve($args, $this->get_execution_context());

        $this->assertInstanceOf(assignment::class, $assignment);

        $this->assertEquals($assignment->get_id(), $expected->id);
    }

    /**
     * Create data required to test assignment service
     */
    protected function create_data() {
        // We'll need to create a competency and create an assignment
        // It's very simple and currently supports getting only assignments by id, however the data structure is fairly complex.

        $competency = $this->generator()->create_competency('Super-duper-competency');
        $other_comp = $this->generator()->create_competency('Competency no one cares about', $competency->frameworkid);

        $this->generator()->create_framework();

        $cohort = $this->assignment_generator()->create_cohort();
        $this->assignment_generator()->create_cohort();

        $position = $this->assignment_generator()->create_position();
        $this->assignment_generator()->create_position();

        $organisation = $this->assignment_generator()->create_organisation();
        $this->assignment_generator()->create_organisation();

        $user = $this->assignment_generator()->create_user();
        $this->assignment_generator()->create_user();

        $cohort_assignment = $this->assignment_generator()->create_cohort_assignment($competency->id, $cohort->id);
        $position_assignment = $this->assignment_generator()->create_cohort_assignment($competency->id, $position->id);
        $organisation_assignment = $this->assignment_generator()->create_cohort_assignment($competency->id, $organisation->id);
        $user_assignment = $this->assignment_generator()->create_cohort_assignment($competency->id, $user->id);

        $this->assignment_generator()->create_cohort_assignment($other_comp->id, $cohort->id);

        return compact(
            'competency',
            'cohort',
            'position',
            'organisation',
            'user',
            'cohort_assignment',
            'position_assignment',
            'organisation_assignment',
            'user_assignment'
        );
    }

    /**
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }

    /**
     * @return \totara_competency\testing\assignment_generator
     */
    protected function assignment_generator() {
        return $this->generator()->assignment_generator();
    }
}