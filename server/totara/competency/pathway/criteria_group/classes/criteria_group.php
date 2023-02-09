<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace pathway_criteria_group;

use coding_exception;
use core\orm\collection;
use core\orm\query\builder;
use \core\orm\entity\repository;
use pathway_criteria_group\entity\criteria_group as criteria_group_entity;
use pathway_criteria_group\entity\criteria_group_criterion as criteria_group_criterion_entity;
use stdClass;
use totara_competency\base_achievement_detail;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\pathway;
use totara_competency\pathway_factory;
use totara_competency\plugin_types;
use totara_criteria\criterion;
use totara_criteria\criterion_factory;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * Class handling criteria_group pathways
 */
class criteria_group extends pathway {

    public const CLASSIFICATION = self::PATHWAY_SINGLE_VALUE;

    /** @var scale_value Scale value completion of the criteria in this group leads to */
    private $scale_value;

    /** @var int $last_key Last key assigned to a criterion */
    private static $last_key = 0;

    /** @var criterion[] Array of criteria in the group */
    private $criteria = [];

    /** @var [int] Ids of saved criterions */
    private $critids = [];

    /********************************************************************
     * Instantiation
     ********************************************************************/

    /**
     * Load the criteria_group configuration from the database
     */
    protected function fetch_configuration(): void {
        if (empty($this->get_path_instance_id())) {
            return;
        }

        // Load the group together with the related criterion records
        // and the scale values
        /** @var criteria_group_entity $criteria_group */
        $criteria_group = criteria_group_entity::repository()
            ->with('criterions')
            ->with('scale_value')
            ->where('id', $this->get_path_instance_id())
            ->one();

        // A group could be empty which happens when the pathway got archived
        if ($criteria_group) {
            if (!empty($criteria_group->scale_value)) {
                $this->set_scale_value($criteria_group->scale_value);
            }

            foreach ($criteria_group->criterions as $row) {
                $this->add_criterion(criterion_factory::fetch($row->criterion_type, $row->criterion_id));
            }
        }
    }

    /****************************************************************************
     * Saving
     ****************************************************************************/

    /**
     * Has the pathway specific configuration changed?
     *
     * @return bool
     */
    protected function configuration_is_dirty(): bool {
        if (empty($this->get_path_instance_id())) {
            return true;
        }

        /** @var criteria_group_criterion_entity[]|collection $criteria_rows */
        $criteria_rows = criteria_group_criterion_entity::repository()
            ->where('criteria_group_id', $this->get_path_instance_id())
            ->get();

        if (count($this->get_criteria()) != count($criteria_rows)) {
            return true;
        }

        $existing_critids = [];
        foreach ($criteria_rows as $row) {
            $existing_critids[] = $row->criterion_id;
        }

        foreach ($this->get_criteria() as $crit) {
            if (!in_array($crit->get_id(), $existing_critids)) {
                return true;
            }

            if ($crit->is_dirty()) {
                return true;
            }
        }

        return false;
    }


    /**
     * Save the criteria_group, associated criteria and approval roles
     */
    protected function save_configuration() {
        global $DB;

        if (empty($this->get_scale_value()) || empty($this->get_scale_value()->id)) {
            throw new coding_exception('A criteria_group pathway requires a valid scale value');
        }

        // Not checking is_dirty here as it is checked in parent::save()
        // We will only get here if something changed

        // If there are no criteria - delete this pathway
        if ($this->get_id() && count($this->get_criteria()) == 0) {
            // It's already archived, don't do anything in this case
            if (!$this->is_active()) {
                return $this;
            }
            // First 'save' the criteria - which will delete the now removed criteria
            $this->save_criteria();

            // Now delete the pathway
            return $this->delete(true);
        }

        // Create new criteria_group instance
        $record = new stdClass();
        $record->scale_value_id = $this->get_scale_value()->id;
        $record->status = static::PATHWAY_STATUS_ACTIVE;
        $record->timemodified = time();

        if ($this->get_path_instance_id()) {
            $record->id = $this->get_path_instance_id();
            $DB->update_record('pathway_criteria_group', $record);
        } else {
            $this->set_path_instance_id($DB->insert_record('pathway_criteria_group', $record));
        }

        $this->save_criteria();
    }

