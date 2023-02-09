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
 * @author  Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\factory\notifiable_event_resolver_factory;

class totara_notification_notifiable_event_resolver_factory_testcase extends testcase {

    public function test_load_map() {
        $cache = notifiable_event_resolver_factory::get_cache_loader();
        self::assertFalse($cache->has(notifiable_event_resolver_factory::MAP_KEY));

        notifiable_event_resolver_factory::load_map();

        self::assertTrue($cache->has(notifiable_event_resolver_factory::MAP_KEY));

        $classes = $cache->get(notifiable_event_resolver_factory::MAP_KEY);

        self::assertIsArray($classes);
        self::assertNotEmpty($classes);

        // Check the components are all valid subsytems or plugins.
        $components = array_keys($classes);
        foreach ($components as $component) {
            self::assertNotNull(core_component::get_component_directory($component));
        }

        // Just verify a couple we expect to exist.
        self::assertArrayHasKey('totara_certification', $classes);
        self::assertArrayHasKey('totara_program', $classes);

        // Check just a couple of expected program notifications to ensure we have the structure correct.
        self::assertContains('totara_program\totara_notification\resolver\assigned', $classes['totara_program']);
        self::assertContains('totara_program\totara_notification\resolver\unassigned', $classes['totara_program']);
    }

}