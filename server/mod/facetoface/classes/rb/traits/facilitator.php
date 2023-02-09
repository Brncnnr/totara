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

trait facilitator {

    /**
     * Add facilitator joints.
     *
     * @param array $joinlist
     * @param string $sessiondatejoin
     * @return void
     */
    protected function add_facilitators_to_join_list(array &$joinlist, $sessiondatejoin) {
        $joinlist[] = new rb_join(
            'facilitatordates',
            'LEFT',
            '{facetoface_facilitator_dates}',
            "facilitatordates.sessionsdateid = {$sessiondatejoin}.id",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            $sessiondatejoin
        );

        $joinlist[] = new rb_join(
            'facilitator',
            'LEFT',
            '{facetoface_facilitator}',
            'facilitator.id = facilitatordates.facilitatorid',
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            'facilitatordates'
        );
    }

    /**
     * Add common facilitators column options (excluding custom fields)
     * @param array $columnoptions
     * @param string $join alias of join or table that provides facilitators fields
     * @param boolean $facilitatoronly
     */
    protected function add_facilitators_fields_to_columns(array &$columnoptions, $join = 'facilitator', bool $facilitatoronly = false) {

        $columnoptions[] = new rb_column_option(
            'facilitator',
            'id',
            $facilitatoronly ? get_string('id', 'rb_source_facetoface_facilitator') : get_string('facilitatorid', 'rb_source_facetoface_facilitator'),
            "$join.id",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
            )
        );
        $columnoptions[] = new rb_column_option(
            'facilitator',
            'userid',
            $facilitatoronly ? get_string('userid', 'rb_source_facetoface_facilitator') : get_string('facilitatoruserid', 'rb_source_facetoface_facilitator'),
            "$join.userid",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
            )
        );
        $columnoptions[] = new rb_column_option(
            'facilitator',
            'name',
            $facilitatoronly ? get_string('name', 'rb_source_facetoface_facilitator') : get_string('facilitatorname', 'rb_source_facetoface_facilitator'),
            "$join.name",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'facilitator_name',
                'extrafields' => [
                    'userid' => "$join.userid",
                ],
            )
        );
        $columnoptions[] = new rb_column_option(
            'facilitator',
            'namelink',
            $facilitatoronly ? get_string('namelink', 'rb_source_facetoface_facilitator') : get_string('facilitatornamelink', 'rb_source_facetoface_facilitator'),
            "$join.name",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'facilitator_name_link',
                'defaultheading' => $facilitatoronly ? get_string('name', 'rb_source_facetoface_facilitator') : get_string('facilitatorname', 'rb_source_facetoface_facilitator'),
                'extrafields' => [
                    'id' => "$join.id",
                    'userid' => "$join.userid",
                ],
            )
        );
        $columnoptions[] = new rb_column_option(
            'facilitator',
            'published',
            $facilitatoronly ? get_string('sitewide', 'rb_source_facetoface_facilitator') : get_string('facilitatorsitewide', 'rb_source_facetoface_facilitator'),
            "CASE WHEN $join.custom > 0 THEN 1 ELSE 0 END",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes',
            )
        );
        $columnoptions[] = new rb_column_option(
            'facilitator',
            'description',
            $facilitatoronly ? get_string('description', 'rb_source_facetoface_facilitator') : get_string('facilitatordescription', 'rb_source_facetoface_facilitator'),
            "$join.description",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'facilitator_description',
                'extrafields' => [
                    'id' => "$join.id",
                ]
            )
        );
        $columnoptions[] = new rb_column_option(
            'facilitator',
            'visible',
            $facilitatoronly ? get_string('visible', 'rb_source_facetoface_facilitator') : get_string('facilitatorvisible', 'rb_source_facetoface_facilitator'),
            "$join.hidden",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes',
            )
        );
        $columnoptions[] = new rb_column_option(
            'facilitator',
            'allowconflicts',
            $facilitatoronly ? get_string('allowconflicts', 'rb_source_facetoface_facilitator') : get_string('facilitatorallowconflicts', 'rb_source_facetoface_facilitator'),
            "$join.allowconflicts",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'yes_or_no',
            )
        );
    }

    /**
     * Add common facilitator filter options (excluding custom fields)
     * @param array $filteroptions
     * @param boolean $facilitatoronly
     */
    protected function add_facilitators_fields_to_filters(array &$filteroptions, bool $facilitatoronly = false) {

        $filteroptions[] = new rb_filter_option(
            'facilitator',
            'id',
            $facilitatoronly ? get_string('id', 'rb_source_facetoface_facilitator') : get_string('facilitatorid', 'rb_source_facetoface_facilitator'),
            'number'
        );
        $filteroptions[] = new rb_filter_option(
            'facilitator',
            'name',
            $facilitatoronly ? get_string('name', 'rb_source_facetoface_facilitator') : get_string('facilitatorname', 'rb_source_facetoface_facilitator'),
            'text',
            array(
                'hiddenoperator' => array(rb_filter_type::RB_FILTER_ISEMPTY, rb_filter_type::RB_FILTER_ISNOTEMPTY)
            )
        );
        $filteroptions[] = new rb_filter_option(
            'facilitator',
            'published',
            $facilitatoronly ? get_string('sitewide', 'rb_source_facetoface_facilitator') : get_string('facilitatorsitewide', 'rb_source_facetoface_facilitator'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array(
                    '0' => get_string('yes'),
                    '1' => get_string('no'),
                ),
                'customhelptext' => array('sitewide', 'rb_source_facetoface_facilitator')
            )
        );
        $filteroptions[] = new rb_filter_option(
            'facilitator',
            'description',
            $facilitatoronly ? get_string('description', 'rb_source_facetoface_facilitator') : get_string('facilitatordescription', 'rb_source_facetoface_facilitator'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'facilitator',
            'visible',
            $facilitatoronly ? get_string('visible', 'rb_source_facetoface_facilitator') : get_string('facilitatorvisible', 'rb_source_facetoface_facilitator'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array(
                    '0' => get_string('yes'),
                    '1' => get_string('no'),
                )
            )
        );
        $filteroptions[] = new rb_filter_option(
            'facilitator',
            'allowconflicts',
            $facilitatoronly ? get_string('allowconflicts', 'rb_source_facetoface_facilitator') : get_string('facilitatorallowconflicts', 'rb_source_facetoface_facilitator'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array(
                    '1' => get_string('yes'),
                    '0' => get_string('no'),
                )
            )
        );
    }
}