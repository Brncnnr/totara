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

use core\orm\query\builder;
use totara_competency\helpers\error;
use totara_competency\helpers\copy_pathway\errors;
use totara_competency\task\copy_pathway_task;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__.'/copy_pathway_testcase.php');

/**
 * @group totara_competency
 * @group totara_competency_copy_pathways
 */
class totara_competency_webapi_resolver_mutation_copy_pathway_test extends totara_competency_copy_pathway_testcase {
    use webapi_phpunit_helper;

    private const MUTATION = 'totara_competency_copy_pathway';
    private const TASK = "\\" . copy_pathway_task::class;

    public function test_successful_ajax_call(): void {
        $test_data = $this->create_test_data();
        $source_id = $this->add_pathways_to_competencies($test_data->source)
            ->first()
            ->id;

        $args = [
            'input' => [
                'source_competency_id' => $source_id,
                'target_competency_ids' => $test_data->targets->pluck('id'),
                'allowed_competency_frameworks' => $test_data->fw->pluck('id')
            ]
        ];

        $this->assert_copy_passed($args);
    }

    public function test_failed_ajax_query_feature_disabled(): void {
        $test_data = $this->create_test_data();

        $feature = 'competency_assignment';
        advanced_feature::disable($feature);

        $this->assert_copy_exception(
            [
                'input' => [
                    'source_competency_id' => $test_data->source->id,
                    'target_competency_ids' => $test_data->targets->pluck('id'),
                    'allowed_competency_frameworks' => $test_data->fw->pluck('id')
                ]
            ],
            "Feature $feature is not available."
        );

        advanced_feature::enable($feature);
    }

    public function test_failed_ajax_query_wrong_user(): void {
        $test_data = $this->create_test_data();

        $args = [
            'input' => [
                'source_competency_id' => $test_data->source->id,
                'target_competency_ids' => $test_data->targets->pluck('id'),
                'allowed_competency_frameworks' => $test_data->fw->pluck('id')
            ]
        ];

        self::setGuestUser();
        $this->assert_copy_exception($args, 'Course or activity not accessible');

        self::setUser(self::getDataGenerator()->create_user());
        $this->assert_copy_exception($args, 'error/adminaccessrequired');

        self::setUser();
        $this->assert_copy_exception($args, 'Course or activity not accessible');
    }

    public function test_failed_ajax_query_invalid_input(): void {
        $test_data = $this->create_test_data();
        $source_id = $test_data->source->id;
        $targets = $test_data->targets;
        $target_ids = $targets->pluck('id');
        $fw_ids = $test_data->fw->pluck('id');

        $this->assert_copy_exception(
            ['input' => []],
            'source_competency_id of required type core_id! was not provided.'
        );

        $this->assert_copy_failed(
            [
                'input' => [
                    'source_competency_id' => 434,
                    'target_competency_ids' => $target_ids,
                    'allowed_competency_frameworks' => $fw_ids
                ]
            ],
            errors::missing_source()
        );

        $this->assert_copy_failed(
            [
                'input' => [
                    'source_competency_id' => $source_id,
                    'target_competency_ids' => $target_ids,
                    'allowed_competency_frameworks' => $fw_ids
                ]
            ],
            errors::source_has_no_pathways()
        );

        $this->add_pathways_to_competencies($test_data->source);
        $this->assert_copy_failed(
            [
                'input' => [
                    'source_competency_id' => $source_id,
                    'target_competency_ids' => [],
                    'allowed_competency_frameworks' => $fw_ids
                ]
            ],
            error::no_selected_competencies()
        );

        $target_fw_id = $test_data->source_fw->id;
        $extra_count = $targets
            ->filter(
                function (competency $competency) use ($target_fw_id): bool {
                    return $competency->frameworkid != $target_fw_id;
                }
            )
            ->count();

        $this->assert_copy_failed(
            [
                'input' => [
                    'source_competency_id' => $source_id,
                    'target_competency_ids' => $target_ids,
                    'allowed_competency_frameworks' => [$target_fw_id]
                ]
            ],
            error::competencies_not_in_frameworks($extra_count)
        );
    }

    public function test_failed_ajax_query_missing_before_queueing(): void {
        $test_data = $this->create_test_data();
        $source_id = $test_data->source->id;
        $target_ids = $test_data->targets->pluck('id');
        $fw_ids = $test_data->fw->pluck('id');

        $this->assert_missing_before_queueing(
            [
                'input' => [
                    'source_competency_id' => $source_id,
                    'target_competency_ids' => $target_ids,
                    'allowed_competency_frameworks' => $fw_ids
                ]
            ],
            function () use ($fw_ids): void {
                competency_framework::repository()
                    ->where('id', $fw_ids)
                    ->delete();
            },
            error::missing_frameworks(count($fw_ids))
        );

        $this->assert_missing_before_queueing(
            [
                'input' => [
                    'source_competency_id' => $source_id,
                    'target_competency_ids' => $target_ids,
                    'allowed_competency_frameworks' => []
                ]
            ],
            function () use ($target_ids): void {
                competency::repository()
                    ->where('id', $target_ids)
                    ->delete();
            },
            error::missing_competencies(count($target_ids))
        );
    }

    /**
     * Convenience function to check that the copy operation failed and an
     * exception was thrown.
     *
     * @param array $args graphql arguments.
     * @param string $error expected error.
     */
    private function assert_copy_exception(
        array $args,
        string $error
    ): void {
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, $error);
    }

    /**
     * Convenience function to check that the copy operation failed.
     *
     * @param array $args graphql arguments.
     * @param error $error expected error.
     */
    private function assert_copy_failed(
        array $args,
        error $error
    ): void {
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertFalse($result['success'], 'copy passed');
        $this->assertEquals(
            // Right now only message is returned; see copy_pathway.graphql
            ['message' => $error->message],
            $result['error'],
            'wrong error'
        );
    }

    /**
     * Convenience function to check that the copy operation passed.
     *
     * Note: this just verifies the task was queued correctly; it does not need
     * to execute a cron run or check whether the actual pathways were copied
     * across; that is left to the totara_competency_copy_pathway_task_test and
     * totara_competency_copy_pathway_test classes to exhaustively verify.
     *
     * @param array $args graphql arguments.
     */
    public function assert_copy_passed(
        array $args
    ): void {
        $this->assertFalse(
            builder::table('task_adhoc')
                ->where('classname', self::TASK)
                ->exists()
        );

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertTrue($result['success'], 'copy failed');
        $this->assertNull($result['error'], 'successful copy has error');

        $this->assertTrue(
            builder::table('task_adhoc')
                ->where('classname', self::TASK)
                ->where('component', 'totara_competency')
                ->where('userid', get_admin()->id)
                ->exists()
        );
    }

    /**
     * Convenience function to check that the adhoc task queueing operation
     * failed.
     *
     * @param array $args graphql arguments.
     * @param callable $delete ()->void function that deletes already registered
     *        entities.
     * @param error $error expected error.
     *
     * @param competency $source source competency.
     * @param competency[] $targets target competencies.
     * @param competency_framework[] $fw target competency framework.
     * @param error $error expected error.
     */
    private function assert_missing_before_queueing(
        array $args,
        callable $delete,
        error $error
    ): void {
        // Oops, something disappears before queueing.
        $delete();

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertFalse($result['success'], 'copy passed');
        $this->assertEquals(
            // Right now only message is returned; see copy_pathway.graphql
            ['message' => $error->message],
            $result['error'],
            'wrong error'
        );
    }
}
