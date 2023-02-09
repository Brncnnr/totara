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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_goone
 */

function contentmarketplace_goone_create_course_module_source_records(): void {
    global $DB;

    $transaction = $DB->start_delegated_transaction();

    $source_prefix = 'content-marketplace://goone/';
    $learning_object_field = $DB->sql_concat("'{$source_prefix}'", 'learning_object.external_id');

    // Stupid extra stuff needed for Mssql for some reason
    $mssql_extra = $DB instanceof sqlsrv_native_moodle_database ? 'OFFSET 0 ROWS' : '';
    $source_substring = $DB->sql_cast_char2int(
        $DB->sql_substr('source', strlen($source_prefix) + 1)
    );
    $DB->execute("
        INSERT INTO {marketplace_goone_learning_object} (external_id)
        SELECT {$source_substring}
        FROM (
            SELECT DISTINCT source
            FROM {files} files
            LEFT JOIN {marketplace_goone_learning_object} learning_object ON source = {$learning_object_field}
            WHERE component = 'mod_scorm'
              AND filearea = 'package'
              AND source LIKE '{$source_prefix}%'
              AND learning_object.id IS NULL
            ORDER BY source
            {$mssql_extra}
        ) AS source        
    ");

    $DB->execute("
        INSERT INTO {totara_contentmarketplace_course_module_source} (cm_id, learning_object_id, marketplace_component)
        SELECT context.instanceid, learning_object.id, 'contentmarketplace_goone'
        FROM {marketplace_goone_learning_object} learning_object
        INNER JOIN {files} files ON files.source = {$learning_object_field}
        INNER JOIN {context} context ON context.id = files.contextid
        LEFT JOIN {totara_contentmarketplace_course_module_source} cm_source
           ON cm_source.learning_object_id = learning_object.id
          AND cm_source.marketplace_component = 'contentmarketplace_goone'
          AND cm_source.cm_id = context.instanceid
        WHERE cm_source.id IS NULL
        AND context.contextlevel = " . CONTEXT_MODULE . "
        ORDER BY context.instanceid
    ");

    $transaction->allow_commit();
}
