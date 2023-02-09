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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
use core\plugininfo\base;

class core_plugin_name_lang_string_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_ensure_plugin_lang_string_exist(): void {
        // Note: please update this excluded types when all of these plugins of
        // these types are converted correctly.
        $excluded_types = ['filter', 'dataformat', 'virtualmeeting'];
        $missing_strings = [];

        $map_plugin_infos = core_plugin_manager::instance()->get_plugins();
        $string_manager = get_string_manager();

        foreach ($map_plugin_infos as $type => $infos) {
            if (in_array($type, $excluded_types)) {
                continue;
            }

            /** @var base $info */
            foreach ($infos as $info) {
                if (!$string_manager->string_exists('pluginname', $info->component)) {
                    $missing_strings[] = $info->component;
                }

            }

        }

        $missing_strings_text = print_r($missing_strings, true);
        self::assertEmpty(
            $missing_strings,
            "The following components are missing 'pluginname' strings: {$missing_strings_text}"
        );
    }
}