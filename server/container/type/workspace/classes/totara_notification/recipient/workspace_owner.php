<?php
/**
 * This file is part of Totara Learn
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 * @category totara_notification
 */

namespace container_workspace\totara_notification\recipient;

use container_workspace\entity\workspace;
use totara_notification\recipient\recipient;

/**
 * Primary owner of the workspace as a recipient.
 */
class workspace_owner implements recipient {
    public static function get_name(): string {
        return get_string('notification_workspace_owner_recipient', 'container_workspace');
    }

    /**
     * Get the primary owner for the workspace
     *
     * @param array $data
     * @return array
     */
    public static function get_user_ids(array $data): array {
        $workspace_id = $data['workspace_id'] ?? null;
        if (empty($workspace_id)) {
            return [];
        }

        /** @var workspace $workspace */
        $workspace = workspace::repository()
            ->where('course_id', $workspace_id)
            ->where('to_be_deleted', false)
            ->one();

        return $workspace ? [$workspace->user_id] : [];
    }
}
