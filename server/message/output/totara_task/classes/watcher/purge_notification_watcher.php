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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package message_totara_task
 */
namespace message_totara_task\watcher;

use core_message\hook\purge_check_notification_hook;
use message_output_totara_task;
use totara_message\entity\message_metadata;

class purge_notification_watcher {
    /**
     * purge_notification_watcher constructor.
     * Preventing this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * @param purge_check_notification_hook $hook
     * @return void
     */
    public static function check_notification_for_purge(purge_check_notification_hook $hook): void {
        global $CFG;

        if ($hook->is_skip_purge()) {
            // Skip the check, if any other proccess had already told the hook to skip.
            return;
        }

        if (!class_exists('message_output_totara_task')) {
            require_once("{$CFG->dirroot}/message/output/totara_task/message_output_totara_task.php");
        }

        $notification = $hook->get_notification();
        $repository = message_metadata::repository();

        $metadata = $repository->find_message_metadata_from_notification_id(
            $notification->id,
            message_output_totara_task::get_processor_id()
        );

        if (null !== $metadata && empty($metadata->timeread)) {
            // Metadata record is existing, but it had not yet been finished the processing.
            // Hence we are skipping the process.
            $hook->mark_skip_purge();
        }
    }
}