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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria;

use core\orm\entity\repository;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\hook\criteria_achievement_changed;
use totara_criteria\hook\criteria_validity_changed;

class competency_item_helper {

    /**
     * @param int|array $user_ids
     * @param int $competency_id
     * @param string|null $plugin_type
     */
    public static function achievement_updated($user_ids, int $competency_id, ?string $plugin_type = null) {
        // Find all criteria with this competency as item
        // As the criterion has no knowledge whether this user's satisfaction of the criteria is to be tracked,
        // it simply generates an criteria_achievement_changed event with the relevant criterion ids and this user's id.
        // Modules that use these criteria are responsible for initiating the relevant processes to create/update
        // the item_record(s) for this user

        if (!is_array($user_ids)) {
            $user_ids = [$user_ids];
        }

        $criteria_ids = criterion_entity::repository()
            ->from_item_ids('competency', $competency_id)
            ->when(!is_null($plugin_type), function (repository $repository) use ($plugin_type) {
                $repository->where('plugin_type', $plugin_type);
            })
            ->get()
            ->pluck('id');

        if (!empty($criteria_ids)) {
            $user_criteria_ids = array_fill_keys($user_ids, $criteria_ids);
            $hook = new criteria_achievement_changed($user_criteria_ids);
            $hook->execute();
        }
    }

    /**
     * @param int $competency_id
     * @param string|null $plugin_type
     */
    public static function configuration_changed(int $competency_id, ?string $plugin_type = null) {
        // Find criteria with this competency as item
        // Re-validate them and trigger criteria_validity_changed for applicable criteria

        $criteria = criterion_entity::repository()
            ->from_item_ids('competency', $competency_id)
            ->when(!is_null($plugin_type), function (repository $repository) use ($plugin_type) {
                $repository->where('plugin_type', $plugin_type);
            })
            ->get();

        $affected_criteria = [];
        foreach ($criteria as $criterion) {
            $instance = criterion_factory::fetch_from_entity($criterion);
            $instance->validate();
            if ($instance->is_valid() != $criterion->valid) {
                $instance->save_valid();
                $affected_criteria[] = $criterion->id;
            }
        }

        if (!empty($affected_criteria)) {
            $hook = new criteria_validity_changed($affected_criteria);
            $hook->execute();
        }
    }

}
