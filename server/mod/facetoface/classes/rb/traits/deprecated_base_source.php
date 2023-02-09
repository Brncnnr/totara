<?php
/*
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\rb\traits;

defined('MOODLE_INTERNAL') || die();

use core_text;
use html_writer;
use moodle_url;
use rb_column_option;

trait deprecated_base_source {

    /**
     * Add deprecated common facetoface session columns
     * Requires 'sessions' join and custom named join to {facetoface_sessions_dates} (by default 'base')
     * @param array $columnoptions
     * @param string $sessiondatejoin Join that provides {facetoface_sessions_dates}
     */
    private function add_deprecated_session_common_to_columns(&$columnoptions, $sessiondatejoin = 'base') {
        $columnoptions[] = new rb_column_option(
            'facetoface',
            'sessionid',
            get_string('sessionid', 'rb_source_facetoface_room_assignments'),
            'sessions.id',
            array(
                'deprecated' => true,
                'joins' => 'sessions',
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );
    }

    /**
     * Asset name linked to asset details
     *
     * @deprecated Since Totara 12.0
     * @param string $assetname
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_asset_name_link($assetname, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_asset_name_link been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_asset_name_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $assetname;
        }
        if (empty($assetname)) {
            return '';
        }
        return html_writer::link(
            new moodle_url('/mod/facetoface/reports/assets.php', array('assetid' => $row->assetid)),
            $assetname
        );
    }

    /**
     * Display opposite to rb_display_yes_no. E.g. zero value will be 'yes', and non-zero 'no'
     *
     * @deprecated Since Totara 12.0
     * @param scalar $no
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_no_yes($no, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_no_yes been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_no_yes::display', DEBUG_DEVELOPER);
        return ($no) ? get_string('no') : get_string('yes');
    }

    /**
     * Display if room allows scheduling conflicts
     *
     * @deprecated Since Totara 12.0
     * @param string $allowconflicts
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_conflicts($allowconflicts, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_conflicts been deprecated since Totara 12.0. Use \totara_reportbuilder\rb\display\yes_or_no::display()', DEBUG_DEVELOPER);
        return $allowconflicts ? get_string('yes') : get_string('no');
    }

    /**
     * Display count of attendees and link to session attendees report page.
     *
     * @deprecated Since Totara 12.0
     * @param int $cntattendees
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_numattendeeslink($cntattendees, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_numattendeeslink been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_num_attendees_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $cntattendees;
        }
        if (!$cntattendees) {
            $cntattendees = '0';
        }

        $viewattendees = get_string('viewattendees', 'mod_facetoface');

        $description = html_writer::span($viewattendees, 'sr-only');
        return html_writer::link(new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $row->session)), $cntattendees . $description, array('title' => $viewattendees));

    }

    /**
     * Return list of user names linked to their profiles from string of concatenated user names, their ids,
     * and length of every name with id
     *
     * @deprecated Since Totara 12.0
     * @param string $name Concatenated list of names, ids, and lengths
     * @param stdClass $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_coded_link_user($name, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_coded_link_user been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_coded_user_link::display', DEBUG_DEVELOPER);
        // Concatenated names are provided as (kind of) pascal string beginning with id in the following format:
        // length_of_following_string.' '.id.' '.name.', '
        if (empty($name)) {
            return '';
        }
        $leftname = $name;
        $result = array();
        while(true) {
            $len = (int)$leftname; // Take string length.
            if (!$len) {
                break;
            }
            $idname = core_text::substr($leftname, core_text::strlen((string)$len)+1, $len, 'UTF-8');
            if (empty($idname)) {
                break;
            }
            $idendpos = core_text::strpos($idname, ' ');
            $id = (int)core_text::substr($idname, 0, $idendpos);
            if (!$id) {
                break;
            }
            $name = trim(core_text::substr($idname, $idendpos));
            $result[] = ($isexport) ? $name : html_writer::link(new moodle_url('/user/profile.php', array('id' => $id)), $name);

            // length(length(idname)) + length(' ') + length(idname) + length(', ').
            $leftname = core_text::substr($leftname, core_text::strlen((string)$len)+1+$len+2);
        }
        return implode(', ', $result);
    }

    /**
     * Convert a f2f approvaltype into a human readable string
     *
     * @deprecated Since Totara 12.0
     * @param int $approvaltype
     * @param object $row
     * @return string
     */
    function rb_display_f2f_approval($approvaltype, $row) {
        debugging('rb_facetoface_base_source::rb_display_f2f_approval been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_approval::display', DEBUG_DEVELOPER);
        return facetoface_get_approvaltype_string($approvaltype, $row->approvalrole);
    }

    /**
     * Room name linked to room details
     *
     * @deprecated Since Totara 12.0
     * @param string $roomname
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_room_name_link($roomname, $row, $isexport = false) {
        debugging('rb_facetoface_base_source::rb_display_room_name_link been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_room_name_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $roomname;
        }
        if (empty($roomname)) {
            return '';
        }

        if ($row->custom) {
            $roomname .= get_string("roomcustom", "mod_facetoface");
        }

        return html_writer::link(
            new moodle_url('/mod/facetoface/reports/rooms.php', array('roomid' => $row->roomid)),
            $roomname
        );
    }
}