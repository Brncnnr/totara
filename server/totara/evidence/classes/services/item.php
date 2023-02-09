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

use context_user;
use core\notification;
use external_api;
use external_description;
use external_function_parameters;
use external_value;
use totara_core\advanced_feature;
use totara_evidence\models;
use totara_evidence\models\helpers\evidence_item_capability_helper;

class item extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function info_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(
                PARAM_INT,
                'id of the item',
                VALUE_REQUIRED,
                0,
                false
            ),
        ]);
    }

    /**
     * @param int $id Evidence item entity ID
     * @return array Formatted metadata about the specified item
     */
    public static function info(int $id): array {
        global $PAGE;

        advanced_feature::require('evidence');

        $item = models\evidence_item::load_by_id($id);
        $PAGE->set_context(context_user::instance($item->user_id));
        evidence_item_capability_helper::for_item($item)->can_view_item(true);

        return $item->get_data();
    }

    /**
     * @return external_description|null
     */
    public static function info_returns(): ?external_description {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function delete_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(
                PARAM_INT,
                'id of the item',
                VALUE_REQUIRED,
                0,
                false
            ),
        ]);
    }

    /**
     * @param int $id Evidence item entity ID
     * @return bool Successfully deleted?
     */
    public static function delete(int $id): bool {
        global $PAGE;

        advanced_feature::require('evidence');

        $item = models\evidence_item::load_by_id($id);

        $PAGE->set_context(context_user::instance($item->user_id));
        $name = $item->get_display_name();

        $item->delete();

        notification::add(
            get_string('notification_item_deleted', 'totara_evidence', $name),
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

}