    /**
     * Save the group_criteria to the db
     */
    private function save_criteria() {
        global $DB;

        if (empty($this->get_path_instance_id())) {
            throw new \coding_exception('Cannot save criteria without a saved pathway to link to.');
        }

        $critrows = $DB->get_records_menu(
            'pathway_criteria_group_criterion',
            ['criteria_group_id' => $this->get_path_instance_id()],
            '',
            'criterion_id, criterion_type'
        );

        $toinsert = [];
        $this->critids = [];

        foreach ($this->get_criteria() as $criterion) {
            $criterion->save();

            $criterion_id = $criterion->get_id();

            if (!isset($critrows[$criterion_id])) {
                $toinsert[] = (object)[
                    'criteria_group_id' => $this->get_path_instance_id(),
                    'criterion_type' => $criterion->get_plugin_type(),
                    'criterion_id' => $criterion->get_id(),
                ];
            }

            $this->critids[] = $criterion_id;

            unset($critrows[$criterion->get_id()]);
        }

        if (!empty($toinsert)) {
            $DB->insert_records('pathway_criteria_group_criterion', $toinsert);
        }

        // Delete removed criteria
        if (!empty($critrows)) {
            foreach ($critrows as $id => $type) {
                $criterion = criterion_factory::fetch($type, $id);
                $criterion->delete();
            }
            $DB->delete_records_list('pathway_criteria_group_criterion', 'criterion_id', array_keys($critrows));
        }
    }

    /**
     * 'Delete' the pathway specific detail by archiving it
     */
    protected function delete_configuration(): void {
        global $DB;

        if (empty($this->get_path_instance_id())) {
            // Never saved
            return;
        }

        $trans = $DB->start_delegated_transaction();

        foreach ($this->criteria as $crit) {
            $crit->delete();
        }

        $DB->delete_records(
            'pathway_criteria_group_criterion',
            ['criteria_group_id' => $this->get_path_instance_id()]
        );

        $DB->delete_records(
            'pathway_criteria_group',
            ['id' => $this->get_path_instance_id()]
        );

        $trans->allow_commit();

        $this->set_path_instance_id(null);
        $this->replace_criteria([]);
    }


    /****************************************************************************
     * Getters and setters
     ****************************************************************************/

    /**
     * Returns the scale value associated with this pathway.
     *
     * @return scale_value|null ?scale_value A null return value indicates that any scale value may be returned
     */
    public function get_scale_value(): ?scale_value {
        return $this->scale_value;
    }

    /**
     * Set the scale value associated with this pathway.
     *
     * @param scale_value $scale_value
     * @return $this
     */
    public function set_scale_value(scale_value $scale_value): pathway {
        $this->scale_value = $scale_value;
        $this->validated = false;
        return $this;
    }

    /**
     * Get all criteria
     *
     * @return criterion[]
     */
    public function get_criteria(): array {
        return $this->criteria;
    }

    /**
     * Add a criterion that must be completed
     *
     * @param criterion $criterion
     * @return $this
     */
    public function add_criterion(criterion $criterion): pathway {
        // Check that the criterion ids are unique.
        // If all are new - no further uniqueness checks

        $id = $criterion->get_id();
        if (empty($id) || !in_array($id, $this->critids)) {
            $key = static::$last_key++;
            $this->criteria[$key] = $criterion;

            if (!empty($id)) {
                $this->critids[] = $id;
                $this->validated = false;
            }
        }


        return $this;
    }

    /**
     * Remove the specified criterion
     *
     * @param int $key Assigned key of the criterion to remove
     * @return $this
     */
    public function remove_criterion(int $key): pathway {
        $id = $this->criteria[$key]->get_id();
        if (!empty($id)) {
            $ids = array_flip($this->critids);
            unset($ids[$id]);
            $this->critids = array_flip($ids);
        }

        unset($this->criteria[$key]);
        $this->validated = false;
        return $this;
    }

    /**
     * Replace all associated criteria with the specified set
     *
     * @param array criteria New set of criteria
     * @return $this
     */
    public function replace_criteria(array $criteria): pathway {
        $this->criteria = $criteria;

        // Build critids and last_key
        $key = $last_key = 0;
        $this->critids = [];

        foreach ($criteria as $key => $crit) {
            // All should have ids at this point
            $id = $crit->get_id();
            if (!empty($id)) {
                $this->critids[] = $id;
            }

            if ($key > $last_key) {
                $last_key = $key;
            }
        }

        static::$last_key = $last_key;
        $this->validated = false;

        return $this;
    }

