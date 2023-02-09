<?php
/**
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package engage_survey
 */
namespace engage_survey\observer;

use engage_survey\event\vote_created;
use totara_engage\resource\resource_completion;

/**
 * Observer for survey component
 */
final class survey_observer {
    /**
     * reaction_observer constructor.
     */
    private function __construct() {
        // Preventing this class from construction
    }

    /**
     * @param vote_created $event
     * @return void
     */
    public static function on_vote_created(vote_created $event): void {
        global $DB;

        $actor_id = $event->get_user_id();
        $others = $event->other;
        $owner_id = $others['owner_id'];
        $resource_id = $event->get_item_id();

        $instance = resource_completion::instance($resource_id, $owner_id);
        $transaction = $DB->start_delegated_transaction();
        if ($instance->can_create($actor_id)) {
            $instance->create();
        }
        $transaction->allow_commit();

        // Clear instance.
        unset($instance);
    }
}