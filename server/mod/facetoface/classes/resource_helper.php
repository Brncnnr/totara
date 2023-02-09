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
* @author Chris Snyder <chris.snyder@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

/**
 * Class resource_helper helps out with resources (rooms, assets, facilitators)
 *
 * @package mod_facetoface
 */
class resource_helper {

    /**
     * For a session at a given date, replaces the old list of resources (if any) with a new list
     *
     * @param int $date
     * @param array $items
     * @param string $type
     * @return bool
     */
    public static function sync_resources(int $date, array $items, string $type): bool {
        global $DB;

        /**
         * TODO: TL-xxx Wouldn't it be nice to make all of these changes safely?
         *
        if (!is_array($DB->get_transaction_start_backtrace())) {
            throw new \coding_exception("resource_helper::sync_resources should be called as part of a database transaction");
        }
         */

        $resource_table = 'facetoface_' . $type . '_dates';
        $resource_id = $type . 'id';

        // First, if no items, then just delete what is here.
        if (empty($items)) {
            return $DB->delete_records($resource_table, ['sessionsdateid' => $date]);
        }

        $olditems = $DB->get_fieldset_select($resource_table, $resource_id, 'sessionsdateid = :date_id', ['date_id' => $date]);

        // Second, there are existing items, but they are the same items. Okay!
        if ((count($items) == count($olditems)) && empty(array_diff($items, $olditems))) {
            return true;
        }

        // Third, find new items and add them
        $new_items = array_diff($items, $olditems);
        foreach ($new_items as $item) {
            $DB->insert_record($resource_table, (object) [
                'sessionsdateid' => $date,
                $resource_id => intval($item)
            ],false);
        }

        // Finally, delete any unused items.
        $unused_items = array_diff($olditems, $items);
        if (count($unused_items)) {
            list($itemid_sql, $itemid_params) = $DB->get_in_or_equal($unused_items, SQL_PARAMS_NAMED);
            list($sd_sql, $sd_params) = $DB->get_in_or_equal($date, SQL_PARAMS_NAMED);
            $where = $resource_id . ' ' . $itemid_sql . ' AND sessionsdateid ' . $sd_sql;
            $params = array_merge($itemid_params, $sd_params);
            $DB->delete_records_select($resource_table, $where, $params);
        }

        return true;
    }
}