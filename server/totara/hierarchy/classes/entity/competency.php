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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\entity;

use core\orm\collection;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_one;
use core\orm\entity\relations\has_one_through;
use core\orm\query\builder;
use totara_competency\user_groups;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway;
use totara_competency\entity\scale_aggregation;
use totara_core\advanced_feature;

// Currently only required to re-use the constants
global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

/**
 * Class competency
 *
 * @property string $shortname Short name
 * @property string $description Competency description
 * @property string $idnumber External systems ID number
 * @property int $frameworkid Framework ID
 * @property string $path Competency path in the hierarchy
 * @property int $parentid Parent competency ID
 * @property bool $visible Visible flag
 * @property int $timecreated Time created
 * @property int $timemodified Time modified
 * @property int $usermodified User modified
 * @property string $fullname Full competency name
 * @property int $depthlevel Depth level in the hierarchy
 * @property int $typeid Competency type ID
 * @property string $sortthread Sortorder
 * @property bool $totarasync Totara sync flag
 *
 * @property int $aggregationmethod Aggregation method
 * @property int $proficiencyexpected Expected proficiency
 * @property int $evidencecount Evidence count
 * @property int $copy_op_id bulk pathway copying operation id
 *
 * @property-read array $assigned_user_groups
 * @property-read competency[] $all_parents
 * @property-read int $children_count this property does only exist if \totara_hierarchy\entity\hierarchy_item_repository::with_children_count() got used
 * @property-read int $assignments_count
 * @property-read int[] $assign_availability
 * @property-read object[] $display_custom_fields
 *
 * @method static competency_repository repository()
 *
 * @property-read competency $parent Parent item
 * @property-read competency_framework $framework Framework
 * @property-read scale $scale Scale associated with this competency
 * @property-read competency_type $comp_type Competency type
 * @property-read string $scale_aggregation_type Scale aggregation type
 *
 * @property-read competency_achievement $achievement
 * @property-read collection $availability
 * @property-read pathway[]|collection $pathways
 * @property-read pathway[]|collection $active_pathways
 *
 * @package totara_competency\entity
 */
class competency extends hierarchy_item {

    public const TABLE = 'comp';

    public const ASSIGNMENT_CREATE_SELF = \competency::ASSIGNMENT_CREATE_SELF;
    public const ASSIGNMENT_CREATE_OTHER = \competency::ASSIGNMENT_CREATE_OTHER;

    /**
     * Get all parents
     *
     * @return array
     */
    public function get_all_parents_attribute(): array {
        $result = [];
        $parent_id = $this->parentid;
        $competency = $this;
        while (!empty($parent_id)) {
            $competency = $competency->parent()->one();
            $result[] = $competency;
            $parent_id = $competency->parentid;
        }
        return array_reverse($result);
    }

    /**
     * Get the parent framework.
     *
     * @return belongs_to
     */
    public function framework(): belongs_to {
        return $this->belongs_to(competency_framework::class, 'frameworkid');
    }

    /**
     * Related achievement, meant to be used with a user filter
     *
     * @return has_one
     */
    public function achievement(): has_one {
        return $this->has_one(competency_achievement::class, 'competency_id')
            ->where_in('status', [competency_achievement::ACTIVE_ASSIGNMENT, competency_achievement::ARCHIVED_ASSIGNMENT]);
    }

    /**
     * Get related scale
     *
     * @return has_one_through
     */
    public function scale(): has_one_through {
        return $this->has_one_through(
            scale_assignment::class,
            scale::class,
            'frameworkid',
            'frameworkid',
            'scaleid',
            'id'
        );
    }

    /**
     * If this is called this item will have a assigned_user_groups attribute loaded when to_array() is called
     *
     * @return $this
     */
    public function with_assigned_user_groups() {
        return $this->add_extra_attribute('assigned_user_groups');
    }

    /**
     * @return array
     */
    public function get_assigned_user_groups_attribute(): array {
        $assignments = assignment::repository()
            ->where('competency_id', $this->id)
            ->select('*')
            ->with_user_group_name()
            ->get();

        $user_group_names = [];
        foreach ($assignments as $assignment) {
            $name = $assignment->user_group_name;
            if ($assignment->user_group_type == user_groups::USER) {
                $user_name_fields = totara_get_all_user_name_fields();
                $user = new \stdClass();
                foreach ($user_name_fields as $field) {
                    $user->$field = isset($assignment->$field) ? $assignment->$field : '';
                }
                $name = fullname($user);
            }
            $user_group_names[] = $name;
        };

        return $user_group_names;
    }

    /**
     * Retrieve scale_aggregation
     *
     * @return string|null Scale aggregation type
     */
    public function get_scale_aggregation_type_attribute(): ?string {
        $scale_aggregation = $this->scale_aggregation;
        return $scale_aggregation ? $scale_aggregation->type : null;
    }

