<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\watcher;

use core\task\manager as task_manager;
use totara_catalog\local\catalog_storage;
use totara_catalog\task\provider_active_task;
use totara_catalog\task\refresh_catalog_adhoc;

defined('MOODLE_INTERNAL') || die();

final class admin_settings_watcher {
    /**
     * This watching is tracking all admin settings that affect catalogue,
     * to make this simple all standard plugins are covered here.
     *
     * @param \core\hook\admin_setting_changed $hook
     * @return void
     */
    public static function changed(\core\hook\admin_setting_changed $hook): void {
        global $DB;

        if (!get_config('totara_catalog', 'version')) {
            // Not installed yet.
            return;
        }

        if ($hook->name === 'usetags') {
            $adhoctask = new refresh_catalog_adhoc();
            $adhoctask->set_component('totara_catalog');
            task_manager::queue_adhoc_task($adhoctask);
            return;
        }

        if ($hook->name === 'enableprograms') {
            if ($hook->newvalue != \totara_core\advanced_feature::DISABLED) {
                $adhoctask = new provider_active_task();
                $adhoctask->set_custom_data(array('objecttype' => 'program'));
                $adhoctask->set_component('totara_catalog');
                task_manager::queue_adhoc_task($adhoctask);
            } else {
                catalog_storage::delete_provider_data('program');
            }
            return;
        }

        if ($hook->name === 'enablecertifications') {
            if ($hook->newvalue != \totara_core\advanced_feature::DISABLED) {
                $adhoctask = new provider_active_task();
                $adhoctask->set_custom_data(array('objecttype' => 'certification'));
                $adhoctask->set_component('totara_catalog');
                task_manager::queue_adhoc_task($adhoctask);
            } else {
                catalog_storage::delete_provider_data('certification');
            }
            return;
        }

        if ($hook->name === 'catalogtype') {
            if ($hook->newvalue === 'totara') {
                $adhoctask = new refresh_catalog_adhoc();
                $adhoctask->set_component('totara_catalog');
                task_manager::queue_adhoc_task($adhoctask);
            } else {
                $DB->delete_records('catalog');
            }
            return;
        }
    }
}