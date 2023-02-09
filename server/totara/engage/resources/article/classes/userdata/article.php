<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package engage_article
 */
namespace engage_article\userdata;

use engage_article\local\helper;
use engage_article\local\loader;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;
use engage_article\totara_engage\resource\article as article_resource;

/**
 * Handles purging, counting and exporting the article resource types created by the user.
 */
final class article extends item {

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['user_data_item_article', 'engage_article'];
    }

    /**
     * Can user data of this item data be purged from system at this time?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus): bool {
        return true;
    }

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        $paginator = loader::load_all_article_of_user((int)$user->id, 0);
        $articles = $paginator->get_items()->all();

        foreach ($articles as $article) {
            helper::purge_article($article);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from system?
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
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        $paginator = loader::load_all_article_of_user((int)$user->id, 0);
        $resources = $paginator->get_items()->all();

        $export = new export();
        $export->data = [];

        /** @var article_resource $resource */
        foreach ($resources as $resource) {
            $export->data[] = [
                'name' => $resource->get_name(),
                'content' => content_to_text($resource->get_content(), $resource->get_format()),
                'timecreated' => $resource->get_timecreated(),
                'timemodified' => $resource->get_timemodified()
            ];
        }

        return $export;
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
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context): int {
        $paginator = loader::load_all_article_of_user((int)$user->id);
        return $paginator->get_total();
    }
}