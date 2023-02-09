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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers;

use context_module;
use mod_perform\activity_access_denied_exception;
use mod_perform\models\activity\helpers\access_checks;
use mod_perform\views\override_nav_breadcrumbs;
use moodle_url;
use reportbuilder;
use totara_core\advanced_feature;
use totara_mvc\controller;
use totara_mvc\report_view;
use totara_mvc\tui_view;
use totara_mvc\view;

/**
 * Common logic across all perform pages.
 *
 * @package mod_perform\controllers
 */
abstract class perform_controller extends controller {

    /**
     * Constant used to determine the maximum number of rows that can be used as a source
     * of an export. This is used to prevent an excessively large query when using an
     * embedded report result as the basis of an export.
     */
    public const BULK_EXPORT_MAX_ROWS = 10000;

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

        advanced_feature::require('performance_activities');

        // If we are in an activity context do some additional checks
        if ($this->context instanceof context_module) {
            $helper = access_checks::for_module_context($this->context);
            try {
                $helper->check();
                $this->get_page()->set_cm($helper->get_cm(), $helper->get_course());
            } catch (activity_access_denied_exception $exception) {
                $this->get_page()->set_url('/mod/perform/manage/activity/index.php');
                $this->get_page()->set_cm($helper->get_cm(), $helper->get_course());
                echo $this->action_invalid()->render();
                exit();
            }
        }
    }

    /**
     * @return view
     * @throws \coding_exception
     */
    public function action_invalid() {
        $notification = view::core_renderer()->notification(get_string('error_access_permission_missing', 'mod_perform'), 'error');
        return self::create_view(null, $notification);
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

    /**
     * Returns tui view for all perform controllers
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
     * Returns report view for all perform controllers
     *
     * @param reportbuilder $report
     * @param bool $debug
     * @return report_view
     */
    public static function create_report_view(reportbuilder $report, bool $debug): report_view {
        return report_view::create_from_report($report, $debug)
            ->add_override(new override_nav_breadcrumbs());
    }

    /**
     * Returns view for all perform controllers
     *
     * @param string|null $template
     * @param array $data
     * @return view
     */
    public static function create_view(?string $template, $data = []): view {
        return view::create($template, $data)
            ->add_override(new override_nav_breadcrumbs());
    }

}
