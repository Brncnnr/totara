<?php
/**
 * This file is part of Totara Core
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\quickaccessmenu\menu;

use totara_core\quickaccessmenu\factory;
use totara_core\quickaccessmenu\item;
use totara_core\quickaccessmenu\menu;
use cache;
use cache_loader;

/**
 * Cached menu
 *
 * Caches the users menu state, once resolved.
 * All items visible to the user should be cached.
 *
 * This menu is taken from the cache. It always has base states.
 * 1. There is no data in the cache, in which case this menu is useless and has no affect.
 * 2. There is data, in which case this menu should be used instead of all other menus.
 */
final class cached extends base {

    /** @var string Key for the menu items within the cache. */
    private const CACHE_KEY_MENUITEMS = 'menuitems';

    /**
     * True if this menu were loaded from the cache.
     * False if the menu was not stored in the cache.
     * @var bool
     */
    private $cache_had_data = false;

    /**
     * Get an instance of the cache to store the users menu in.
     *
     * @param int $userid The id of the user we're getting the cache for.
     * @param string|null $lang The language we'll be caching. If null the current language is used.
     * @return cache_loader
     */
    private static function get_cache(int $userid, string $lang = null): cache_loader {
        // Don't worry about storing this in a property or anything. The cache API has its own static caching to speed
        // up repeated calls to get a cache.
        if ($lang === null) {
            $lang = current_language();
        }
        return cache::make('totara_core', 'quickaccessmenu_complete', ['userid' => $userid, 'lang' => $lang]);
    }

    /**
     * Gets quick access menu items from the cache, if they are there.
     *
     * @param factory $factory
     * @return cached
     */
    public static function get(factory $factory): cached {
        $data = self::get_cache($factory->get_userid())->get(self::CACHE_KEY_MENUITEMS);
        if ($data === false) {
            return new cached($factory, []);
        }

        $items = [];
        foreach ($data as $record) {
            $items[] = item::wake_from_cache($record);
        }
        $menu = new cached($factory, $items);
        $menu->cache_had_data = true;
        return $menu;
    }

    /**
     * Populates the cache for the user with the items from their menu.
     *
     * @param factory $factory
     * @param menu $menu
     */
    public static function set(factory $factory, menu $menu) {
        $items = [];
        foreach ($menu->get_items() as $item) {
            $items[] = $item->prepare_to_cache();
        }
        self::get_cache($factory->get_userid())->set(self::CACHE_KEY_MENUITEMS, $items);
    }

    /**
     * Deletes the cache entry for the given user.
     *
     * @param int $userid
     */
    public static function delete(int $userid) {
        self::get_cache($userid)->delete(self::CACHE_KEY_MENUITEMS);
    }

    /**
     * Returns true if the menu was loaded from the cache, false otherwise.
     *
     * @return bool
     */
    public function was_loaded_from_cache(): bool {
        return $this->cache_had_data;
    }

    /**
     * Returns null if the cache has not been set, otherwise true if there are items and false if we know that there are not.
     *
     * @return bool|null
     */
    public function has_any_items(): ?bool {
        if (!$this->cache_had_data) {
            return null;
        }
        return !empty($this->get_all_items());
    }
}
