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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers;

use reportbuilder;
use totara_core\advanced_feature;
use totara_mvc\controller;
use totara_mvc\report_view;
use totara_mvc\tui_view;
use totara_mvc\view;
use moodle_url;
use mod_approval\views\override_nav_breadcrumbs;

/**
 * A base controller for approval workflows.
 */
abstract class workflow_controller extends controller {

    /**
     * Checks and call require_login if parameter is set, can be overridden if special set up is needed
     *
     * @return void
     */
    protected function authorize(): void {
        advanced_feature::require('approval_workflows');
        parent::authorize();
    }

    /**
     * Returns tui view for all approval controllers
     *
     * @param string $component
     * @param array $props
     * @return tui_view
     */
    public static function create_tui_view(string $component, array $props = []): tui_view {
        return tui_view::create($component, $props)
            ->add_override(new override_nav_breadcrumbs());
    }

    /**
     * Returns report view for all approval controllers
     *
     * @param reportbuilder $report
     * @param bool $debug
     *
     * @return report_view
     */
    public static function create_report_view(reportbuilder $report, bool $debug): report_view {
        return report_view::create_from_report($report, $debug)
            ->add_override(new override_nav_breadcrumbs());
    }

    /**
     * Returns view for all approval controllers
     *
     * @param string|null $template
     * @param array|false $data
     * @return view
     */
    public static function create_view(?string $template, $data = false): view {
        return view::create($template, $data ?: [])
            ->add_override(new override_nav_breadcrumbs());
    }

    /**
     * The base URL for this page.
     *
     * @return string
     */
    abstract public static function get_base_url(): string;

    /**
     * The URL for this page, with params.
     *
     * @param array $params
     * @return moodle_url
     */
    final public static function get_url(array $params = []): moodle_url {
        return new moodle_url(static::get_base_url(), $params);
    }
}