    /**
     * Return a summary of the criteria associated with this pathway.
     *
     * @return array List of summarized criteria objects associated with the pathway
     */
    public function get_summarized_criteria_set(): array {
        $result = array_map(
            function (criterion $criterion) {
                return $criterion->display_instance()->get_configuration();
            },
            $this->get_criteria()
        );

        return $result;
    }

    /**
     * Validate the pathway configuration
     * @return bool
     */
    public function is_configuration_valid(): bool {
        if (count($this->get_criteria()) == 0) {
            return false;
        }

        foreach ($this->get_criteria() as $criterion) {
            $criterion->validate();
            if (!$criterion->is_valid()) {
                return false;
            }
        }

        return true;
    }


    /*******************************************************************************************************
     * Data exporting
     *******************************************************************************************************/

    /**
     * @return array
     */
    public static function export_criteria_types(): array {
        $display_order = array_flip(['onactivate', 'linkedcourses', 'coursecompletion', 'childcompetency', 'othercompetency']);

        $types = plugin_types::get_enabled_plugins('criteria', 'totara_criteria');
        $types = array_map(function ($type) {
            $criterion = criterion_factory::create($type);
            return [
                'type' => $criterion->get_plugin_type(),
                'title' => $criterion->get_title(),
                'singleuse' => (int)$criterion->is_singleuse(),
                'criterion_templatename' => $criterion->get_edit_template(),
            ];
        }, $types);

        usort($types, function ($type1, $type2) use ($display_order) {
            // If new plugin, add at back
            $order1 = $display_order[$type1['type']] ?? 99;
            $order2 = $display_order[$type2['type']] ?? 99;
            return $order1 - $order2;
        });

        return array_values($types);
    }
    /**
     * Return the name of the template to use for editing this pathway
     *
     * @return string Template name
     */
    public function get_edit_template(): string {
        return 'pathway_criteria_group/pathway_criteria_group_edit';
    }

    /**
     * Export detail for editing the pathway
     *
     * @return array containing templatedata
     */
    public function export_edit_detail(): array {
        $result = $this->export_edit_overview();
        $result['scalevalue'] = $this->get_scale_value()->id;
        $result['criteria'] = $this->export_criteria();

        return $result;
    }

    /**
     * Export information of the criteria in this group for editing
     *
     * @return array
     */
    public function export_criteria(): array {
        $result = [];

        $show_and = false;
        foreach ($this->criteria as $criterion) {
            $criterion = $criterion->export_edit_detail();
            if ($show_and) {
                $criterion['showand'] = true;
            } else {
                $show_and = true;
            }

            $result[] = $criterion;
        }

        return $result;
    }

    /**
     * Get a short description of the content of this pathway
     *
     * @return string  Short description
     */
    public function get_short_description(): string {
        $criteria_types = [];
        foreach ($this->criteria as $criterion) {
            if (!isset($criteria_types[$criterion->get_plugin_type()])) {
                $criteria_types[$criterion->get_plugin_type()] = $criterion->get_title();
            }
        }

        $glue = ' ' . get_string('and', 'totara_competency') . ' ';
        return implode($glue, $criteria_types);
    }

    /**
     * Retrieve the current configuration from the database
     *
     * @param int|null $id
     * @return stdClass|null
     */
    public static function dump_pathway_configuration(?int $id = null) {
        global $DB;

        if (!is_null($id) && $result = $DB->get_record('pathway_criteria_group', ['id' => $id])) {
            $result->criteria = $DB->get_records('pathway_criteria_group_criterion', ['criteria_group_id' => $id]);
            foreach ($result->criteria as $id => $criterion) {
                $criterion->detail = criterion_factory::dump_criterion_configuration($criterion->criterion_type,
                    $criterion->criterion_id
                );
            }

            return $result;
        }

        return null;
    }



    /*************************************************************************************************
     * Per user
     * ***********************************************************************************************/

    /**
     * Return obtained scale value for this user
     * (Not naming it aggregate to avoid confusion of aggregation of all pathway results for this user)
     *
     * Returns achievement detail which will contain the scale value id achieved.
     * If a value was achieved, related info will include all criteria that were satisfied.
     * If no value was achieved, related info will be empty.
     *
     * @param int $user_id User to calculate the results for
     * @return base_achievement_detail
     */
    public function aggregate_current_value(int $user_id): base_achievement_detail {
        if (is_null($this->scale_value) || empty($this->criteria)) {
            return new achievement_detail();
        }

        $achievement_detail = new achievement_detail();

        $result = true;
        $last_achieved_date = null;
        foreach ($this->criteria as $criterion) {
            $crit_satisfied = $criterion->aggregate($user_id);
            if ($crit_satisfied) {
                $achievement_detail->add_completed_criterion($criterion);
                if ($criterion->get_last_achieved_date() > $last_achieved_date) {
                    $last_achieved_date = $criterion->get_last_achieved_date();
                }
            }

            $result = $result && $crit_satisfied;
            // If any criterion not yet satisfied by the user, we can stop
            if (!$result) {
                break 1;
            }
        }

        if ($result) {
            $achievement_detail->set_scale_value_id($this->scale_value->id)
                ->set_achieved_at($last_achieved_date);
        } else {
            // Remove any achievement data if the user didn't actually achieve a value.
            $achievement_detail->set_related_info([]);
        }

        return $achievement_detail;
    }

