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

namespace mod_facetoface\rb\traits;

use rb_column_option;
use rb_filter_option;
use rb_filter_type;
use rb_join;

defined('MOODLE_INTERNAL') || die();

trait rooms {

    /**
     * Add room joints.
     *
     * @param array $joinlist
     * @param string $sessiondatejoin
     * @return void
     */
    protected function add_rooms_to_join_list(array &$joinlist, $sessiondatejoin) {
        $joinlist[] = new rb_join(
            'roomdates',
            'LEFT',
            '{facetoface_room_dates}',
            "roomdates.sessionsdateid = {$sessiondatejoin}.id",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            $sessiondatejoin
        );

        $joinlist[] = new rb_join(
            'room',
            'LEFT',
            '{facetoface_room}',
            'room.id = roomdates.roomid',
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            'roomdates'
        );
    }

    /**
     * Add common room column options (excluding custom fields)
     *
     * @param array $columnoptions
     * @param string $join alias of join or table that provides room fields
     * @param boolean $roomonly
     * @param string $sessionsdatejoin alias of sessionsdate join
     */
    protected function add_rooms_fields_to_columns(array &$columnoptions, $join = 'room', bool $roomonly = false, string $sessionsdatejoin = '') {
        // Room link could have sessionsdate ID that is useful when trying to point to the room in an specific event.
        $roomlinkoptions = [
            'joins' => $join,
            'dbdatatype' => 'text',
            'displayfunc' => 'f2f_room_name_link',
            'defaultheading' => $roomonly ? get_string('name', 'rb_source_facetoface_rooms') : get_string('roomname', 'rb_source_facetoface_rooms'),
            'extrafields' => ['roomid' => "$join.id", 'custom' => "{$join}.custom"]
        ];
        if (!empty($sessionsdatejoin)) {
            $roomlinkoptions['joins'] = [$join, $sessionsdatejoin];
            $roomlinkoptions['extrafields'] = [
                'roomid' => "$join.id",
                'custom' => "{$join}.custom",
                'sessionsdateid' => "{$sessionsdatejoin}.id"
            ];
        }

        $columnoptions[] = new rb_column_option(
            'room',
            'id',
            $roomonly ? get_string('id', 'rb_source_facetoface_rooms') : get_string('roomid', 'rb_source_facetoface_rooms'),
            "$join.id",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'name',
            $roomonly ? get_string('name', 'rb_source_facetoface_rooms') : get_string('roomname', 'rb_source_facetoface_rooms'),
            "$join.name",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'format_string'
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'namelink',
            $roomonly ? get_string('namelink', 'rb_source_facetoface_rooms') : get_string('roomnamelink', 'rb_source_facetoface_rooms'),
            "$join.name",
            $roomlinkoptions
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'published',
            $roomonly ? get_string('sitewide', 'rb_source_facetoface_rooms') : get_string('roomsitewide', 'rb_source_facetoface_rooms'),
            "CASE WHEN $join.custom > 0 THEN 1 ELSE 0 END",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes',
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'description',
            $roomonly ? get_string('description', 'rb_source_facetoface_rooms') : get_string('roomdescription', 'rb_source_facetoface_rooms'),
            "$join.description",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'room_description',
                'extrafields' => array('roomid' => "$join.id")
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'visible',
            $roomonly ? get_string('visible', 'rb_source_facetoface_rooms') : get_string('roomvisible', 'rb_source_facetoface_rooms'),
            "$join.hidden",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes'
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'capacity',
            $roomonly ? get_string('capacity', 'rb_source_facetoface_rooms') : get_string('roomcapacity', 'rb_source_facetoface_rooms'),
            "$join.capacity",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'allowconflicts',
            $roomonly ? get_string('allowconflicts', 'rb_source_facetoface_rooms') : get_string('roomallowconflicts', 'rb_source_facetoface_rooms'),
            "$join.allowconflicts",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'yes_or_no',
            )
        );

        $columnoptions[] = new rb_column_option(
            'room',
            'url',
            $roomonly ? get_string('link', 'rb_source_facetoface_rooms') : get_string('roomlink', 'rb_source_facetoface_rooms'),
            "$join.url",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'room_url',
            )
        );
    }

    /**
     * Add common room filter options (excluding custom fields)
     * @param array $filteroptions
     * @param boolean $roomonly
     */
    protected function add_rooms_fields_to_filters(array &$filteroptions, bool $roomonly = false) {
        $filteroptions[] = new rb_filter_option(
            'room',
            'id',
            $roomonly ? get_string('id', 'rb_source_facetoface_rooms') : get_string('roomid', 'rb_source_facetoface_rooms'),
            'number'
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'name',
            $roomonly ? get_string('name', 'rb_source_facetoface_rooms') : get_string('roomname', 'rb_source_facetoface_rooms'),
            'text',
            array(
                'hiddenoperator' => array(rb_filter_type::RB_FILTER_ISEMPTY, rb_filter_type::RB_FILTER_ISNOTEMPTY)
            )
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'published',
            $roomonly ? get_string('sitewide', 'rb_source_facetoface_rooms') : get_string('roomsitewide', 'rb_source_facetoface_rooms'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array('0' => get_string('yes'), '1' => get_string('no')),
                'customhelptext' => array('sitewide', 'rb_source_facetoface_rooms')
            )
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'description',
            $roomonly ? get_string('description', 'rb_source_facetoface_rooms') : get_string('roomdescription', 'rb_source_facetoface_rooms'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'visible',
            $roomonly ? get_string('visible', 'rb_source_facetoface_rooms') : get_string('roomvisible', 'rb_source_facetoface_rooms'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array('0' => get_string('yes'), '1' => get_string('no'))
            )
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'allowconflicts',
            $roomonly ? get_string('allowconflicts', 'rb_source_facetoface_rooms') : get_string('roomallowconflicts', 'rb_source_facetoface_rooms'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array(1 => get_string('yes'), 0 => get_string('no'))
            )
        );

        $filteroptions[] = new rb_filter_option(
            'room',
            'capacity',
            $roomonly ? get_string('capacity', 'rb_source_facetoface_rooms') : get_string('roomcapacity', 'rb_source_facetoface_rooms'),
            'number'
        );
    }
}