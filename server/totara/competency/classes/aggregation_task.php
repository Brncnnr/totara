<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

use core\orm\entity\repository;
use core\orm\query\builder;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\entity\pathway_achievement;
use totara_hierarchy\entity\competency;

/**
 * Class to run the aggregation on a list of users and competencies.
 *
 * Which users and competencies to run on is determined by the table passed in.
 *
 * The task uses all records in the table provided and run aggregation on them.
 */
class aggregation_task {

    /**
     * @var aggregation_users_table
     */
    private $table;

    /**
     * @var bool
     */
    private $full_user_set;

    /**
     * @var pathway_evaluator_user_source
     */
    private $pw_user_id_source;

    /**
     * @var competency_aggregator_user_source
     */
    private $comp_user_id_source;

    /**
     * @param aggregation_users_table $table
     * @param bool $full_user_set set it to true if all users and competencies are in the table,
     *                            false if only a subset of rows is in the table
     */
    public function __construct(aggregation_users_table $table, bool $full_user_set) {
        $this->table = $table;
        $this->full_user_set = $full_user_set;
    }

    /**
     * @param int|null $aggregation_time if needed you can specify the time, defaults to current unix timestamp
     */
    public function execute(?int $aggregation_time = null) {
        $this->pw_user_id_source = new pathway_evaluator_user_source($this->table, $this->full_user_set);
        $this->comp_user_id_source = new competency_aggregator_user_source($this->table, $this->full_user_set);

        $aggregation_time = $aggregation_time ?? time();

        $competencies_with_archived_pws = $this->get_competency_with_archived_pathways();
        $competencies_with_active_pws = $this->get_competency_with_active_pathways_for_assigned_users();

        // We want to combine both results to go only once through it
        $competency_ids = array_unique(
            array_merge(
                array_keys($competencies_with_archived_pws),
                array_keys($competencies_with_active_pws)
            )
        );

        /** @var competency $competency */
        foreach ($competency_ids as $competency_id) {
            $competency_to_aggregate = null;

            // Aggregate each archived pathway which still has active pathway achievements
            // before aggregating the competency itself
            $competency = $competencies_with_archived_pws[$competency_id] ?? null;
            if ($competency) {
                $competency_to_aggregate = $competency;
                foreach ($competency->pathways as $pathway_entity) {
                    // Avoid another query by attaching the competency
                    $pathway_entity->relate('competency', $competency);
                    $pathway = pathway_factory::from_entity($pathway_entity);
                    $pathway->archive_pathway_achievements();
                }

                // Mark all rows for that competency as has_changed
                // so that it will be picked up by the competency achievement aggregation
                $this->pw_user_id_source->mark_all_users_with_competency($competency->id);
            }

            // Aggregate each active pathway before aggregating the competency itself
            $competency = $competencies_with_active_pws[$competency_id] ?? null;
            if ($competency) {
                $competency_to_aggregate = $competency;
                foreach ($competency->active_pathways as $pathway_entity) {
                    // Avoid another query by attaching the competency
                    $pathway_entity->relate('competency', $competency);
                    $pathway = pathway_factory::from_entity($pathway_entity);

                    $pw_evaluator = pathway_evaluator_factory::create($pathway, $this->pw_user_id_source);
                    $pw_evaluator->aggregate($aggregation_time);
                }
            }

            $this->aggregate_competency_achievements($competency_to_aggregate, $aggregation_time);
        }
    }

    /**
     * @return competency[]
     */
    private function get_competency_with_active_pathways_for_assigned_users(): array {
        $pathway_types = plugin_types::get_enabled_plugins('pathway', 'totara_competency');

        $uses_process_key = $this->table->get_process_key_column() && $this->table->get_process_key_value();

        // Make sure we only load competencies we have an entry in the queue for
        $queue_builder = builder::table($this->table->get_table_name())
            ->where_field($this->table->get_competency_id_column(), "c.id")
            ->when($uses_process_key, function (builder $builder) {
                $builder->where($this->table->get_process_key_column(), $this->table->get_process_key_value());
            });

        // Make sure that we only query competencies which have an active pathway
        $pathway_builder = builder::table(pathway_entity::TABLE)
            ->where_field('competency_id', "c.id")
            ->where('path_type', $pathway_types)
            ->where('status', pathway::PATHWAY_STATUS_ACTIVE);

        $result =  competency::repository()
            ->as('c')
            ->where_exists($queue_builder)
            ->where_exists($pathway_builder)
            ->order_by('depthlevel', 'desc')
            ->order_by('id', 'asc')
            ->with([
                // We want to get all active pathway entities in one go, using the relation
                'active_pathways' => function (repository $repository) use ($pathway_types) {
                    $repository->where('path_type', $pathway_types);
                }
            ])
            ->get();

        return $result->key_by('id')->all(true);
    }

    /**
     * @return competency[]
     */
    private function get_competency_with_archived_pathways(): array {
        // We are looking for competencies with archived pathways which have still active pathway_achievements
        $pathway_builder = builder::table(pathway_entity::TABLE)
            ->join([pathway_achievement::TABLE, 'pwa'], 'id', 'pathway_id')
            ->where_field('competency_id', "c.id")
            ->where('pwa.status', pathway_achievement::STATUS_CURRENT)
            ->where('status', pathway::PATHWAY_STATUS_ARCHIVED);

        $result = competency::repository()
            ->as('c')
            ->where_exists($pathway_builder)
            ->order_by('depthlevel', 'desc')
            ->order_by('id', 'asc')
            ->with([
                // We want to get all archived pathway entities in one go, using the relation
                'pathways' => function (repository $repository) {

                    $achievement_builder = builder::table(pathway_achievement::TABLE)
                        ->where('status', pathway_achievement::STATUS_CURRENT)
                        ->where_field('pathway_id', "pw.id");

                    $repository
                        ->as('pw')
                        ->where_exists($achievement_builder)
                        ->where('status', pathway::PATHWAY_STATUS_ARCHIVED);
                }
            ])
            ->get();

        return $result->key_by('id')->all(true);
    }

    /**
     * Perform competency achievement aggregation for all users who were marked as having changes
     *
     * @param competency $competency
     * @param int $aggregation_time
     */
    private function aggregate_competency_achievements(competency $competency, int $aggregation_time) {
        $configuration = new achievement_configuration($competency);
        $competency_aggregator = new competency_achievement_aggregator($configuration, $this->comp_user_id_source);
        $competency_aggregator->aggregate($aggregation_time);
    }

}
