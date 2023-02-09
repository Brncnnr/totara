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

use rb_column_option;
use rb_filter_option;
use rb_filter_type;
use rb_join;

defined('MOODLE_INTERNAL') || die();

trait assets {

    /**
     * Add asset joints.
     *
     * @param array $joinlist
     * @param string $sessiondatejoin
     * @return void
     */
    protected function add_assets_to_join_list(array &$joinlist, string $sessiondatejoin) {
        $joinlist[] = new rb_join(
            'assetdates',
            'LEFT',
            '{facetoface_asset_dates}',
            "assetdates.sessionsdateid = {$sessiondatejoin}.id",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            $sessiondatejoin
        );

        $joinlist[] = new rb_join(
            'asset',
            'LEFT',
            '{facetoface_asset}',
            'asset.id = assetdates.assetid',
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            'assetdates'
        );
    }

    /**
     * Add common assets column options (excluding custom fields)
     * @param array $columnoptions
     * @param string $join alias of join or table that provides assets fields
     * @param boolean $assetonly
     */
    protected function add_assets_fields_to_columns(array &$columnoptions, $join = 'asset', $assetonly = false) {
        $columnoptions[] = new rb_column_option(
            'asset',
            'id',
            $assetonly ? get_string('id', 'rb_source_facetoface_asset') : get_string('assetid', 'rb_source_facetoface_asset'),
            "$join.id",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'name',
            $assetonly ? get_string('name', 'rb_source_facetoface_asset') : get_string('assetname', 'rb_source_facetoface_asset'),
            "$join.name",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'format_string'
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'namelink',
            $assetonly ? get_string('namelink', 'rb_source_facetoface_asset') : get_string('assetnamelink', 'rb_source_facetoface_asset'),
            "$join.name",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'f2f_asset_name_link',
                'defaultheading' => $assetonly ? get_string('name', 'rb_source_facetoface_asset') : get_string('assetname', 'rb_source_facetoface_asset'),
                'extrafields' => array('assetid' => "$join.id")
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'published',
            $assetonly ? get_string('sitewide', 'rb_source_facetoface_asset') : get_string('assetsitewide', 'rb_source_facetoface_asset'),
            "CASE WHEN $join.custom > 0 THEN 1 ELSE 0 END",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes',
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'description',
            $assetonly ? get_string('description', 'rb_source_facetoface_asset') : get_string('assetdescription', 'rb_source_facetoface_asset'),
            "$join.description",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'asset_description',
                'extrafields' => array('assetid' => "$join.id")
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'visible',
            $assetonly ? get_string('visible', 'rb_source_facetoface_asset') : get_string('assetvisible', 'rb_source_facetoface_asset'),
            "$join.hidden",
            array(
                'joins' => $join,
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_no_yes'
            )
        );

        $columnoptions[] = new rb_column_option(
            'asset',
            'allowconflicts',
            $assetonly ? get_string('allowconflicts', 'rb_source_facetoface_asset') : get_string('assetallowconflicts', 'rb_source_facetoface_asset'),
            "$join.allowconflicts",
            array(
                'joins' => $join,
                'dbdatatype' => 'text',
                'displayfunc' => 'yes_or_no',
            )
        );
    }

    /**
     * Add common asset filter options (excluding custom fields)
     * @param array $filteroptions
     * @param bool $assetonly
     */
    protected function add_assets_fields_to_filters(array &$filteroptions, bool $assetonly = false) {
        $filteroptions[] = new rb_filter_option(
            'asset',
            'id',
            $assetonly ? get_string('id', 'rb_source_facetoface_asset') : get_string('assetid', 'rb_source_facetoface_asset'),
            'number'
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'name',
            $assetonly ? get_string('name', 'rb_source_facetoface_asset') : get_string('assetname', 'rb_source_facetoface_asset'),
            'text',
            array(
                'hiddenoperator' => array(rb_filter_type::RB_FILTER_ISEMPTY, rb_filter_type::RB_FILTER_ISNOTEMPTY)
            )
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'published',
            $assetonly ? get_string('sitewide', 'rb_source_facetoface_asset') : get_string('assetsitewide', 'rb_source_facetoface_asset'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array('0' => get_string('yes'), '1' => get_string('no')),
                'customhelptext' => array('sitewide', 'rb_source_facetoface_asset')
            )
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'description',
            $assetonly ? get_string('description', 'rb_source_facetoface_asset') : get_string('assetdescription', 'rb_source_facetoface_asset'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'visible',
            $assetonly ? get_string('visible', 'rb_source_facetoface_asset') : get_string('assetvisible', 'rb_source_facetoface_asset'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array('0' => get_string('yes'), '1' => get_string('no'))
            )
        );

        $filteroptions[] = new rb_filter_option(
            'asset',
            'allowconflicts',
            $assetonly ? get_string('allowconflicts', 'rb_source_facetoface_asset') : get_string('assetallowconflicts', 'rb_source_facetoface_asset'),
            'select',
            array(
                'simplemode' => true,
                'selectchoices' => array(1 => get_string('yes'), 0 => get_string('no'))
            )
        );
    }
}