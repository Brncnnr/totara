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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

use aggregation_highest\highest;
use aggregation_latest_achieved\latest_achieved;
use coding_exception;
use core\orm\collection;
use core\orm\query\builder;
use criteria_childcompetency\childcompetency;
use criteria_linkedcourses\linkedcourses;
use pathway_criteria_group\criteria_group;
use pathway_criteria_group\entity\criteria_group_criterion as criteria_group_criterion_entity;
use pathway_learning_plan\learning_plan;
use totara_competency\entity\configuration_change;
use totara_competency\entity\pathway as pathway_entity;
use totara_core\advanced_feature;
use totara_criteria\criterion;
use totara_criteria\entity\criterion as criterion_entity;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;

/**
 * Class handling legacy aggregation method on the competency
 */
class legacy_aggregation {

    /** @var competency */
    private $competency;

    /** @var scale_value */
    private $min_proficient_value;

    public function __construct(competency $competency) {
        $this->competency = $competency;
    }

    /**
     * Apply the legacy aggregation method making sure that the criteria
     * for linked courses and child competencies match the aggregation method
     * set on the competency.
     *
     * OFF     - Removes all linked courses and child competency criteria
     * ALL/ANY - Makes sure that there's at least one child competency and
     *           linked course criteria each and sets it's aggregation method to ALL or ANY_N
     */
    public function apply() {
        // We only want this to be happening if we are not on perform
        if (advanced_feature::is_enabled('competency_assignment')) {
            return;
        }

        $aggregation_method = $this->get_mapped_aggregation_method();
        if (is_null($aggregation_method)) {
            $this->remove_criteria();
        } else {
            $this->update_or_create_default_criteria(new linkedcourses());
            $this->update_or_create_default_criteria(new childcompetency());
        }

        // Log change and queue all users
        configuration_change::add_competency_entry(
            $this->competency->id,
            configuration_change::CHANGED_COMPETENCY_AGGREGATION,
            time()
        );
    }

    /**
     * Get minimum proficient scale value for current competency
     *
     * @return scale_value
     */
    private function get_minimum_proficient_value(): scale_value {
        if (empty($this->min_proficient_value)) {
            /** @var scale $scale */
            $scale = scale::repository()
                ->join(['comp_scale_assignments', 'sa'], 'id', 'scaleid')
                ->where('sa.frameworkid', $this->competency->frameworkid)
                ->one();

            $this->min_proficient_value = $scale->min_proficient_value;
        }
        return $this->min_proficient_value;
    }

    /**
     * Get aggregation method of current competency mapped to criterion aggregation method
     *
     * @return int|null
     */
    private function get_mapped_aggregation_method(): ?int {
        global $CFG;
        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');

        switch ($this->competency->aggregationmethod) {
            case \competency::AGGREGATION_METHOD_ANY:
                return criterion::AGGREGATE_ANY_N;
            case \competency::AGGREGATION_METHOD_ALL:
                return criterion::AGGREGATE_ALL;
            case \competency::AGGREGATION_METHOD_OFF:
            case null:
                return null;
        }

        throw new coding_exception('Unknown aggregation method, only supporting OFF, ANY or ALL.');
    }

    /**
     * @param criterion $criterion
     * @return criterion_entity[]|collection
     */
    private function get_existing_criteria(criterion $criterion): collection {
        return criterion_entity::repository()
            // This joins ensures that we only tackle criteria connected to competencies
            ->join([criteria_group_criterion_entity::TABLE, 'cgc'], 'id', 'criterion_id')
            ->join([pathway_entity::TABLE, 'pw'], 'cgc.criteria_group_id', 'pw.path_instance_id')
            ->where('plugin_type', $criterion->get_plugin_type())
            ->where('pw.competency_id', $this->competency->id)
            ->where('pw.status', pathway::PATHWAY_STATUS_ACTIVE)
            ->get();
    }

