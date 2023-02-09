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
use core\entity\user;
use totara_competency\helpers\error;
use totara_competency\helpers\copy_pathway\errors;
use totara_competency\models\copy_pathway;
use totara_competency\task\copy_pathway_task;
use totara_hierarchy\entity\competency;

require_once(__DIR__.'/copy_pathway_testcase.php');

/**
 * @group totara_competency
 * @group totara_competency_copy_pathways
 */
class totara_competency_copy_pathway_task_test extends totara_competency_copy_pathway_testcase {
    public function test_execute_passed(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)->first();
        $targets = $this->add_pathways_to_competencies($test_data->targets);

        $this->assert_task_execution(
            $this->create_task($source, $targets, 34324),
            $targets,
            [sprintf('copied pathways to %d targets', $targets->count())]
        );
    }

    public function test_execute_failed(): void {
        $test_data = $this->create_test_data();
        $targets = $test_data->targets;

        $this->assert_task_execution(
            $this->create_task($test_data->source, $targets, 34324),
            $targets,
            [errors::source_has_no_pathways()->message]
        );
    }

    public function test_same_target_in_multiple_unexecuted_tasks(): void {
        $task1_data = $this->create_test_data();
        $source1 = $this->add_pathways_to_competencies($task1_data->source)->first();
        $targets1 = $task1_data->targets;

        // This also verifies the task1 targets have the task1 copy op id.
        $task1 = $this->create_task($source1, $targets1, 34324);

        $task2_data = $this->create_test_data();
        $source2 = $this->add_pathways_to_competencies($task2_data->source)->first();

        // This verifies task1 targets now have the task2 copy op id ie task1
        // no longer should copy pathways to them.
        $task2 = $this->create_task($source2, $targets1, 10000);

        // Which means that when task1 runs, there should be nothing to copy to.
        $this->assert_task_execution(
            $task1,
            collection::new([]),
            [error::no_selected_competencies()->message]
        );

        // And task2 should do the actual copying.
        $this->assert_task_execution(
            $task2,
            $targets1,
            [sprintf('copied pathways to %d targets', $targets1->count())]
        );
    }

    /**
     * Runs the specified task and tests it.
     *
     * Note: this just verifies the task executed correctly; it does not need to
     * check whether the actual pathways were copied across; that is left to the
     * totara_competency_copy_pathway_test class to exhaustively verify.
     *
     * @param copy_pathway_task $task task to execute.
     * @param collection<competency> $targets target competencies.
     * @param string[] $expected_logs expected logging messages in the correct
     *        sequence.
     */
    private function assert_task_execution(
        copy_pathway_task $task,
        collection $targets,
        array $expected_logs
    ): void {
        $logs = [];

        $task
            ->set_logger(
                function (string $message) use (&$logs): void {
                    $logs[] = $message;
                }
            )
            ->execute();

        foreach ($expected_logs as $i => $expected) {
            self::assertStringContainsString($expected, $logs[$i], 'wrong logs');
        }

        $tagged_count = competency::repository()
            ->where('id', $targets->pluck('id'))
            ->where('copy_op_id', '!=', 0)
            ->count();
        $this->assertEquals(0, $tagged_count, 'wrong tagged count');
    }

    /**
     * Creates a task with the given parameters.
     *
     * @param competency $source source competency.
     * @param collection<competency> $targets target competencies.
     * @param int $copy_op_id copy operation id for the created task.
     *
     * @return copy_pathway_task the created task.
     */
    private function create_task(
        competency $source,
        collection $targets,
        int $copy_op_id
    ): copy_pathway_task {
        $task = copy_pathway_task::create(
            copy_pathway::create($source, $targets->all(), []),
            user::logged_in()->id,
            $copy_op_id
        );

        $tagged_ids = $targets->pluck('id');
        $tagged_count = competency::repository()
            ->where('id', $tagged_ids)
            ->where('copy_op_id', $copy_op_id)
            ->count();
        $this->assertEquals(
            count($tagged_ids), $tagged_count, 'wrong tagged count'
        );

        return $task;
    }
}
