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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

class editor_weka_deprecate_metadata_file_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_check_the_metadata_files(): void {
        $plugin_types = core_component::get_plugin_types();
        $plugin_types = array_keys($plugin_types);

        foreach ($plugin_types as $plugin_type) {
            $plugins = core_component::get_plugin_list_with_file($plugin_type, 'db/editor_weka.php');
            self::assertEmpty($plugins, "There are editor_weka.php files for plugins in '{$plugin_type}'");
        }
    }
}