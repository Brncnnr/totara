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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\template;

require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

abstract class base {

    /**
     * @var string The report full name
     */
    public $fullname;

    /**
     * @var string The report short name
     */
    public $shortname;

    /**
     * @var string The report summary
     */
    public $summary;

    /**
     * @var string The report source name
     */
    public $source;

    /**
     * @var string The report label name
     */
    public $label;

    /**
     * @var string Is the report hidden
     */
    public $hidden;

    /**
     * @var string The default sort column
     */
    public $defaultsortcolumn = null;

    /**
     * @var int The default sort order
     */
    public $defaultsortorder = SORT_ASC;

    /**
     * @var string The number of records to show per page
     */
    public $recordsperpage;

    /**
     * @var string The reports content mode
     */
    public $contentmode;

    /**
     * @var string The reports access mode
     */
    public $accessmode;

    /**
     * @var array The report columns
     */
    public $columns;

    /**
     * @var array The report filters
     */
    public $filters;

    /**
     * @var array The report toolbar search columns
     */
    public $toolbarsearchcolumns;

    /**
     * @var array The graph definition.
     */
    public $graph;

    /**
     * @var array The report access settings
     */
    public $accesssettings;

    /**
     * @var array The report content settings.
     */
    public $contentsettings;

    public function __construct() {
        $this->label                = get_string('label:other', 'totara_reportbuilder');
        $this->hidden               = 0;
        $this->recordsperpage       = 40;
        $this->contentmode          = REPORT_BUILDER_CONTENT_MODE_NONE;
        $this->accessmode           = REPORT_BUILDER_ACCESS_MODE_NONE;
        $this->columns              = $this->define_columns();
        $this->filters              = $this->define_filters();
        $this->toolbarsearchcolumns = $this->define_toolbarsearchcolumns();
        $this->contentsettings      = $this->define_contentsettings();
        $this->accesssettings       = $this->define_accesssettings();
        $this->graph                = $this->define_graph();
    }

    /**
     * The defined columns
     *
     * @return array
     */
    protected function define_columns() : array {
        return array();
    }

    /**
     * The defined filters
     *
     * @return array
     */
    protected function define_filters() : array {
        return array();
    }

    /**
     * The defined content settings
     *
     * @return array
     */
    protected function define_contentsettings() : array {
        return array();
    }

    /**
     * The defined toolbar search columns
     *
     * @return array
     */
    protected function define_toolbarsearchcolumns() : array {
        return array();
    }

    /**
     * The defined access settings
     *
     * @return array
     */
    protected function define_accesssettings() : array {
        return array();
    }

    /**
     * Define the graph data
     *
     * @return array
     */
    protected function define_graph() : array {
        return array();
    }
}
