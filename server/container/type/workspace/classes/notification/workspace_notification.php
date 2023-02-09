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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\notification;

use container_workspace\entity\workspace_off_notification;
use container_workspace\interactor\workspace\interactor as workspace_interactor;

/**
 * A wrapper for all the database read/write related to table {@see workspace_off_notification::TABLE}
 */
final class workspace_notification {
    /**
     * workspace_notification constructor.
     * Prevent this class from being constructed.
     */
    private function __construct() {
    }

    /**
     * @param int $workspace_id
     * @param int|null $actor_id
     *
     * @return void
     */
    public static function off(int $workspace_id, ?int $actor_id = null): void {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $interactor = workspace_interactor::from_workspace_id($workspace_id, $actor_id);
        if (!$interactor->is_joined()) {
            throw new \coding_exception("Only member of a workspace can turn off the notification");
        }

        $repository = workspace_off_notification::repository();
        $entity = $repository->find_for_user_in_workspace($actor_id, $workspace_id);

        if (null !== $entity) {
            return;
        }

        $entity = new workspace_off_notification();
        $entity->user_id = $actor_id;
        $entity->course_id = $workspace_id;

        $entity->save();
    }

    /**
     * By turning on the notification, this process will try to delete the current record that created by the user
     * related to the given $workspace_id.
     *
     * @param int $workspace_id
     * @param int|null $actor_id
     *
     * @return void
     */
    public static function on(int $workspace_id, ?int $actor_id = null): void {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $interactor = workspace_interactor::from_workspace_id($workspace_id, $actor_id);
        if (!$interactor->is_joined()) {
            throw new \coding_exception("Only member of a workspace can turn on the notification");
        }

        $repository = workspace_off_notification::repository();
        $entity = $repository->find_for_user_in_workspace($actor_id, $workspace_id);

        if (null === $entity) {
            // No entity was found, could be meaning that user had never turned this on.
            return;
        }

        $entity->delete();
    }

    /**
     * @param int $workspace_id
     * @param int|null $actor_id
     * @return bool
     */
    public static function is_off(int $workspace_id, ?int $actor_id = null): bool {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER;
        }

        $repository = workspace_off_notification::repository();
        return $repository->exists_for_user_in_workspace($actor_id, $workspace_id);
    }
}