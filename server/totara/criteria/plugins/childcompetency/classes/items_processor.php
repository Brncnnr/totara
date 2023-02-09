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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace criteria_childcompetency;

use totara_competency\achievement_configuration;
use totara_competency\entity\configuration_change;
use totara_criteria\entity\criteria_metadata as criteria_metadata_entity;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\hook\criteria_validity_changed;
use totara_hierarchy\entity\competency as competency_entity;

class items_processor {

    /**
     * Update the criterion items so that a criterion_item exist for each direct child competency of the applicable
     * competency
     * Not generating any events to be picked up by modules that use this type of criteria (e.g. criteria_group)
     * as the change is not user specific and therefore may result in a lot of work to be completed by the observers.
     *
     * @param int $competency_id Update items of this
     */
    public static function update_items(int $competency_id) {
        global $DB;

        $item_type = (new childcompetency())->get_items_type();

        // Get all childcompetency criteria linked to the competency.
        $criteria = criterion_entity::repository()
            ->join([criteria_metadata_entity::TABLE, 'cm'], 'id', 'criterion_id')
            ->where('cm.metakey', childcompetency::METADATA_COMPETENCY_KEY)
            ->where('cm.metavalue', $competency_id)
            ->with('items')
            ->where('plugin_type', 'childcompetency')
            ->get();

        if (!$criteria->count()) {
            return;
        }

        // We only need to determine whether there were any changes once
        // All childcompetency criteria on the same competency will point to the same item ids
        $child_competency_ids = competency_entity::repository()
            ->where('parentid', $competency_id)
            ->get()
            ->pluck('id');

        $current_item_ids = $criteria
            ->first()
            ->items
            ->pluck('item_id');

        $to_add = array_diff($child_competency_ids, $current_item_ids);
        $to_delete = array_diff($current_item_ids, $child_competency_ids);

        if (empty($to_add) && empty($to_delete)) {
            return;
        }

        // We use the same action time for all updates to ensure that we only log changes once
        // if the competency has more than one childcompetency criteria
        $now = time();

        // We get a configuration dump before we start making changes for the specific competency
        // so that we can dump history if anything did change
        $competency_dump = achievement_configuration::get_current_configuration_dump($competency_id);

        // Saving through criteria_childcompetency instance instead of entity to ensure validation checks are done
        $transaction = $DB->start_delegated_transaction();

        $affected_criteria = [];
        foreach ($criteria as $criterion) {
            $childcompetency = childcompetency::fetch_from_entity($criterion);
            $childcompetency->set_item_ids($child_competency_ids);
            $childcompetency->save();
            if ($childcompetency->is_valid() != $criterion->valid) {
                $affected_criteria[] = $criterion->id;
            }
        }

        // Dump the configuration history and log the configuration change
        // TODO: Is it correct to save a configuration change here? Suggested - trigger a 'criteria_configuration_changed hook
        //       to be picked up by criteria_group (TL-23545)
        //       Also - to sort out the history - save history everytime something changes -> thus the last saved history
        //       entry is the current configuration (TL-23546)
        $config = new achievement_configuration(new competency_entity($competency_id));
        $config->save_configuration_history($now, $competency_dump);
        configuration_change::add_competency_entry(
            $competency_id,
            configuration_change::CHANGED_CRITERIA,
            $now,
            true
        );

        if (!empty($affected_criteria)) {
            $hook = new criteria_validity_changed($affected_criteria);
            $hook->execute();
        }

        $transaction->allow_commit();
    }
}
