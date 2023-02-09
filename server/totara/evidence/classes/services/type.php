<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\services;

use context_system;
use core\notification;
use core_collator;
use external_api;
use external_description;
use external_function_parameters;
use external_value;
use html_writer;
use totara_core\advanced_feature;
use totara_evidence\customfield_area;
use totara_evidence\entity\evidence_type;
use totara_evidence\models;
use totara_evidence\models\helpers\evidence_item_capability_helper;

class type extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function data_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(
                PARAM_INT,
                'id of the type',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * @param int $id Evidence type entity ID
     * @return array All data about the specified type
     */
    public static function data(int $id): array {
        advanced_feature::require('evidence');

        global $PAGE;
        $PAGE->set_context(context_system::instance());

        $type = models\evidence_type::load_by_id($id);
        $type::can_manage(true);

        return array_merge($type->get_data(), [
            'edit_url' => customfield_area\evidence::get_url($type->get_id())
        ]);
    }

    /**
     * @return external_description|null
     */
    public static function data_returns(): ?external_description {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function details_parameters(): external_function_parameters {
        return new external_function_parameters([
            'type_id' => new external_value(
                PARAM_INT,
                'id of the type',
                VALUE_REQUIRED
            ),
            'user_id' => new external_value(
                PARAM_INT,
                'id of the user you are viewing',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * @param int $type_id Evidence type entity ID
     * @param int $user_id ID of user you are creating evidence for
     * @return array Limited details about the type
     */
    public static function details(int $type_id, int $user_id): array {
        global $PAGE;

        advanced_feature::require('evidence');

        $PAGE->set_context(context_system::instance());

        $type = models\evidence_type::load_by_id($type_id);

        // Details for a type are needed for when viewing or creating an evidence item, so doesn't need to be admin
        evidence_item_capability_helper::for_user($user_id)->can_view_list(true);

        $description = $type->get_display_description();
        if ($description === '') {
            $description = html_writer::tag('i',
                get_string('no_description_available', 'totara_evidence')
            );
        }

        return [
            'name' => $type->get_display_name(),
            'description' => $description,
        ];
    }

    /**
     * @return external_description|null
     */
    public static function details_returns(): ?external_description {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function delete_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(
                PARAM_INT,
                'id of the type',
                VALUE_REQUIRED,
                0,
                false
            ),
        ]);
    }

    /**
     * @param int $id Evidence type entity ID
     * @return bool Successfully deleted?
     */
    public static function delete(int $id): bool {
        global $PAGE;

        advanced_feature::require('evidence');

        $type = models\evidence_type::load_by_id($id);

        $PAGE->set_context(context_system::instance());
        $name = $type->get_display_name();

        $type->delete();

        notification::add(
            get_string('notification_type_deleted', 'totara_evidence', $name),
            notification::SUCCESS
        );

        return true;
    }

    /**
     * @return external_description|null
     */
    public static function delete_returns(): ?external_description {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function search_parameters(): external_function_parameters {
        return new external_function_parameters([
            'string' => new external_value(
                PARAM_TEXT,
                'search query string',
                VALUE_REQUIRED,
                '',
                true
            ),
            'location' => new external_value(
                PARAM_INT,
                'search query location',
                VALUE_DEFAULT,
                models\evidence_type::LOCATION_EVIDENCE_BANK,
                true
            ),
        ]);
    }

    /**
     * @param string $string Search query string
     * @return string[] List of evidence types with
     */
    public static function search(string $string, ?int $location = models\evidence_type::LOCATION_EVIDENCE_BANK): array {
        global $PAGE;

        advanced_feature::require('evidence');

        // Need to set context before calling format_string()
        $PAGE->set_context(context_system::instance());

        // The front-end component is hardcoded to use label attribute, hence have to remap here.
        $types = evidence_type::repository()
            ->filter_by_active()
            ->where('name', 'ilike', $string)
            ->get()
            ->transform(static function (evidence_type $type) {
                return [
                    'value' => $type->id,
                    'label' => $type->model->display_name,
                ];
            })
            ->to_array(); // Can't use fetch() because javascript doesn't support associative arrays

        core_collator::asort_array_of_arrays_by_key($types, 'label');

        // Restore scrambled keys, just in case.
        return array_values($types);
    }

    /**
     * @return external_description|null
     */
    public static function search_returns(): ?external_description {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function set_visibility_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(
                PARAM_INT,
                'id of the type',
                VALUE_REQUIRED,
                0,
                false
            ),
            'visible' => new external_value(
                PARAM_BOOL,
                'set to visible?',
                VALUE_REQUIRED,
                false,
                false
            ),
        ]);
    }

    public static function set_visibility(int $id, bool $visible): void {
        advanced_feature::require('evidence');

        $type = models\evidence_type::load_by_id($id);
        if ($visible) {
            $type->update_status(models\evidence_type::STATUS_ACTIVE);
        } else {
            $type->update_status(models\evidence_type::STATUS_HIDDEN);
        }
    }

    /**
     * @return external_description|null
     */
    public static function set_visibility_returns(): ?external_description {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function get_import_fields_parameters(): external_function_parameters {
        return new external_function_parameters([
            'type_id' => new external_value(
                PARAM_INT,
                'id of the type',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Return the list of customfields that may be added during import.
     * The first time a type is used to create ROL evidence, we add the default system type fields to ensure all fields required
     * by the import process are available. These fields are removed from the result list.
     *
     * @param int $type_id Evidence type entity ID
     * @return array List of custom field names that may be used during imported. Names are prepended with 'customfield_'
     */
    public static function get_import_fields(int $type_id): array {
        advanced_feature::require('evidence');
        return models\evidence_type::load_by_id($type_id)->get_import_fields();
    }

    /**
     * @return external_description|null
     */
    public static function get_import_fields_returns(): ?external_description {
        return new \external_multiple_structure(
            new \external_value(PARAM_TEXT, 'Import field name')
        );
    }

}
