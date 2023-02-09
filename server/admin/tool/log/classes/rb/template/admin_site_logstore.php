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
 * @package tool_log
 */

namespace tool_log\rb\template;
use \totara_reportbuilder\rb\template\base;

class admin_site_logstore extends base {

    /**
     * @var array The report content settings.
     */
    public $contentsettings;

    public function __construct() {
        parent::__construct();

        $this->fullname        = get_string('template:admin_site_logstore:title', 'rb_source_site_logstore');
        $this->shortname       = 'admin_site_logstore';
        $this->source          = 'site_logstore';
        $this->label           = get_string('template:admin_site_logstore:label', 'rb_source_site_logstore');
        $this->summary         = get_string('template:admin_site_logstore:summary', 'rb_source_site_logstore');
        $this->accessmode      = REPORT_BUILDER_ACCESS_MODE_ANY;
    }

    /**
     * The defined columns
     *
     * @return array
     */
    protected function define_columns() : array {
        $columns = array(
            array(
                'type' => 'logstore_standard_log',
                'value' => 'timecreated',
                'transform' => 'yearmonthday',
            ),
            array(
                'type' => 'user',
                'value' => 'username',
                'heading' => get_string('template:admin_site_logstore:numberoflogentries', 'rb_source_site_logstore'),
                'customheading' => 1,
                'aggregate' => 'countany',
            ),
        );

        return $columns;
    }

    /**
     * The defined access settings
     *
     * @return array
     */
    protected function define_accesssettings() : array {
        global $CFG;

        $accesssettings = array(
            'role' => array(
                'enable' => 1,
                'activeroles' => $CFG->managerroleid,
                'context' => 'site',
            ),
        );

        return $accesssettings;
    }

    /**
     * Define the graph data
     *
     * @return array
     */
    protected function define_graph() : array {
        return array(
            'type' => 'area',
            'category' => 'logstore_standard_log-timecreated',
            'series' => json_encode(array('user-username')),
        );
    }
}
