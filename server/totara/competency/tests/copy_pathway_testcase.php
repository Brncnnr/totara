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
use core\orm\query\builder;
use core\session\manager;
use pathway_criteria_group\criteria_group;
use totara_competency\pathway;
use totara_criteria\criterion;
use totara_competency\event\pathways_copied_bulk;
use totara_hierarchy\entity\competency;

require_once(__DIR__.'/totara_competency_testcase.php');

abstract class totara_competency_copy_pathway_testcase extends totara_competency_testcase {
    /**
     * Generates test data.
     *
     * @param int $fw_count no of competency frameworks to generate. If equal to
     *        one, then all competencies including the source belong to this.
     *        Otherwise, the source framework has the source and $competency_per_fw
     *        additional competencies and $fw_count - 1 additional frameworks are
     *        generated.
     * @param int $competency_per_fw no of competencies per framework to generate.
     *
     * @return stdClass test data in this format:
     *         - [competency] source - source competency
     *         - [competency_framework] source_fw - source competency framework
     *         - [collection<competency>] targets - other competencies to copy
     *           to
     *         - [collection<competency_framework>] fw - all frameworks including
     *           the source one.
     */
    protected function create_test_data(
        int $fw_count = 3,
        int $competency_per_fw = 2
    ): stdClass {
        self::setAdminUser();

        $generator = $this->generator();
        $source_fw = $generator->create_framework(null, "source fw");
        $source =  $generator->create_competency('source', $source_fw);

        $targets = [];
        for ($i = 0; $i < $competency_per_fw; $i++) {
            $targets[] = $generator->create_competency("target $i", $source_fw);
        }

        $frameworks = [$source_fw];
        for ($i = 0; $i < $fw_count - 1; $i++) {
            $fw = $generator->create_framework(null, "target fw $i");

            for ($j = 0; $j < $competency_per_fw; $j++) {
                $targets[] = $generator->create_competency("competency $j", $fw);
            }

            $frameworks[] = $fw;
        }

        return (object) [
            'source' => $source,
            'source_fw' => $source_fw,
            'targets' => collection::new($targets),
            'fw' => collection::new($frameworks),
        ];
    }

    /**
     * Tests that the specified competency has exactly the expected pathways.
     *
     * @param competency|collection<competency>|competency[] $targets targets to
     *        check.
     * @param array<int,array> $expected expected pathways as generated via the
     *        self::expected_pathways_after_copy() method.
     */
    protected function assert_pathways_exist(
        $targets,
        array $expected
    ): void {
        $this->as_collection($targets)->map(
            function (competency $target) use ($expected): void {
                $target_id = $target->id;
                $pathways = $expected[$target_id] ?? null;
                $this->assertNotNull(
                    $pathways, "cannot find expected pathways for $target_id"
                );

                $actual_pathways = $this->unpacked_pathways($target)->all();

                $this->assertCount(
                    count($pathways), $actual_pathways, 'wrong pathway count'
                );

                // Cannot use $this->assertEqualsCanonicalizing() here to compare
                // pathways because it does not sort arrays within arrays; there
                // will be intermittent failures when the correct data is retrieved
                // from the database but in a different order.
                sort($pathways);
                sort($actual_pathways);

                foreach ($pathways as $i => $expected_pathway) {
                    foreach ($actual_pathways[$i] as $key => $value) {
                        $expected_value = $expected_pathway[$key];

                        $key === 'other'
                            ? $this->assertEqualsCanonicalizing($expected_value, $value)
                            : $this->assertEquals($expected_value, $value);
                    }
                }
            }
        );
    }

    /**
     * Tests that a pathway copy operation was logged.
     *
     * NB: this assumes the caller previously executed self::enable_logstore().
     *
     * @param int $copy_id unique pathway copy operation id.
     * @param competency $source competency from which pathways were copied.
     * @param collection<collection> $targets competencies with the copied
     *        pathways.
     */
    protected function assert_pathway_copy_operation_logged(
        int $copy_id,
        competency $source,
        collection $targets
    ): void {
        // This throws an exception if there was not exactly one entry.
        $log_entry = builder::table('logstore_standard_log')
            ->where('objectid', $copy_id)
            ->where_like('eventname', pathways_copied_bulk::class)
            ->one(true);

        $this->assertEquals(manager::get_realuser()->id, $log_entry->userid);

        $details = unserialize($log_entry->other);
        $this->assertEquals($source->id, $details['source_id']);
        $this->assertEqualsCanonicalizing($targets->pluck('id'), $details['target_ids']);
    }

