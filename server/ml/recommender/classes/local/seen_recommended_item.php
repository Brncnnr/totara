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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\local;

use core\orm\query\builder;
use engage_article\event\article_viewed;
use ml_recommender\entity\recommended_user_item;
use totara_core\advanced_feature;
use totara_playlist\event\playlist_viewed;

/**
 * Class seen_recommended_item
 *
 * Mark a recommended item as seen.
 *
 * @package ml_recommender\local
 */
final class seen_recommended_item {
    /**
     * seen_recommended_item constructor.
     */
    private function __construct() {
        // This class should only be used statically.
    }

    /**
     * Mark a viewed recommended article as having been seen by user.
     *
     * @param article_viewed $event
     * @return void
     */
    public static function seen_recommended_article(article_viewed $event): void {
        static::process_seen_event((int) $event->userid, (int) $event->other['resourceid'], $event->component);
    }

    /**
     * Mark a viewed recommended playlist as having been seen by user.
     *
     * @param playlist_viewed $event
     * @return void
     */
    public static function seen_recommended_playlist(playlist_viewed $event): void {
        static::process_seen_event((int) $event->userid, (int) $event->objectid, $event->component);
    }

    /**
     * If item has been recommended to user, then mark it as having been seen.
     *
     * @param $event Instance of core_ml\event
     * @return void
     */
    private static function process_seen_event(int $user_id, int $item_id, string $component): void {
        if (advanced_feature::is_disabled('ml_recommender')) {
            return;
        }

        $builder = builder::table(recommended_user_item::TABLE);
        $builder->where('user_id', $user_id);
        $builder->where('item_id', $item_id);
        $builder->where('component', $component);
        $builder->where('seen', 0);

        $attrs = new \stdClass();
        $attrs->seen = (int) 1;

        $builder->update($attrs);
    }
}