<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Scott Davies <scott.davies@totara.com>
 * @package totara_oauth2
 */

namespace totara_oauth2\task;

use core\task\scheduled_task;
use totara_oauth2\entity\access_token;

/**
 * A scheduled task to clean up expired oauth2_tokens from the database.
 */
class expired_oauth2_tokens_task extends scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_expired_oauth2_tokens', 'totara_oauth2');
    }

    /**
     * Do the job.
     */
    public function execute() {
        // Hard-delete the expired task_expired_oauth2_tokens records.
        access_token::repository()->where("expires", "<", time())->delete();
    }
}