    /**
     * Does this instance contain any singleuse criteria
     *
     * @return bool
     */
    public function has_singleuse_criteria(): bool {
        foreach ($this->criteria as $criterion) {
            if ($criterion->is_singleuse()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Archive empty pathways, means all pathways with no criteria
     *
     * @return void
     */
    public static function archive_empty_pathways() {
        // Clean up empty pathways
        builder::get_db()->transaction(function () {
            // Find and delete criteria group pathway records which are empty
            $empty_pathways = pathway_entity::repository()
                ->left_join([criteria_group_entity::TABLE, 'cg'], 'path_instance_id', 'id')
                ->left_join([criteria_group_criterion_entity::TABLE, 'cgc'], 'path_instance_id', 'criteria_group_id')
                ->where('path_type', 'criteria_group')
                ->where(function (builder $builder) {
                    // We want to get archive any pathway which is empty,
                    // has no group linked or empty groups
                    $builder->or_where('cg.id', null)->or_where('cgc.id', null);
                })
                ->with('competency')
                ->get();

            foreach ($empty_pathways as $empty_pathway) {
                $pathway = pathway_factory::from_entity($empty_pathway);
                $pathway->delete(false);
            }
        });
    }

    /**
     * Get number of pathways using scale value.
     *
     * @param int $value_id Scale value id.
     *
     * @return int
     */
    public static function get_pathway_count_by_scale_value_id(int $value_id): int {
        return self::get_pathways_by_scale_value_builder($value_id)->count();
    }

    /**
     * Delete Pathways using the scale values.
     *
     * @param int $value_id Scale value id.
     *
     * @return array
     */
    public static function delete_pathways_with_scale_value_id(int $value_id): array {
        $pathways = self::get_pathways_by_scale_value_builder($value_id)->with('competency')->get();
        $pathways_deleted = [];

        if (!empty($pathways)) {
            foreach ($pathways as $pathway) {
                $pathway_instance = pathway_factory::from_entity($pathway);
                $pathways_deleted[] = $pathway_instance->delete();
            }
        }

        return $pathways_deleted;
    }

    /**
     * Get reusable query builder to fetch pathways by scale value.
     *
     * @param int $value_id Scale value id.
     *
     * @return repository
     *
     * @throws coding_exception
     */
    private static function get_pathways_by_scale_value_builder(int $value_id): repository {
        return $scale_value_pathways = pathway_entity::repository()
            ->where('path_type', 'criteria_group')
            ->join([criteria_group_entity::TABLE, 'cg'], 'path_instance_id', 'id')
            ->where('cg.scale_value_id', $value_id);
    }

    /*******************************************************************************************************
     * Pathway copying
     *******************************************************************************************************/
    /**
     * @inheritDoc
     */
    public function copy_to_competency(competency $competency): competency {
        // Cannot assume caller already fetched the configuration; it is possible
        // for the caller to do (new manual())->copy_to_competency()!
        $this->fetch_configuration();

        $competency_id = $competency->id;
        $pathway = collection::new($this->get_criteria())
            ->reduce(
                function (
                    self $pathway, criterion $criterion
                ) use ($competency_id): self {
                    $template = $criterion->make_template();
                    if (!$template) {
                        return $pathway;
                    }

                    $new_criterion = $template
                        ->set_competency_id($competency_id)
                        ->save();

                    return $pathway->add_criterion($new_criterion);
                },
                new self()
            )
            ->set_competency($competency)
            ->set_sortorder($this->get_sortorder())
            ->set_path_instance_id(null)
            ->set_status($this->get_status());

        $scale = $this->get_scale_value();
        if ($scale) {
            $pathway->set_scale_value($scale);
        }

        $pathway->save();

        return $competency;
    }
}
