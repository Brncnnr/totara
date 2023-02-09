<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\local;

use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\entity\learning_object;
use contentmarketplace_linkedin\task\adhoc_syncing_task;
use core\orm\query\builder;

/**
 * A helper for syncing learning objects.
 */
class sync_helper {
    /**
     * Preventing this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * Queue an adhoc task when
     * @return void
     */
    public static function settings_update_callback(): void {
        $client_secret = config::client_secret();
        $client_id = config::client_id();

        if (empty($client_id) || empty($client_secret)) {
            // Just skip queuing the task when either client's id and secret
            // are not provided.
            return;
        }

        // Before queuing the adhoc task, we would need to make sure that we do not queue
        // extra redundant task as if sync already happened.
        // To check for the sync already happened, we can check the learning objects table
        // to see if it has any records yet.
        $db = builder::get_db();
        $existing = $db->record_exists(learning_object::TABLE, []);

        if ($existing) {
            // Skip the queue as there are already records.
            return;
        }

        // Queue the adhoc task.
        adhoc_syncing_task::enqueue();
    }
}