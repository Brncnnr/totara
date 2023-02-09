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
 * @package totara_pathway
 */

namespace pathway_manual;

use pathway_manual\entity\rating;
use pathway_manual\entity\role as role_entity;
use pathway_manual\models\roles\role;
use pathway_manual\models\roles\role_factory;
use totara_competency\base_achievement_detail;
use totara_competency\pathway;
use totara_hierarchy\entity\competency;


class manual extends pathway {

    /**
     * Types of aggregation available in manual
     */
    public const AGGREGATE_ALL = 1;
    public const AGGREGATE_ANY_N = 2;
    public const AGGREGATE_LAST = 3;

    public const CLASSIFICATION = self::PATHWAY_MULTI_VALUE;

    /**
     * @var role[]
     */
    private $roles = [];

    /********************************************************************
     * Instantiation
     ********************************************************************/

    /**
     * Load the manual configuration from the database
     */
    protected function fetch_configuration(): void {
        global $DB;

        $this->set_roles(
            $DB->get_fieldset_select(
                'pathway_manual_role',
                'role',
                'path_manual_id = :path_manual_id',
                ['path_manual_id' => $this->get_path_instance_id()]
            )
        );
    }

    /****************************************************************************
     * Saving
     ****************************************************************************/

    /**
     * Save the configuration
     */
    protected function save_configuration() {
        global $DB;

        // Not checking is_dirty here as it is checked in parent::save()

        $main = new \stdClass();
        $main->aggregation_method = self::AGGREGATE_LAST; // Default in v1
        $main->status = static::PATHWAY_STATUS_ACTIVE;
        $main->timemodified = time();

        if (empty($this->get_path_instance_id())) {
            $this->set_path_instance_id($DB->insert_record('pathway_manual', $main));
        } else {
            // Updating the timemodified as some of the roles changed
            $main->id = $this->get_path_instance_id();
            $DB->update_record('pathway_manual', $main);
        }

        // We want the role to be first in the select statement so that arrays are keyed that way.
        $previous_role_records = $DB->get_records('pathway_manual_role',
            ['path_manual_id' => $this->get_path_instance_id()],
            '',
            'role, id, path_manual_id'
        );

        // Create this object once and reuse it for inserting new records.
        $new_record = new \stdClass();
        $new_record->path_manual_id = $this->get_path_instance_id();
        foreach ($this->roles as $role) {
            if (!isset($previous_role_records[$role::get_name()])) {
                $new_record->role = $role::get_name();
                $DB->insert_record('pathway_manual_role', $new_record);
            } else {
                unset($previous_role_records[$role::get_name()]);
            }
        }

        // Remaining previous records are not meant to be there.
        if (!empty($previous_role_records)) {
            list($insql, $inparams) = $DB->get_in_or_equal(array_keys($previous_role_records), SQL_PARAMS_NAMED);

            $select =
                "path_manual_id = :path_manual_id
                 AND role $insql";

            $params = array_merge(['path_manual_id' => $this->get_path_instance_id()], $inparams);

            $DB->delete_records_select('pathway_manual_role', $select, $params);
        }
    }

    /**
     * Determine whether there are any difference between the instance
     * and the stored values
     *
     * @return bool
     */
    protected function configuration_is_dirty(): bool {
        global $DB;

        if (empty($this->get_path_instance_id())) {
            // New instance is considered dirty
            return true;
        }

        // We want the role to be first in the select statement so that arrays are keyed that way.
        $previous_role_records = $DB->get_records('pathway_manual_role',
            ['path_manual_id' => $this->get_path_instance_id()],
            '',
            'role, id, path_manual_id'
        );

        $has_changes = count($previous_role_records) != count($this->roles);
        if (!$has_changes) {
            // Compare one by one
            foreach ($this->roles as $role) {
                if (!isset($previous_role_records[$role::get_name()])) {
                    $has_changes = true;
                    break;
                } else {
                    unset($previous_role_records[$role::get_name()]);
                }
            }

            $has_changes = $has_changes || count($previous_role_records) > 0;
        }

        return $has_changes;
    }


    /**
     * Delete the pathway specific detail
     */
    protected function delete_configuration(): void {
        global $DB;

        $DB->delete_records('pathway_manual_role', ['path_manual_id' => $this->get_path_instance_id()]);
        $DB->delete_records('pathway_manual', ['id' => $this->get_path_instance_id()]);
        $this->set_path_instance_id(null);
    }


    /**************************************************************************
     * Aggregation
     **************************************************************************/

