<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers\workflow;

use context;
use container_approval\approval as approval_container;
use totara_core\advanced_feature;
use totara_mvc\admin_controller;
use moodle_url;

/**
 * Base controller for manage workflow
 */
abstract class base extends admin_controller {

    /**
     * @inheritDoc
     */
    public function __construct() {
        $this->admin_external_page_name = 'manageapprovalworkflows';
        $this->layout = 'noblocks';
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
        return approval_container::get_default_category_context();
    }

    /**
     * @return int
     */
    protected function get_workflow_id_param(): int {
        return $this->get_required_param('workflow_id', PARAM_INT);
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
}