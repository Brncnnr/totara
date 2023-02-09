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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\watcher;

use global_navigation;
use mod_approval\model\workflow\workflow;
use moodle_page;
use settings_navigation;
use totara_core\advanced_feature;
use totara_core\hook\navigation\base;
use totara_core\hook\navigation\global_navigation_for_ajax_intialise;
use totara_core\hook\navigation\global_navigation_initialise;
use totara_core\hook\navigation\settings_navigation_initialise;

/**
 * Performance hacks hooks
 */
final class performance {
    /**
     * Private constructor.
     */
    private function __construct() {
        throw new \coding_exception('This class cannot be instantiated.');
    }

    /**
     * @param base $hook
     * @param global_navigation|settings_navigation $nav
     */
    private static function override_navigation_for_workflow(base $hook, $nav): void {
        // Not our problem.
        if (advanced_feature::is_disabled('approval_workflows')) {
            return;
        }
        // Eww, reflection in production code :(
        $rp = new \ReflectionProperty($nav, 'page');
        $rp->setAccessible(true);
        /** @var moodle_page $page */
        $page = $rp->getValue($nav);
        if (workflow::is_workflow_container($page->course)) {
            $hook->set_override(true);
        }
    }

    /**
     * @param global_navigation_for_ajax_intialise $hook
     */
    public static function override_global_navigation_for_ajax(global_navigation_for_ajax_intialise $hook): void {
        self::override_navigation_for_workflow($hook, $hook->get_navigation());
    }

    /**
     * @param global_navigation_initialise $hook
     */
    public static function override_global_navigation(global_navigation_initialise $hook): void {
        self::override_navigation_for_workflow($hook, $hook->get_navigation());
    }

    /**
     * @param settings_navigation_initialise $hook
     */
    public static function override_settings_navigation(settings_navigation_initialise $hook): void {
        self::override_navigation_for_workflow($hook, $hook->get_navigation());
    }

    // what about settings_navigation_ajax?
}
