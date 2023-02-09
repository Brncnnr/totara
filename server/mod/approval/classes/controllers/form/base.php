<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers\form;

use context;
use core\entity\user;
use container_approval\approval as container;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\category_interactor;
use totara_core\advanced_feature;
use totara_mvc\report_view;
use totara_mvc\admin_controller;
use moodle_url;
use reportbuilder;

/**
 * Base controller for manage form
 */
abstract class base extends admin_controller {

    /**
     * @inheritDoc
     */
    public function __construct() {
        $this->admin_external_page_name = 'manageapprovalforms';
        $this->layout = $this->get_optional_param('rid', null, PARAM_INT) ? 'noblocks' : 'report';
        parent::__construct();
    }

    /**
     * Checks and call require_login if parameter is set, can be overridden if special set up is needed
     *
     * @return void
     */
    protected function authorize(): void {
        // We do not want to redirect due to not being enrolled
        // we cannot prevent this when passing the course.
        // In this case we do a normal require_login first to capture
        // generic errors, like not being logged in, etc.
        require_login(null, $this->auto_login_guest);

        advanced_feature::require('approval_workflows');
    }

    /**
     * @inheritDoc
     */
    public function setup_context(): context {
        return container::get_default_category_context();
    }

    /**
     * @throws access_denied_exception
     */
    public function can_manage_workflows(): void {
        if (!(new category_interactor(
            container::get_default_category_context(),
            user::logged_in()->id
        ))->can_manage_workflows()) {
            throw access_denied_exception::manage_workflows('Cannot manage approval workflow forms');
        }
    }

    /**
     * @return int
     */
    protected function get_id_param(): int {
        return $this->get_required_param('id', PARAM_INT);
    }

    /**
     * The URL for this page, with params.
     *
     * @param array $params
     * @return moodle_url
     */
    final public static function get_url(array $params = []): moodle_url {
        return new moodle_url(static::URL, $params);
    }

    /**
     * The URL to the report page
     *
     * @return moodle_url
     */
    protected function get_report_url(): moodle_url {
        $rid = $this->get_optional_param('rid', null, PARAM_INT);
        if ($rid) {
            return new moodle_url('/totara/reportbuilder/report.php', ['id' => $rid]);
        }
        return new moodle_url(index::URL);
    }

    /**
     * Returns report view for all perform controllers
     *
     * @param reportbuilder $report
     * @param bool $debug
     * @param string $template
     * @return report_view
     */
    public static function create_report_view(reportbuilder $report, bool $debug = false, string $template = 'totara_mvc/report'): report_view {
        return report_view::create_from_report($report, $debug, $template);
    }
}