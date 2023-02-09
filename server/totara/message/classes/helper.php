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
 * @package totara_message
 */
namespace totara_message;

use core\orm\query\builder;
use totara_message\entity\message_metadata;

class helper {
    /**
     * helper constructor.
     * Preventing this class from instantiation
     */
    private function __construct() {
    }

    /**
     * @param int      $notification_id
     * @param int|null $time_read
     * @param int|null $processor_id
     *
     * @return void
     */
    public static function mark_message_metadata_read(int $notification_id, ?int $time_read = null,
                                                      ?int $processor_id = null): void {
        $processor_ids = [$processor_id];
        if (empty($processor_id)) {
            $processor_ids = builder::table('message_processors')->select('id')->get()->pluck('id');
        }

        foreach ($processor_ids as $internal_process_id) {
            $repository = message_metadata::repository();
            $metadata = $repository->find_message_metadata_from_notification_id(
                $notification_id,
                $internal_process_id
            );

            if (null === $metadata) {
                continue;
            }

            $metadata->timeread = $time_read ?? time();
            $metadata->save();
        }
    }
}