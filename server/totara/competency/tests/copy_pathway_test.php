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

use core\testing\generator as core_generator;
use pathway_criteria_group\criteria_group;
use pathway_learning_plan\learning_plan;
use pathway_manual\manual;
use pathway_manual\models\roles\appraiser;
use pathway_perform_rating\perform_rating;
use totara_competency\achievement_configuration;
use totara_competency\helpers\error;
use totara_competency\helpers\copy_pathway\errors;
use totara_competency\models\copy_pathway;
use totara_competency\testing\generator;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;
use totara_criteria\testing\generator as criteria_generator;

require_once(__DIR__.'/copy_pathway_testcase.php');

/**
 * @group totara_competency
 * @group totara_competency_copy_pathways
 */
class totara_competency_copy_pathway_test extends totara_competency_copy_pathway_testcase {
    public function test_unknown_source(): void {
        $this->assert_copier_creation_failed(
            123,
            $this->create_test_data()->targets->pluck('id'),
            [],
            errors::missing_source()->message
        );
    }

    public function test_unknown_target_competencies(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)
            ->first()
            ->id;

        $this->assert_copier_creation_failed(
            $source, [123], [], error::missing_competencies()->message
        );
    }

    public function test_unknown_frameworks(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)
            ->first()
            ->id;

        $this->assert_copier_creation_failed(
            $source,
            $test_data->targets->pluck('id'),
            [1234],
            error::missing_frameworks()->message
        );
    }

    public function test_source_has_no_pathways_to_copy(): void {
        $test_data = $this->create_test_data();

        $this->assert_copy_failed(
            $test_data->source->id,
            $test_data->targets->pluck('id'),
            [],
            errors::source_has_no_pathways()
        );
    }

    public function test_no_target_competencies(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)
            ->first()
            ->id;

        $this->assert_copy_failed(
            $source, [], [], error::no_selected_competencies()
        );
    }

    public function test_target_competencies_not_in_allowed_frameworks(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)
            ->first()
            ->id;

        $targets = $test_data->targets;
        $fw_id = $test_data->source_fw->id;
        $extra_count = $targets
            ->filter(
                function (competency $competency) use ($fw_id): bool {
                    return $competency->frameworkid != $fw_id;
                }
            )
            ->count();

        $this->assert_copy_failed(
            $source,
            $targets->pluck('id'),
            [$fw_id],
            error::competencies_not_in_frameworks($extra_count)
        );
    }

    public function test_missing_source_before_copy(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)->first();

        $this->assert_missing_before_copy(
            $source,
            $test_data->targets->all(),
            [],
            function () use ($source): void {
                competency::repository()->where('id', $source->id)->delete();
            },
            errors::missing_source()
        );
    }

    public function test_missing_target_competencies_before_copy(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)->first();
        $targets = $test_data->targets;

        $this->assert_missing_before_copy(
            $source,
            $targets->all(),
            [],
            function () use ($targets): void {
                competency::repository()
                    ->where('id', $targets->pluck('id'))
                    ->delete();
            },
            error::missing_competencies($targets->count())
        );
    }

    public function test_missing_frameworks_before_copy(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)->first();
        $fw = $test_data->source_fw;

        $this->assert_missing_before_copy(
            $source,
            $test_data->targets->all(),
            [$fw],
            function () use ($fw): void {
                competency_framework::repository()
                    ->where('id', $fw->id)
                    ->delete();
            },
            error::missing_frameworks()
        );
    }

    public function test_copy_pathways_any_framework(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)->first();
        $targets = $test_data->targets->all();

        $this->assert_copy_passed($source, $targets, [], $targets);
    }

    public function test_copy_pathways_restricted_framework(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)->first();

        $source_fw = $test_data->source_fw;
        $targets = $this
            ->filter_competencies_by_frameworks($test_data->targets, $source_fw)
            ->all();

        $this->assert_copy_passed($source, $targets, [$source_fw], $targets);
    }

    public function test_copy_pathways_repeated_targets_and_frameworks(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)->first();

        $source_fw = $test_data->source_fw;
        $targets = $this
            ->filter_competencies_by_frameworks($test_data->targets, $source_fw)
            ->all();

        $this->assert_copy_passed(
            $source,
            array_merge($targets, $targets),
            [$source_fw, $source_fw],
            $targets
        );
    }

    public function test_copy_pathways_to_targets_with_included_source(): void {
        $test_data = $this->create_test_data();
        $source = $this->add_pathways_to_competencies($test_data->source)->first();
        $targets = $test_data->targets->all();

        $this->assert_copy_passed(
            $source, array_merge($targets, [$source]), [], $targets
        );
    }

    public function test_copy_active_pathways_to_targets_with_existing_pathways(): void {
        $test_data = $this->create_test_data();

        // The source has both active and archived pathways; only active ones
        // should be copied across.
        $source = $test_data->source;
        $generator = generator::instance();
        $core_generator = core_generator::instance();
        $criteria_generator = criteria_generator::instance();

        $source_with_pathways = $this->add_pathways_to_competencies(
            $source,
            [
                (object) ['class' => learning_plan::class],
                (object) ['class' => perform_rating::class],
                (object) ['class' => manual::class, 'roles' => [appraiser::class]],
                (object) [
                    'class' => criteria_group::class,
                    'criteria' => [
                        $criteria_generator->create_coursecompletion([
                            'courseids' => [$core_generator->create_course()->id]
                        ]),
                        $criteria_generator->create_linkedcourses([
                            'competency' => $source->id
                        ]),
                        $criteria_generator->create_onactivate([
                            'competency' => $source->id
                        ]),
                        $criteria_generator->create_childcompetency([
                            'competency' => $source->id
                        ]),
                        $criteria_generator->create_othercompetency([
                            'competencyids' => [$generator->create_competency()->id]
                        ])
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
        )->all();

        $this->assert_copy_passed($source_with_pathways, $targets, [], $targets);
    }

    /**
     * Tests that the copy operation throws an exception when constructing the
     * copier.
     *
     * @param int $source_id source competency id.
     * @param int[] $target_ids target competency ids.
     * @param int[] $fw_ids target competency framework ids.
     * @param string $error expected error.
     */
    private function assert_copier_creation_failed(
        int $source_id,
        array $target_ids,
        array $fw_ids,
        string $error
    ): void {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage($error);
        copy_pathway::create_by_ids($source_id, $target_ids, $fw_ids);
    }

    /**
     * Tests that the copy operation fails.
     *
     * @param int $source_id source competency id.
     * @param int[] $target_ids target competency ids.
     * @param int[] $fw_ids target competency framework ids.
     * @param error $error expected error.
     */
    private function assert_copy_failed(
        int $source_id,
        array $target_ids,
        array $fw_ids,
        error $error
    ): void {
        $copy_op_id = $source_id;

        $this->enable_logstore();
        $result = copy_pathway::create_by_ids($source_id, $target_ids, $fw_ids)
            ->copy($copy_op_id);
        $this->disable_logstore();

        $this->assertFalse($result->is_successful(), "copy passed: $result");
        $this->assertEquals($error, $result->value, 'wrong error');
        $this->assert_pathway_copy_operation_not_logged($copy_op_id);
    }

    /**
     * Tests that the copy operation passes.
     *
     * @param competency $source source competency.
     * @param competency[] $targets target competencies.
     * @param competency_framework[] $fw target competency framework.
     * @param competency[] $expected_targets expected copied competencies.
     */
    private function assert_copy_passed(
        competency $source,
        array $targets,
        array $fw,
        array $expected_targets
    ): void {
        $expected_pathways = $this->expected_pathways_after_copy(
            $source, $expected_targets
        );

        $copy_op_id = $source->id;

        $this->enable_logstore();
        $result = copy_pathway::create($source, $targets, $fw)->copy($copy_op_id);
        $this->disable_logstore();

        $this->assertTrue($result->is_successful(), "copy failed: $result");

        $copied = $result->value;
        $this->assertEqualsCanonicalizing(
            array_keys($expected_pathways), $copied->pluck('id'), 'wrong return'
        );

        $this->assert_aggregation_type(
            $copied,
            (new achievement_configuration($source))->get_aggregation_type()
        );

        $this->assert_pathways_exist($copied, $expected_pathways);
        $this->assert_pathway_copy_operation_logged($copy_op_id, $source, $copied);
    }

    /**
     * Tests that the copy operation fails.
     *
     * @param competency $source source competency.
     * @param competency[] $targets target competencies.
     * @param competency_framework[] $fw target competency framework.
     * @param callable $delete ()->void function that deletes already registered
     *        entities.
     * @param error $error expected error.
     */
    private function assert_missing_before_copy(
        competency $source,
        array $targets,
        array $fw,
        callable $delete,
        error $error
    ): void {
        $copier = copy_pathway::create($source, $targets, $fw);

        // Oops, something disappears before copy.
        $delete();
        $result = $copier->copy($source->id);

        $this->assertFalse($result->is_successful(), "copy passed: $result");
        $this->assertEquals($error, $result->value, 'wrong error');
    }
}