    /**
     * @param int $user_id
     * @return achievement_detail
     */
    public function aggregate_current_value(int $user_id): base_achievement_detail {
        /** @var null|rating $rating */
        $rating = rating::repository()
            ->where('competency_id', $this->get_competency()->id)
            ->where('user_id', $user_id)
            ->where_in('assigned_by_role', $this->get_role_names())
            ->order_by('id', 'desc')
            ->first();

        $achievement_detail = new achievement_detail();
        $achievement_detail->add_rating($rating);

        return $achievement_detail;
    }


    /****************************************************************************
     * Getters and setters
     ****************************************************************************/

    /**
     * Set roles
     *
     * @param string[] $roles Array of role classes (e.g. [self_role::class]) or names (e.g. ['self'])
     * @return $this
     */
    public function set_roles(array $roles): pathway {
        $this->roles = role_factory::create_multiple($roles);
        $this->validated = false;
        return $this;
    }

    /**
     * Get roles
     *
     * @return role[]
     */
    public function get_roles() {
        return $this->roles;
    }

    /**
     * Get role names.
     *
     * @return string[]
     */
    public function get_role_names() {
        return array_map(function (role $role) {
            return $role::get_name();
        }, $this->roles);
    }

    /**
     * Does this pathway have the specified role enabled for it?
     *
     * @param role $role
     * @return bool
     */
    public function has_role(role $role) {
        foreach ($this->roles as $enabled_roles) {
            if ($role instanceof $enabled_roles) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return a summary of the criteria associated with this pathway.
     * The returned set will always contain only a single element with the roles
     * that may give a manual rating returned as items
     *
     * @return array List of summarized criteria objects associated with the pathway
     */
    public function get_summarized_criteria_set(): array {
        $result = new \stdClass();
        $result->item_type = $this->get_title();
        if (!$this->is_valid()) {
            $result->error = get_string('error_invalid_configuration', 'totara_competency');
            $result->items = [
                (object)[
                    'description' => '',
                    'error' => get_string('error_no_raters', 'pathway_manual'),
                ],
            ];
        } else {
            $result->items = array_map(function (role $role) {
                return (object) [
                    'description' => $role::get_display_name()
                ];
            }, $this->get_roles());
        }

        return [$result];
    }

    /**
     * Validate the configuration
     * @return bool
     */
    protected function is_configuration_valid(): bool {
        return !empty($this->roles);
    }

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('achievement_path_group_label', 'pathway_manual');
    }



    /*******************************************************************************************************
     * Mustache template data exporting
     *******************************************************************************************************/

    /**
     * Return the name of the template to use for editing this pathway
     *
     * @return string Template name
     */
    public function get_edit_template(): string {
        return 'pathway_manual/pathway_manual_edit';
    }

    /**
     * Get a short description of the content of this pathway
     *
     * @return string Short description
     */
    public function get_short_description(): string {
        return implode(', ', array_map(function (role $role) {
            return $role::get_display_name();
        }, $this->roles));
    }

    /**
     * Export detail for editing the pathway
     * This contains detail information
     *
     * @return array
     */
    public function export_edit_detail(): array {
        $result = $this->export_edit_overview();
        $result['roles'] = $this->export_roles();

        return $result;
    }

    /**
     * Export roles
     *
     * @return array containing 'id', role and name for all linked roles
     */
    public function export_roles(): array {
        // Picker works on ids which we simulate through using their display order (which is always unique).
        return array_map(function (role $role) {
            return [
                'id' => $role::get_display_order(),
                'value' => $role::get_name(),
                'text' => $role::get_display_name(),
            ];
        }, $this->roles);
    }

    /**
     * Retrieve the current configuration from the database
     *
     * @param int|null $id Instance id
     * @return \stdClass | null
     */
    public static function dump_pathway_configuration(?int $id = null) {
        global $DB;

        if (!is_null($id) && $result = $DB->get_record('pathway_manual', ['id' => $id])) {
            $result->roles = $DB->get_records('pathway_manual_role', ['path_manual_id' => $id]);
            return $result;
        }

        return null;
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

        $roles = role_entity::repository()
            ->where('path_manual_id', $this->get_path_instance_id())
            ->get()
            ->pluck('role');

        (new self())
            ->set_competency($competency)
            ->set_sortorder($this->get_sortorder())
            ->set_path_instance_id(null)
            ->set_status($this->get_status())
            ->set_roles($roles)
            ->save();

        return $competency;
    }
}
