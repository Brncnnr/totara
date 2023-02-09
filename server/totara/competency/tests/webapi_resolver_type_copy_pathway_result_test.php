<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use core\collection;
use core\testing\generator as core_generator;
use core\webapi\execution_context;
use pathway_criteria_group\criteria_group;
use pathway_learning_plan\learning_plan;
use pathway_manual\manual;
use totara_competency\formatter\copy_pathway_result;
use totara_competency\helpers\error;
use totara_competency\helpers\result;
use totara_competency\testing\generator;
use totara_competency\webapi\resolver\mutation\copy_pathway as copy_pathway_model;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_criteria\testing\generator as criteria_generator;

require_once(__DIR__.'/copy_pathway_testcase.php');

/**
 * @group totara_competency
 * @group totara_competency_copy_pathways
 */
class totara_competency_webapi_resolver_type_copy_pathway_result_test extends totara_competency_copy_pathway_testcase {
    use webapi_phpunit_helper;

    private const TYPE = 'totara_competency_copy_pathway_result';

    /**
     * Test data for test_invalid
     */
    public function td_invalid(): array {
        $source = result::create(error::no_selected_competencies());

        return [
            '1. wrong target class' => [new stdClass(), 'message', result::class],
            '2. unknown field' => [$source, 'unknown_field', 'unknown_field'],
        ];
    }

    /**
     * @dataProvider td_invalid
     */
    public function test_invalid(
        $source,
        string $field,
        string $error
    ): void {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage($error);
        $this->resolve_graphql_type(self::TYPE, $field, $source);
    }

    /**
     * Test data for test_valid
     */
    public function td_valid(): array {
        $error = error::no_selected_competencies();
        $failed = result::create($error);
        $passed = result::create(collection::new([]));

        return [
            '1. error from result' => [$failed, 'error', $error],
            '2. successful from error result' => [$failed, 'success', false],
            '3. successful from passed result' => [$passed, 'success', true]
        ];
    }

    /**
     * @dataProvider td_valid
     */
    public function test_valid(
        result $source,
        string $field,
        $expected
    ): void {
        $this->assertEquals(
            $expected,
            $this->resolve_graphql_type(self::TYPE, $field, $source, []),
            'wrong value'
        );
    }

    public function test_copy_pathway_count_and_review_count() {
        $this->setAdminUser();
        $test_data = $this->create_test_data(1, 3);
        $source = $test_data->source;
        $generator = generator::instance();
        $core_generator = core_generator::instance();
        $criteria_generator = criteria_generator::instance();

        $source_with_pathways = $this->add_pathways_to_competencies(
            $source,
            [
                (object) ['class' => learning_plan::class],
                (object) [
                    'class' => criteria_group::class,
                    'criteria' => [
                        $criteria_generator->create_coursecompletion([
                            'courseids' => [$core_generator->create_course()->id]
                        ]),
                        $criteria_generator->create_linkedcourses([
                            'competency' => $source->id
                        ]),
                    ]
                ]
            ],
            [(object) ['class' => manual::class]],
        )->first();
        $source_with_pathways->active_pathways(); // Force a reload of the changed pathways.

        // Target competencies have a mixture of archived and active pathways;
        // when the copy is done, the active pathways should be archived.
        $targets = $this->add_pathways_to_competencies(
            $test_data->targets,
            [(object) ['class' => learning_plan::class]],
            [(object) ['class' => manual::class]]
        )->pluck('id');

        $args = [
            'input' => [
                'source_competency_id' => $source->id,
                'target_competency_ids' => $targets,
                'allowed_competency_frameworks' => [$test_data->source_fw->id]
            ]
        ];
        $context = execution_context::create('dev');
        $result = copy_pathway_model::resolve($args, $context);
        $expect = 3;
        $copied_count = $this->resolve_graphql_type(self::TYPE, copy_pathway_result::RESULT_TOTAL_COUNT, $result, []);
        $this->assertEquals($expect, $copied_count, 'Wrong Count of total');
        $expect = 0;
        $need_review_count = $this->resolve_graphql_type(self::TYPE, copy_pathway_result::RESULT_REVIEW_COUNT, $result, []);
        $this->assertEquals($expect, $need_review_count, 'Wrong Count of total');
    }
}
