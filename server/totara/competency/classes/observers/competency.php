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

namespace totara_competency\observers;

use core\event\base;
use hierarchy_competency\event\competency_created;
use hierarchy_competency\event\competency_deleted;
use hierarchy_competency\event\competency_updated;
use stdClass;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\configuration_change;
use totara_competency\entity\configuration_history;
use totara_competency\entity\scale_aggregation;
use totara_competency\legacy_aggregation;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency as competency_entity;

class competency {

    public static function updated(competency_updated $event) {
        // Get the changed item out of the event
        $snapshot = $event->get_record_snapshot('comp', $event->get_data()['objectid']);

        // Only if the aggregation method changed apply aggregation method on the pathways
        if (self::aggregation_method_changed($event, $snapshot)) {
            $competency = new competency_entity($snapshot);

            $aggregation = new legacy_aggregation($competency);
            $aggregation->apply();
        }
    }

    /**
     * Check if the aggregation method changed, this only applies if perform is not enabled
     *
     * @param base $event
     * @param stdClass $snapshot already updated competency
     * @return bool
     */
    private static function aggregation_method_changed(base $event, stdClass $snapshot): bool {
        // We want this happening only if perform is not activated
        if (advanced_feature::is_enabled('competency_assignment')) {
            return false;
        }

        $old_instance = $event->get_data()['other']['old_instance'] ?? null;
        if (!$old_instance) {
            throw new \coding_exception('Missing old instance in competency_updated event');
        }

        return isset($snapshot->aggregationmethod)
            && ($old_instance['aggregationmethod'] != $snapshot->aggregationmethod);
    }

    /**
     * React on a new competency being created
     *
     * @param competency_created $event
     */
    public static function created(competency_created $event) {
        $competency_id = $event->get_data()['objectid'];

        // Create default criteria based on the current aggregation method
        if (!advanced_feature::is_enabled('competency_assignment')) {
            $aggregation = new legacy_aggregation(new competency_entity($competency_id));
            $aggregation->create_default_pathways();
        }
    }

    /**
     * React on a competency being deleted
     *
     * @param competency_deleted $event
     */
    public static function deleted(competency_deleted $event) {
        global $DB;

        $competency_id = $event->get_data()['objectid'];

        $DB->transaction(function () use ($competency_id) {
            configuration_change::repository()
                ->where('competency_id', $competency_id)
                ->delete();

            configuration_history::repository()
                ->where('competency_id', $competency_id)
                ->delete();

            scale_aggregation::repository()
                ->where('competency_id', $competency_id)
                ->delete();

            competency_achievement::repository()
                ->where('competency_id', $competency_id)
                ->delete();
        });
    }

}
