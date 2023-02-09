<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\userdata;

use coding_exception;
use context;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;
use mod_facetoface\customfield_area\facetofacefacilitator as facilitatorcustomfield;

class facilitator_customfields extends facilitator {

    /**
     * Execute user data export for this item.
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {

        $export = new export();
        $customfielddata = self::get_customfield_data($user, $context);
        $export->data = self::add_files($export, $user, $customfielddata);
        return $export;
    }

    /**
     * Get customfield data.
     * @param $customfieldtype
     * @param target_user $user
     * @param context $context
     * @return array
     * @throws coding_exception
     */
    private static function get_customfield_data(target_user $user, context $context) {
        global $DB;

        $facilitators = self::get_facilitators($user, $context);
        $facilitatorids = array_column($facilitators, 'id');
        if (empty($facilitatorids)) {
            return [];
        }

        [$facilitatorsqlin, $facilitatorinparams] = $DB->get_in_or_equal($facilitatorids);

        // For one *_info_data record there can be any number of records in the *_info_data_param table that
        // we want to include in the export, so left join to that and expect multiple rows for every *_info_data.id.
        $sql = "SELECT d.id,
                       d.data,
                       d.fieldid,
                       d.facetofacefacilitatorid AS facilitatorid,
                       dp.value AS paramvalue
                  FROM {facetoface_facilitator_info_data} d
             LEFT JOIN {facetoface_facilitator_info_data_param} dp ON dp.dataid = d.id
                 WHERE facetofacefacilitatorid $facilitatorsqlin";

        $records = $DB->get_recordset_sql($sql, $facilitatorinparams);
        $customfielddata = [];
        foreach ($records as $record) {
            if (!isset($customfielddata[$record->id])) {
                $customfielddata[$record->id]['facilitatorid'] = $record->facilitatorid;
                $customfielddata[$record->id]['data'] = $record->data;
                $customfielddata[$record->id]['params'] = [];
            }
            if (!empty($record->paramvalue)) {
                $customfielddata[$record->id]['params'][] = $record->paramvalue;
            }
        }

        return $customfielddata;
    }

    /**
     * Add relevant files for export.
     * @param export $export
     * @param target_user $user
     * @param array $exportdata
     * @param array $fileareas
     * @return array
     */
    private static function add_files(export $export, target_user $user, array $customfielddata): array {
        // Custom field files are stored with system context, so we filter by userid.
        $fs = get_file_storage();

        $syscontext = facilitatorcustomfield::get_context();
        $component = facilitatorcustomfield::get_component();
        $fileareas = facilitatorcustomfield::get_fileareas();

        $files = $fs->get_area_files(
            $syscontext->id,
            $component,
            $fileareas[0],
            false,
            'filename ASC',
            false,
            0,
            $user->id
        );

        foreach ($customfielddata as $id => $data) {
            $customfielddata[$id]['files'] = [];

            foreach ($files as $file) {
                if ($file->get_itemid() == $id) {
                    $customfielddata[$id]['files'][] = $export->add_file($file);
                }
            }
        }

        $files = $fs->get_area_files(
            $syscontext->id,
            'totara_customfield',
            $fileareas[1],
            false,
            'filename ASC',
            false,
            0,
            $user->id
        );

        foreach ($customfielddata as $id => $data) {
            $customfielddata[$id]['files'] = [];

            foreach ($files as $file) {
                if ($file->get_itemid() == $id) {
                    $customfielddata[$id]['files'][] = $export->add_file($file);
                }
            }
        }

        return $customfielddata;
    }
}