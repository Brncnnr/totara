<?php
/**
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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\task;

use totara_contentmarketplace\sync;

/**
 * Synchronise metadata from the external content marketplace services into the local system.
 */
class sync_task extends base_sync_task {
    /**
     * @inheritDoc
     */
    public function get_name() {
        return get_string('sync_learning_object_metadata_task', 'totara_contentmarketplace');
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        $sync = new sync($this->client, $this->trace);
        $sync->execute(false);
    }
}