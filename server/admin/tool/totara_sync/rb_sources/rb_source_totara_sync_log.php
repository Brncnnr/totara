<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package tool
 * @subpackage totara_sync
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_totara_sync_log extends rb_base_source {
    function __construct() {
        $this->base = '{totara_sync_log}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_totara_sync_log');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_totara_sync_log');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_totara_sync_log');
        $this->usedcomponents[] = 'tool_totara_sync';
        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    protected function define_joinlist() {
        return array();
    }

    protected function define_columnoptions() {

        $columnoptions = array(
            new rb_column_option(
                'totara_sync_log',
                'id',
                'id',
                "base.id",
                array('displayfunc' => 'integer')
            ),
            new rb_column_option(
                'totara_sync_log',
                'runid',
                get_string('runid', 'tool_totara_sync'),
                "base.runid",
                array('displayfunc' => 'integer')
            ),
            new rb_column_option(
                'totara_sync_log',
                'time',
                get_string('datetime', 'tool_totara_sync'),
                "base.time",
                array('displayfunc' => 'nice_datetime_seconds',
                      'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'totara_sync_log',
                'element',
                get_string('element', 'tool_totara_sync'),
                "base.element",
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'totara_sync_log',
                'logtype',
                get_string('logtype', 'tool_totara_sync'),
                "base.logtype",
                array('displayfunc' => 'totara_sync_log_type')
            ),
            new rb_column_option(
                'totara_sync_log',
                'action',
                get_string('action', 'tool_totara_sync'),
                "base.action",
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'totara_sync_log',
                'info',
                get_string('info', 'tool_totara_sync'),
                "base.info",
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'totara_sync_log',         // type
                'runid',           // value
                get_string('runid', 'tool_totara_sync'), // label
                'number'     // filtertype
            ),
            new rb_filter_option(
                'totara_sync_log',         // type
                'time',           // value
                get_string('datetime', 'tool_totara_sync'), // label
                'date',     // filtertype
                array(
                    'includetime' => true,
                )
            ),
            new rb_filter_option(
                'totara_sync_log',         // type
                'element',           // value
                get_string('element', 'tool_totara_sync'), // label
                'text'     // filtertype
            ),
            new rb_filter_option(
                'totara_sync_log',         // type
                'logtype',           // value
                get_string('logtype', 'tool_totara_sync'), // label
                'select',     // filtertype
                array(
                    'selectfunc' => 'logtypes',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'totara_sync_log',         // type
                'action',           // value
                get_string('action', 'tool_totara_sync'), // label
                'text'     // filtertype
            ),
            new rb_filter_option(
                'totara_sync_log',         // type
                'info',           // value
                get_string('info', 'tool_totara_sync'), // label
                'textarea'     // filtertype
            ),

        );

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array(

            new rb_content_option(
                'date',
                get_string('datetime', 'tool_totara_sync'),
                'base.time'
            ),
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        return array();
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'totara_sync_log',
                'value' => 'id',
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'runid',
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'time',
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'element',
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'logtype',
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'action',
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'info',
            ),
        );

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'totara_sync_log',
                'value' => 'runid',
                'advanced' => 0,
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'time',
                'advanced' => 0,
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'element',
                'advanced' => 0,
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'logtype',
                'advanced' => 0,
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'action',
                'advanced' => 0,
            ),
            array(
                'type' => 'totara_sync_log',
                'value' => 'info',
                'advanced' => 0,
            ),
        );

        return $defaultfilters;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array(
            /*
            // array of rb_column objects, e.g:
            new rb_column(
                '',         // type
                '',         // value
                '',         // heading
                '',         // field
                array()     // options
            )
            */
        );
        return $requiredcolumns;
    }

    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_logtypes() {
        return array(
            'error' => get_string('error', 'tool_totara_sync'),
            'info' => get_string('info', 'tool_totara_sync'),
            'warn' => get_string('warn', 'tool_totara_sync'),
        );
    }


} // end of rb_source_totara_sync_log class

