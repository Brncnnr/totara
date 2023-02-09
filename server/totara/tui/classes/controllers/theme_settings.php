<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_tui
 */

namespace totara_tui\controllers;

use context;
use moodle_url;
use totara_mvc\admin_controller;
use totara_mvc\tui_view;
use core\entity\tenant;
use core\theme\settings as core_theme_settings;

class theme_settings extends admin_controller {

    /**
     * @var string
     */
    protected $theme;

    /**
     * The tenant id, if one was provided.
     * @var int
     */
    protected $tenant_id;

    /**
     * @inheritDoc
     */
    protected $layout = 'standard';

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        return \context_system::instance();
    }

    /**
     * @inheritDoc
     */
    protected function init_page_object() {
        global $SITE;

        parent::init_page_object();

        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            // Don't set page options while under test as it will prevent
            // creating the controller multiple times in tests.
            return;
        }

        // Force current theme to be the theme we are editing. Only for the
        // current request, it does not persist when you switch to other pages.
        // This is neccesary as each theme's theme settings are implemented as
        // *overrides* to the core theme settings views.
        $theme = $this->get_optional_param('theme_name', null, PARAM_COMPONENT);
        if ($theme) {
            $page = $this->get_page();
            // Must set course before theme.
            // If we don't set it here, set_course() will throw an exception in
            // $page->initialise_theme_and_output().
            // We already set the context in the parent method, so calling this
            // won't change the context.
            $page->set_course($SITE);
            $page->force_theme($theme);
        }
    }

    /**
     * @inheritDoc
     */
    public function process(string $action = '') {
        global $CFG, $USER;

        // Get the theme name from parameter.
        $this->theme = $this->get_required_param('theme_name', PARAM_COMPONENT);
        $this->tenant_id = $this->get_optional_param('tenant_id', null, PARAM_INT);

        // Set external admin page name.
        $this->admin_external_page_name = "{$this->theme}_editor";

        require_login(null, false);
        $url = new moodle_url('/totara/tui/theme_settings.php', ['theme_name' => $this->theme]);
        if (!empty($this->tenant_id)) {
            $url->param('tenant_id', $this->tenant_id);
        }
        $this->set_url($url);

        // Redirect to the correct tenant this user belongs to.
        if (!empty($USER->tenantid) && $USER->tenantid != $this->tenant_id) {
            redirect(new moodle_url($url, ['tenant_id' => $USER->tenantid]));
        }

        // Request access to theme settings for tenant or site.
        $tenant = null;
        if (!empty($this->tenant_id)) {
            $tenant = tenant::repository()->find($this->tenant_id);
            if (empty($tenant)) {
                throw new \moodle_exception('errorinvalidtenant', 'totara_tenant');
            }
            $context = \context_tenant::instance($this->tenant_id);
            $this->check_user_access($context);

            // If user does not belong to the tenancy but has the required access then let the user through.
            if (empty($USER->tenantid)) {
                $categorycontext = \context_coursecat::instance($tenant->categoryid);
                require_capability('totara/tui:themesettings', $categorycontext);
                $this->get_page()->set_pagelayout('admin');
                $this->get_page()->set_title(get_site()->shortname);
                $this->get_page()->set_heading(get_site()->fullname);
                parent::process($action);
                return;
            }
        } else {
            $context = \context_system::instance();
            $this->check_user_access($context);
        }

        // At this point:
        // 1. Tenant ID is not set as a parameter which means we want access to site's theme settings.
        // 2. Tenant ID is set and user belongs to the tenancy.
        require_once($CFG->libdir.'/adminlib.php');
        admin_externalpage_setup(
            "{$this->theme}_editor",
            '', // not used
            ['theme' => $this->theme],
            '',
            []
        );

        parent::process($action);
    }

    /**
     * Confirm that the user has access to this page.
     *
     * @param context $context
     */
    private function check_user_access(context $context): void {
        if ($context->is_user_access_prevented()) {
            throw new \moodle_exception(
                'accessdenied',
                'admin',
                '',
                null,
                $context->id . ' prevented access'
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function action(): tui_view {
        global $CFG, $PAGE;

        $core_theme_settings = new core_theme_settings($PAGE->theme, $this->tenant_id ?? 0);

        $props = [
            'theme' => $this->theme,
            'themeName' => get_string_manager()->string_exists('pluginname', 'theme_' . $this->theme)
                ? get_string('pluginname', 'theme_' . $this->theme)
                : null,
            'customizableTenantSettings' => $core_theme_settings->get_customizable_tenant_theme_settings(),
        ];

        // If tenant is selected then get selected tenant details.
        $tenant_id = $this->get_optional_param('tenant_id', null,PARAM_INT);
        if (!empty($tenant_id) && $CFG->tenantsenabled) {
            /** @var tenant $tenant */
            $tenant = tenant::repository()->find($tenant_id);
            if (empty($tenant)) {
                throw new \moodle_exception('errorinvalidtenant', 'totara_tenant');
            }

            // First confirm that the user has access to the specific tenant appearance settings.
            $categorycontext = \context_coursecat::instance($tenant->categoryid);
            require_capability('totara/tui:themesettings', $categorycontext);

            // Set up props for the component.
            $props['selectedTenantId'] = $tenant->id;
            $props['selectedTenantName'] = $tenant->name;
        } else {
            // User need capability to access site appearance settings.
            require_capability('totara/core:appearance', \context_system::instance());
        }

        $tui_view = tui_view::create('tui/pages/ThemeSettings', $props);
        $tui_view->set_title(get_string('theme_settings', 'totara_tui'));

        return $tui_view;
    }

}