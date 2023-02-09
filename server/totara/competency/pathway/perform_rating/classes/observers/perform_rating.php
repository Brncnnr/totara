<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package pathway_perform_rating
 */

namespace pathway_perform_rating\observers;

use coding_exception;
use pathway_perform_rating\event\perform_rating_created;
use pathway_perform_rating\perform_rating as perform_rating_entity;
use totara_competency\aggregation_users_table;
use totara_competency\entity\pathway;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer
 */
class perform_rating {

    public static function created(perform_rating_created $event) {
        $competency_id = $event->other['competency_id'] ?? null;
        $user_id = $event->relateduserid ?? null;
        if (!$competency_id || !$user_id) {
            throw new coding_exception('Missing required competency or user id');
        }

        $has_pathway = pathway::repository()
            ->where('path_type', perform_rating_entity::pathway_type())
            ->where('competency_id', $competency_id)
            ->exists();

        // No need to queue for aggregation if there's no pathway
        if (!$has_pathway) {
            return;
        }

        // Make sure that when a user got rated we trigger the aggregation
        (new aggregation_users_table())->queue_for_aggregation(
            $event->relateduserid,
            $event->other['competency_id']
        );
    }

}
