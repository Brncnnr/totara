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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package totara_job
 */

/**
 * Fix any invalid temp manager assignments that were introduced via TL-31561.
 */
function totara_job_fix_dangling_temp_manager_assignments(): void {
    global $DB;

    $finder = "
         SELECT ja.id
           FROM {job_assignment} ja
      LEFT JOIN {job_assignment} ref ON ref.id = ja.tempmanagerjaid
          WHERE ja.tempmanagerjaid IS NOT NULL
            AND ref.id IS NULL
    ";

    $update = "
        UPDATE {job_assignment}
        SET tempmanagerjaid = NULL,
            tempmanagerexpirydate = NULL
        WHERE id
    ";

    $dangling = $DB->get_fieldset_sql($finder);
    foreach (array_chunk($dangling, 200) as $chunk) {
        [$records, $ids] = $DB->get_in_or_equal($chunk);
        $DB->execute("$update $records", $ids);
    }
}
