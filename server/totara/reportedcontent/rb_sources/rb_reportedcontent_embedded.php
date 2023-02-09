<?php
/**
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_reportedcontent
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Report of inappropriate content as flagged by users
 */
final class rb_reportedcontent_embedded extends rb_base_embedded {
    /**
     * @var string
     */
    public $defaultsortcolumn;

    /**
     * @var int
     */
    public $defaultsortorder;

    /**
     * rb_reportedcontent_embedded constructor.
     *
     * @param array $data
     * @throws coding_exception
     */
    public function __construct(array $data) {
        $this->embeddedparams = $data;
        $this->url = '/totara/reportedcontent/index.php';
        $this->source = 'reportedcontent';
        $this->shortname = 'reportedcontent';
        $this->fullname = get_string('reportedcontentreport', 'totara_reportedcontent');
        $this->columns = array(
            array(
                'type' => 'creator',
                'value' => 'namelink',
                'heading' => get_string('type_creator', 'rb_source_reportedcontent'),
            ),
            array(
                'type' => 'reportedcontent',
                'value' => 'content',
                'heading' => get_string('content', 'rb_source_reportedcontent'),
            ),
            array(
                'type' => 'reportedcontent',
                'value' => 'url',
                'heading' => get_string('url', 'rb_source_reportedcontent'),
            ),
            array(
                'type' => 'reportedcontent',
                'value' => 'time_created',
                'heading' => get_string('timecreated', 'rb_source_reportedcontent'),
            ),
            array(
                'type' => 'reportedcontent',
                'value' => 'action',
                'heading' => get_string('action', 'rb_source_reportedcontent'),
            ),
        );

        $this->filters = array(
            array(
                'type' => 'creator',
                'value' => 'fullname',
                'advanced' => 0,
                'fieldname' => get_string('type_creator_fullname', 'rb_source_reportedcontent'),
            ),
            array(
                'type' => 'reportedcontent',
                'value' => 'status',
                'advanced' => 0,
                'defaultvalue' => ['operator' => 1, 'value' => 0],
            ),
        );

        $this->defaultsortcolumn = "reportedcontent_time_created";
        $this->defaultsortorder = SORT_DESC;

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    /**
     * @return bool
     */
    public function embedded_global_restrictions_supported() {
        return true;
    }

    /**
     * Can searches be saved?
     *
     * @return bool
     */
    public static function is_search_saving_allowed() : bool {
        return false;
    }

    /**
     * Check if the user is capable of accessing this report.
     *
     * @param int $reportfor id of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return bool true if the user can access this report
     */
    public function is_capable($reportfor, $report) : bool {
        $syscontext = context_system::instance();

        return has_capability('totara/reportedcontent:manage', $syscontext, $reportfor);
    }

    /**
     * This report required either resources or workspaces.
     * @return bool
     */
    public static function is_report_ignored() {
        return advanced_feature::is_disabled('engage_resources') &&
            advanced_feature::is_disabled('container_workspace');
    }
}