    /**
     * Tests that a pathway copy operation was not logged.
     *
     * NB: this assumes the caller previously executed self::enable_logstore().
     *
     * @param int $copy_id unique pathway copy operation id.
     */
    protected function assert_pathway_copy_operation_not_logged(
        int $copy_id
    ): void {
        $count = builder::table('logstore_standard_log')
            ->where('objectid', $copy_id)
            ->where('eventname', pathways_copied_bulk::class)
            ->count();

        $this->assertEquals(0, $count);
    }

    /**
     * Disable all logstores.
     */
    protected function disable_logstore(): void {
        set_config('enabled_stores', '', 'tool_log');
        get_log_manager(true);
    }

    /**
     * Enables the standard logstore.
     */
    protected function enable_logstore(): void {
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        set_config('buffersize', 0, 'logstore_standard');
        set_config('logguests', 1, 'logstore_standard');
        get_log_manager(true);
    }

    /**
     * Determines the expected competency pathways after a copy. After a copy
     * operation, a competency should have these elements in their pathway list:
     * - the active pathways from the source
     * - originally active target pathways which now become archived pathways
     * - originally archived target pathways
     *
     * @param competency $source source from which to copy active pathways.
     * @param competency|collection<competency>|competency[] $targets targets
     *        for which to determine the copied pathways.
     *
     * @return array<int,array> mapping of competency ids to expected pathways
     *         for those competencies after the copy.
     */
    protected function expected_pathways_after_copy(
        competency $source,
        $targets
    ): array {
        $copied = $this->unpacked_pathways($source, false)->all();

        return $this->as_collection($targets)->reduce(
            function (array $by_id, competency $target) use ($copied): array {
                $target_pathways = $this->unpacked_pathways($target)
                    ->map(
                        function (array $unpacked): array {
                            if ($unpacked['status'] === 'ACTIVE') {
                                $unpacked['status'] = 'ARCHIVED';
                            }

                            return $unpacked;
                        }
                    )
                    ->all();

                $by_id[$target->id] = array_merge($copied, $target_pathways);
                return $by_id;
            },
            []
        );
    }

    /**
     * Returns competency pathway details as an set of key-value pairs.
     *
     * @param competency $competency competency to process.
     * @param bool $all_pathways if false returns only active pathway details
     *        otherwise returns details of both active and archived pathways.
     *
     * @return collection<array> a collection of pathway details; each detail
     *         has these keys:
     *         - [string] classification: classification name
     *         - [int] sort_order: sorting order
     *         - [string] status: status name
     *         - [string] title: pathway title
     *         - [string] type: pathway class
     *         - [string] desc: short description (only for non criteria group
     *           pathways)
     *         - [array] other: custom details; the summarized criteria set for
     *           non criteria group pathways, the aggregation details, type and
     *           title for each criteria_group criterion.
     */
    protected function unpacked_pathways(
        competency $competency,
        bool $all_pathways = true
    ): collection {
        $pathways = $all_pathways ? 'pathways' : 'active_pathways';

        return $competency->$pathways()
            ->get()
            ->map(Closure::fromCallable([pathway::class, 'from_entity']))
            ->map(Closure::fromCallable([$this, 'unpacked_pathway']));
    }

    /**
     * Returns the details for for a pathway type.
     *
     * @param pathway $pathway
     *
     * @return array
     */
    private function unpacked_pathway(pathway $pathway): array {
        $details = [
            'classification' => $pathway->get_classification_name(),
            'sort_order' => $pathway->get_sortorder(),
            'status' => $pathway->get_status_name(),
            'title' => $pathway->get_title(),
            'type' => $pathway->get_path_type()
        ];

        // Criteria groups are tricky; need to formulate a set of details to prove
        // the copying succeeded but without using things that are definitely
        // different eg associated competencies.
        if ($pathway instanceof criteria_group) {
            $details['other'] = collection::new($pathway->get_criteria())
                ->map(
                    function (criterion $criterion): array {
                        return [
                            'aggr_method' => $criterion->get_aggregation_method(),
                            'aggr_params' => $criterion->get_aggregation_params(),
                            'aggr_reqd' => $criterion->get_aggregation_num_required(),
                            'singleuse' => $criterion->is_singleuse(),
                            'type' => $criterion->get_plugin_type(),
                            'title' => $criterion->get_title()
                        ];
                    }
                )
                ->all();
        } else {
            // Believe it or not criteria_group::get_short_description() returns
            // different strings _depending on the order in which the pathway's
            // criteria is retrieved from the database_. Which would obviously
            // lead to intermittent and unpredictable test failures. Hence that
            // detail is not used for comparing criteria groups.
            $details['desc'] = $pathway->get_short_description();
            $details['other'] = $pathway->get_summarized_criteria_set();
        }

        return $details;
    }
}
