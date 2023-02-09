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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\placeholder\abstraction;

use totara_notification\watcher\phpunit_reset_watcher;

/**
 * Trait placeholder_instance_cache
 *
 * Placeholder objects are very likely to be fetched many times for the same item during notification queue processing.
 * This provides a cache for the placeholder instances to reduce DB load.
 *
 * Note: Remember to call clear_instance_cache() in PHPUnit tests.
 */
trait placeholder_instance_cache {

    /**
     * Limit the cache size so memory use doesn't get out of hand. We don't need a big size since the repeated hits
     * for the same item are usually sequential.
     *
     * @var int
     */
    private static $cache_max_size = 20;

    /**
     * @var array
     */
    private static $placeholder_instance_cache = [];

    /**
     * @param string $key
     * @return placeholder|null
     */
    protected static function get_cached_instance(string $key): ?placeholder {
        return self::$placeholder_instance_cache[$key] ?? null;
    }

    /**
     * @param string $key
     * @param placeholder $instance
     */
    protected static function add_instance_to_cache(string $key, placeholder $instance): void {
        if (PHPUNIT_TEST) {
            phpunit_reset_watcher::register_cached_placeholder_class(self::class);
        }

        if (count(self::$placeholder_instance_cache) >= self::$cache_max_size) {
            // Drop the oldest key.
            reset(self::$placeholder_instance_cache);
            $first_key = key(self::$placeholder_instance_cache);
            unset(self::$placeholder_instance_cache[$first_key]);
        }
        self::$placeholder_instance_cache[$key] = $instance;
    }

    public static function clear_instance_cache(): void {
        self::$placeholder_instance_cache = [];
    }
}
