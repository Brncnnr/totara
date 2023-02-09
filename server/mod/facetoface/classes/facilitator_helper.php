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

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\customfield_area\facetofacefacilitator as facilitatorcustomfield;

/**
 * Class facilitator_helper
 */
final class facilitator_helper {
    /**
     * facilitator data
     * @param \stdClass $data to be saved includes:
     *      - int {facetoface_facilitator}.id
     *      - int {facetoface_facilitator}.userid
     *      - string {facetoface_facilitator}.name
     *      - int {facetoface_facilitator}.allowconflicts
     *      - string {facetoface_facilitator}.description
     *      - int {facetoface_facilitator}.custom
     *      - int {facetoface_facilitator}.hidden
     * @return facilitator
     */
    public static function save(\stdClass $data): facilitator {
        global $TEXTAREA_OPTIONS;

        $data->custom = $data->notcustom ? 0 : 1;

        if ($data->id) {
            $facilitator = new facilitator($data->id);
        } else {
            if (isset($data->custom) && $data->custom == 1) {
                $facilitator = facilitator::create_custom_facilitator();
            } else {
                $facilitator = new facilitator();
            }
        }
        $facilitator->set_name($data->name);
        $facilitator->set_userid($data->userid);
        $facilitator->set_allowconflicts($data->allowconflicts);
        if (empty($data->custom)) {
            $facilitator->publish();
        }

        // We need to make sure the facilitator exists before formatting the customfields and description.
        if (!$facilitator->exists()) {
            $facilitator->save();
        }

        // Export data to store in customfields and description.
        $data->id = $facilitator->get_id();

        $context = facilitatorcustomfield::get_context();
        $tblprefix = facilitatorcustomfield::get_prefix();
        $component = facilitatorcustomfield::get_component();
        $prefix = $filearea = facilitatorcustomfield::get_area_name();

        customfield_save_data($data, $prefix, $tblprefix);

        // Update description.
        $data = file_postupdate_standard_editor(
            $data,
            'description',
            $TEXTAREA_OPTIONS,
            $context,
            $component,
            $filearea,
            $facilitator->get_id()
        );
        $facilitator->set_description($data->description);
        $facilitator->save();
        // Return new/updated facilitator.
        return $facilitator;
    }

    /**
     * Sync the list of facilitators for a given seminar event date
     * @param integer $date Seminar date Id
     * @param array $facilitators List of facilitator Ids
     * @return bool
     */
    public static function sync(int $date, array $facilitators = []): bool {
        return resource_helper::sync_resources($date, $facilitators, 'facilitator');
    }

    /**
     * Get facilitators for specific session.
     * @param int $sessionid
     * @return string
     */
    public static function get_session_facilitatorids(int $sessionid): string {
        global $DB;

        $facilitatorid = $DB->sql_group_concat($DB->sql_cast_2char('ffd.facilitatorid'), ',');
        $sql = "
        SELECT {$facilitatorid} AS facilitatorids
          FROM {facetoface_sessions_dates} fsd
     LEFT JOIN {facetoface_facilitator_dates} ffd ON ffd.sessionsdateid = fsd.id
         WHERE fsd.id = :id";
        $ret = $DB->get_field_sql($sql, array('id' => $sessionid));
        return $ret ? $ret : '';
    }

    /**
     * Get all facilitator ids for a given session date, sorted by id.
     *
     * @param int $session_date_id
     * @return array
     */
    public static function get_facilitator_ids_sorted(int $session_date_id): array {
        global $DB;
        return array_keys($DB->get_records(
            'facetoface_facilitator_dates',
            ['sessionsdateid' => $session_date_id],
            'facilitatorid',
            'facilitatorid'
        ));
    }
}