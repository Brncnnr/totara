<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

namespace mod_facetoface;

/**
 * Additional asset functionality.
 */
final class asset_helper {

    /**
     * Asset data
     *
     * @param \stdClass $data to be saved includes:
     *      - int {facetoface_asset}.id
     *      - string {facetoface_asset}.name
     *      - int {facetoface_asset}.allowconflicts
     *      - string {facetoface_asset}.description
     *      - int {facetoface_asset}.custom
     *      - int {facetoface_asset}.hidden
     * @return asset
     */
    public static function save(\stdClass $data): asset {
        global $TEXTAREA_OPTIONS;

        $data->custom = $data->notcustom ? 0 : 1;

        if ($data->id) {
            $asset = new asset($data->id);
        } else {
            if (isset($data->custom) && $data->custom == 1) {
                $asset = asset::create_custom_asset();
            } else {
                $asset = new asset();
            }
        }
        $asset->set_name($data->name);
        $asset->set_allowconflicts($data->allowconflicts);
        if (empty($data->custom)) {
            $asset->publish();
        }

        // We need to make sure the asset exists before formatting the customfields and description.
        if (!$asset->exists()) {
            $asset->save();
        }

        // Export data to store in customfields and description.
        $data->id = $asset->get_id();
        customfield_save_data($data, 'facetofaceasset', 'facetoface_asset');

        // Update description.
        $data = file_postupdate_standard_editor(
            $data,
            'description',
            $TEXTAREA_OPTIONS,
            $TEXTAREA_OPTIONS['context'],
            'mod_facetoface',
            'asset',
            $asset->get_id()
        );
        $asset->set_description($data->description);
        $asset->save();
        // Return new/updated asset.
        return $asset;
    }

    /**
     * Sync the list of assets for a given seminar event date
     *
     * @param integer $date Seminar date Id
     * @param array $assets List of asset Ids
     * @return bool
     */
    public static function sync(int $date, array $assets = []): bool {
        return resource_helper::sync_resources($date, $assets, 'asset');
    }

    /**
     * Get assets for specific session.
     *
     * @param int $sessionid
     * @return string
     */
    public static function get_session_assetids(int $sessionid): string {
        global $DB;

        $assetid = $DB->sql_group_concat($DB->sql_cast_2char('fad.assetid'), ',');
        $sql = "
        SELECT {$assetid} AS assetids
          FROM {facetoface_sessions_dates} fsd
          LEFT JOIN {facetoface_asset_dates} fad ON (fad.sessionsdateid = fsd.id)
         WHERE fsd.id = :id";

        $ret = $DB->get_field_sql($sql, array('id' => $sessionid));
        return $ret ? $ret : '';
    }
}
