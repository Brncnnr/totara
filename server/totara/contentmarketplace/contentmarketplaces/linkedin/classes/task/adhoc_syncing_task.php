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
namespace contentmarketplace_linkedin\task;

use contentmarketplace_linkedin\sync_action\sync_classifications;
use contentmarketplace_linkedin\sync_action\sync_learning_asset;
use core\task\adhoc_task;
use core\task\manager;
use totara_contentmarketplace\sync;
use totara_core\http\client;
use totara_core\http\clients\curl_client;
use progress_trace;
use null_progress_trace;

/**
 * An adhoc task to perform syncing for linkedin specific only.
 * This task will be queued when linkedin learning is enabled, and client secret/id is enabled for the first.
 */
class adhoc_syncing_task extends adhoc_task {
    /**
     * Default to curl client.
     * @var client
     */
    private $client;

    /**
     * Default to {@see null_progress_trace}
     * @var progress_trace
     */
    private $progress_trace;

    /**
     * @param client|null $client
     */
    public function __construct(?client $client = null) {
        $this->client = $client ?? new curl_client();
        $this->progress_trace = new null_progress_trace();
    }

    /**
     * @param client $client
     * @erturn void
     */
    public function set_client(client $client): void {
        $this->client = $client;
    }

    /**
     * @param progress_trace $progress_trace
     * @return void
     */
    public function set_progress_trace(progress_trace $progress_trace): void {
        $this->progress_trace = $progress_trace;
    }

    /**
     * @return void
     */
    public static function enqueue(): void {
        $task = new self();
        $task->set_component("contentmarketplace_linkedin");
        manager::queue_adhoc_task($task);
    }

    /**
     * @return void
     */
    public function execute(): void {
        $sync = new sync($this->client, $this->progress_trace);
        $sync->set_sync_action_classes([
            sync_classifications::class,
            sync_learning_asset::class
        ]);

        $sync->execute(true);
    }
}