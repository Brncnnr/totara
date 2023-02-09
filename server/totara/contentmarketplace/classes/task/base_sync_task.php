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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\task;

use core\task\scheduled_task;
use progress_trace;
use text_progress_trace;
use totara_core\http\client;
use totara_core\http\clients\curl_client;

abstract class base_sync_task extends scheduled_task {
    /**
     * @var client
     */
    protected $client;

    /**
     * @var progress_trace
     */
    protected $trace;

    /**
     * base_sync_task constructor.
     * @param progress_trace|null $trace
     * @param client|null $client
     */
    public function __construct(?progress_trace $trace = null, ?client $client = null) {
        // Default to curl client, but it can be mock for testing purpose.
        $this->client = $client ?? new curl_client();
        $this->trace = $trace ?? new text_progress_trace();
    }

    /**
     * @param progress_trace $trace
     * @return void
     */
    public function set_trace(progress_trace $trace): void {
        $this->trace = $trace;
    }

    /**
     * @param client $client
     * @return void
     */
    public function set_client(client $client): void {
        $this->client = $client;
    }
}