    /**
     * Related scale aggregation record
     *
     * (Confirm whether it's has one or many)
     *
     * @return has_one
     */
    public function scale_aggregation(): has_one {
        return $this->has_one(scale_aggregation::class, 'competency_id');
    }

    /**
     * Assignment availability related model
     *
     * @return has_many
     */
    public function availability(): has_many {
        return $this->has_many(assignment_availability::class, 'comp_id');
    }

    /**
     * Get the short achievement pathway warning message,
     * if there are any potential problems.
     *
     * @return string|null
     */
    public function get_pathway_warning_message_short(): ?string {
        if ($this->pathways_need_review()) {
            return get_string('achievement_paths_need_review', 'totara_competency');
        }

        return null;
    }

    /**
     * Get the long achievement pathway warning message,
     * if there are any potential problems.
     *
     * @return string|null
     */
    public function get_pathway_warning_message_long(): ?string {
        if ($this->pathways_need_review()) {
            return get_string('achievement_paths_need_review_long', 'totara_competency');
        }

        return null;
    }

    /**
     * Get assignment availability types
     *
     * @return array Of assignment availability types
     */
    protected function get_assign_availability_attribute(): array {
        return $this->availability->pluck('availability');
    }

    /**
     * Can this competency be assigned via the given assignment type?
     * This should only be used to check low level flags on the competency.
     * Any other condition checks should be done in the assignment model.
     *
     * @param string $assignment_type see constants in the assignment entity
     * @return bool
     */
    public function can_assign(string $assignment_type) {
        switch ($assignment_type) {
            case assignment::TYPE_SELF:
                $assignable = $this->can_assign_self();
                break;
            case assignment::TYPE_OTHER:
                $assignable = $this->can_assign_other();
                break;
            default:
                // For all other types we don't have a flag yet,
                // so we default to be able to assign
                $assignable = true;
                break;
        }

        return $assignable;
    }

    /**
     * Can this competency be assigned by users for themselves?
     *
     * @return bool
     */
    public function can_assign_self(): bool {
        return in_array(static::ASSIGNMENT_CREATE_SELF, $this->assign_availability);
    }

    /**
     * Can this competency be assigned by other users?
     *
     * @return bool
     */
    public function can_assign_other(): bool {
        return in_array(static::ASSIGNMENT_CREATE_OTHER, $this->assign_availability);
    }

    /**
     * Configured pathways for this competency
     *
     * @return has_many
     */
    public function pathways(): has_many {
        return $this->has_many(pathway::class, 'competency_id');
    }

    /**
     * Configured pathways for this competency
     *
     * @return has_many
     */
    public function active_pathways(): has_many {
        return $this->pathways()
            ->where('status', \totara_competency\pathway::PATHWAY_STATUS_ACTIVE)
            ->order_by('sortorder');
    }

    /**
     * check if the competency contain active criteria based pathways
     *
     * @return bool
     */
    public function has_criteria_based_pathway(): bool {
        return !!$this->active_pathways()
            ->where('path_type', 'criteria_group')
            ->count();
    }

    /**
     * Retrieve all custom field definitions and values for this competency
     *
     * @return object[] Array of custom field objects, with type, title and their
     */
    protected function get_display_custom_fields_attribute(): array {
        global $CFG;

        $custom_field_records = builder::table('comp_type_info_data', 'data')
            ->select(['data.*', 'field.datatype', 'field.hidden', 'field.fullname', 'field.shortname'])
            ->join(['comp_type_info_field', 'field'], 'fieldid', 'id')
            ->where('data.competencyid', $this->id)
            ->where('field.hidden', 0)
            ->order_by('field.sortorder')
            ->fetch();

        $fields_to_display = [];

        foreach ($custom_field_records as $field) {
            /** @var \customfield_base $field_class */
            $field_class = 'customfield_' . $field->datatype;
            require_once($CFG->dirroot . '/totara/customfield/field/' . $field->datatype . '/field.class.php');

            $fields_to_display[] = (object) [
                'type' => $field->datatype,
                'title' => format_string($field->fullname),
                'value' => $field_class::display_item_data($field->data, [
                    'prefix' => \competency::PREFIX,
                    'itemid' => $field->id,
                    'extended' => true,
                ]),
            ];
        }

        return $fields_to_display;
    }

    private function pathways_need_review(): bool {
        if (advanced_feature::is_disabled('competency_assignment')) {
            return false;
        }

        if (count($this->active_pathways) === 0) {
            return true;
        }

        foreach ($this->active_pathways as $pathway) {
            if (!$pathway->valid) {
                return true;
            }
        }

        return false;
    }

}
