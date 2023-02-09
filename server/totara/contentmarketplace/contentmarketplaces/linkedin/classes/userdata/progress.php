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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\userdata;

use contentmarketplace_linkedin\entity\user_progress as user_progress_entity;
use contentmarketplace_linkedin\model\user_progress as user_progress_model;
use context;
use core\orm\entity\repository;
use core_text;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

class progress extends item {

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     */
    public static function get_main_component(): string {
        return 'totara_contentmarketplace';
    }

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param context $context Not used, as progress is independent of courses
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context): int {
        return static::query($user->id)->count();
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable(): bool {
        return true;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param context $context Not used, as progress is independent of courses
     * @return export result object
     */
    protected static function export(target_user $user, context $context) {
        $data = static::query($user->id)
            ->with('learning_object')
            ->order_by('id')
            ->get()
            ->map_to(user_progress_model::class)
            ->map(static function (user_progress_model $completion) {
                $entry = [
                    'id' => (int) $completion->id,
                    'learning_object_urn' => $completion->learning_object_urn,
                    'progress' => (int) $completion->progress,
                    'completed' => (bool) $completion->completed,
                    'time_created' => (int) $completion->time_created,
                    'time_updated' => (int) $completion->time_updated,
                    'time_completed' => (int) $completion->time_completed,
                ];
                if ($completion->learning_object !== null) {
                    $entry['learning_object_title'] = core_text::entities_to_utf8(
                        format_string($completion->learning_object->title)
                    );
                }
                return $entry;
            })
            ->all();

        $export = new export();
        $export->data = [static::get_name() => $data];
        return $export;
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge user data for this item.
     *
     * @param target_user $user
     * @param context $context Not used, as progress is independent of courses
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context) {
        static::query($user->id)->delete();

        return static::RESULT_STATUS_SUCCESS;
    }

    /**
     * Repository query for user's progress data.
     *
     * @param int $user_id
     * @return repository
     */
    protected static function query(int $user_id): repository {
        return user_progress_entity::repository()
            ->where('user_id', $user_id);
    }

}
