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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\userdata\helpers;

use core\orm\query\builder;
use mod_perform\models\activity\respondable_element_plugin;

/**
 * Common functionality for handling user data files.
 * @package mod_perform\userdata
 */
class userdata_file_helper {

    /**
     * Restrict the specified builder to the file component and areas for the element plugins.
     *
     * @param builder $builder
     * @param string $file_table_alias
     */
    public static function apply_respondable_element_file_restrictions(builder $builder, string $file_table_alias = 'files'): void {
        foreach (respondable_element_plugin::get_element_plugins_with_files() as $plugin) {
            $builder->or_where(function (builder $builder) use ($plugin, $file_table_alias) {
                $builder
                    ->where($file_table_alias . '.component', $plugin::get_response_files_component_name())
                    ->where($file_table_alias . '.filearea', $plugin::get_response_files_filearea_name());
            });
        }
    }

}
