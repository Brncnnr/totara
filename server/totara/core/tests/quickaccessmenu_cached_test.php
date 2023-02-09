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

use \totara_core\quickaccessmenu\factory;
use \totara_core\quickaccessmenu\menu\cached;
use \totara_core\quickaccessmenu\item;
use \totara_core\quickaccessmenu\group;

/**
 * @group totara_core
 */
class totara_core_quickaccessmenu_cached_testcase extends \core_phpunit\testcase {

    public function test_get() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $factory = factory::instance($user->id);
        $instance = cached::get($factory);

        self::assertInstanceOf(cached::class, $instance);
        self::assertEquals($user->id, $instance->get_userid());
    }

    public function test_set() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $factory = factory::instance($user->id);

        $method = new ReflectionMethod($factory, 'get_cached_menu');
        $method->setAccessible(true);

        // There are no items in the cached menu.
        self::assertCount(0, cached::get($factory)->get_items());
        // Confirm, it doesn't know about items either.
        self::assertNull($method->invoke($factory)->has_any_items());
        // Use the factory to check for items, populating the caches.
        self::assertFalse($factory->has_possible_items());
        // Confirm that the cache is primed, and that we know there are no items in menu.
        self::assertFalse($method->invoke($factory)->has_any_items());

        // Add an item to their menu, directly into the cache.
        $item = item::from_preference('test', group::get(group::PLATFORM), 'Test', null, true, new moodle_url('#'));
        $menu = cached::get($factory);
        $menu->add_item($item);

        // It's been added to the menu, but the menu hasn't yet been persisted.
        self::assertFalse($method->invoke($factory)->has_any_items());
        // Persist the menu.
        cached::set($factory, $menu);

        // We now have items in the menu, and the cache is correctly primed.
        self::assertTrue($method->invoke($factory)->has_any_items());
        self::assertCount(1, cached::get($factory)->get_items());
        // Just confirm that the factory has the same answer.
        self::assertTrue($factory->has_possible_items());
    }

}