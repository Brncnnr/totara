<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Angela Kuznetsova <angela.kuznetsova@totara.com>
 * @package totara_contentmarketplace
 */

defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;

/**
 * Fixed the orphaned records from totara_contentmarketplace_course_module_source table with cm_id = null.
 */
function totara_contentmarketplace_upgradelib_remove_orphaned_records(): void {
    $keys = builder::table('totara_contentmarketplace_course_module_source', 'cms')
        ->left_join(['course_modules', 'cm'], 'cms.cm_id', 'cm.id')
        ->where_null('cm.id')
        ->get()->keys();

    $keys_chunked = array_chunk($keys, builder::get_db()->get_max_in_params());
    $transaction = builder::get_db()->start_delegated_transaction();
    foreach ($keys_chunked as $key_chunked) {
        [$cm_ids_sql, $cm_ids_params] = builder::get_db()->get_in_or_equal($key_chunked, SQL_PARAMS_NAMED);
        builder::get_db()->delete_records_select('totara_contentmarketplace_course_module_source', "id $cm_ids_sql", $cm_ids_params);
    }
    $transaction->allow_commit();
}