    /**
     * Remove all linked courses and child competency criteria and
     * trigger clean up of empty pathways afterwards.
     *
     * This matches the behaviour of setting the legacy aggregation method
     * on the competency to OFF
     */
    private function remove_criteria() {
        // Find all criteria group criterions of the given type
        $criteria_group_criteria = criteria_group_criterion_entity::repository()
            // This joins ensures that we only tackle criteria connected to competencies
            ->join([pathway_entity::TABLE, 'pw'], 'criteria_group_id', 'pw.path_instance_id')
            ->where('criterion_type', [
                (new childcompetency())->get_plugin_type(),
                (new linkedcourses())->get_plugin_type()
            ])
            ->where('pw.competency_id', $this->competency->id)
            ->where('pw.status', pathway::PATHWAY_STATUS_ACTIVE)
            ->get();

        $criterions_to_delete = [];
        // First delete all rows in cireria_group_criterion
        foreach ($criteria_group_criteria as $criteria_group_criterion) {
            $criterions_to_delete[] = $criteria_group_criterion->criterion_id;
            $criteria_group_criterion->delete();
        }

        // Then delete all related rows in totara_criteria
        criterion_entity::repository()
            ->where('id', $criterions_to_delete)
            ->delete();

        // Clean up empty leftovers
        criteria_group::archive_empty_pathways();
    }

    /**
     * If there are existing criteria then update them otherwise create new ones
     * @param criterion $criterion
     */
    private function update_or_create_default_criteria(criterion $criterion) {
        $aggregation_method = $this->get_mapped_aggregation_method();

        $criteria = $this->get_existing_criteria($criterion);
        if ($criteria->count()) {
            // Update existing criteria
            foreach ($criteria as $criterion_entity) {
                $criterion = $criterion::fetch_from_entity($criterion_entity);
                $criterion->set_aggregation_method($aggregation_method);
                $criterion->save();
            }
        } else {
            $this->create_default_criteria($criterion);
        }
    }

    /**
     * Create the set of default pathways needed for each competency, which
     * consists of one pathway with a linkedcourses criterion and another one with a
     * childcompetency criterion. This mirrors the pre-perform aggregation behaviour.
     *
     * @param scale|null $scale needed to get the min_proficient_value, if omitted it's lazily loaded
     * @param bool $update_items true to update items in criteria immediately
     */
    public function create_default_pathways(scale $scale = null, bool $update_items = true) {
        $configuration = new achievement_configuration($this->competency);
        if ($configuration->has_aggregation_type()) {
            // If there's already an aggregation type chances are
            // high that some criteria are already set.
            // We only want to set the defaults on a criteria with no
            // pathways set
            return;
        }

        // Use the default site setting for the legacy aggregation method for new pathways
        $default_aggregation = get_config('totara_competency', 'legacy_aggregation_method');
        if ($default_aggregation == 1) {
            $aggregation_type = latest_achieved::aggregation_type();
        } else {
            $aggregation_type = highest::aggregation_type();
        }

        $configuration->set_aggregation_type($aggregation_type);
        $configuration->save_aggregation();

        if ($this->should_add_learning_plans()) {
            $lp_pathway = new learning_plan();
            $lp_pathway->set_competency($this->competency);
            $lp_pathway->save();
        }

        if ($scale) {
            $min_proficient_value = $scale->min_proficient_value;
        } else {
            $min_proficient_value = $this->get_minimum_proficient_value();
        }

        $this->create_default_criteria(new linkedcourses(), $min_proficient_value)
            ->create_default_criteria(new childcompetency(), $min_proficient_value);
    }

    private function should_add_learning_plans(): bool {
        if (!advanced_feature::is_enabled('competency_assignment')) {
            // If perform isn't enabled, we'll need to add the learning plan pathway here since users will not be able
            // to access an interface to add them themselves if they need them.
            return true;
        }

        if (advanced_feature::is_disabled('learningplans')) {
            return false;
        }

        return builder::table('dp_plan_competency_assign')->exists();
    }

    /**
     * Creates a new criteria group with a given criterion, i.e. child competency or linked cours
     *
     * @param criterion $criterion
     * @param scale_value $min_proficient_scale_value if omitted it gets the current valud from the competency
     *
     * @return $this
     * @throws coding_exception
     */
    public function create_default_criteria(criterion $criterion, scale_value $min_proficient_scale_value = null) {
        $aggregation_method = $this->get_mapped_aggregation_method();
        if (is_null($aggregation_method)) {
            // In case of OFF do nothing
            return $this;
        }

        if (empty($min_proficient_scale_value)) {
            $min_proficient_scale_value = $this->get_minimum_proficient_value();
        }

        $criterion->set_aggregation_method($aggregation_method);
        $criterion->set_competency_id($this->competency->id);

        $group = new criteria_group();
        $group->set_competency($this->competency);
        $group->set_scale_value($min_proficient_scale_value);
        $group->add_criterion($criterion);
        $group->save();

        return $this;
    }

}
