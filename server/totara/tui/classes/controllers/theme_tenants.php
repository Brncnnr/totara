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

use core\entity\tenant;
use totara_mvc\admin_controller;
use totara_mvc\tui_view;

class theme_tenants extends admin_controller {

    /**
     * @var string
     */
    protected $theme;

    /**
     * @inheritDoc
     */
    protected $admin_external_page_name;

    /**
     * @inheritDoc
     */
    protected $layout = 'standard';

    /**
     * @inheritDoc
     */
    protected function setup_context(): \context {
        return \context_system::instance();
    }

    /**
     * @inheritDoc
     */
    public function process(string $action = '') {
        // Get the theme name from parameter.
        $this->theme = $this->get_required_param('theme_name', PARAM_COMPONENT);
        $this->admin_external_page_name = "{$this->theme}_editor";
        $this->set_url(new \moodle_url('/totara/tui/theme_tenants.php', ['theme_name' => $this->theme]));
        parent::process($action);
    }

    /**
     * @inheritDoc
     */
    public function action(): tui_view {
        global $CFG;

        // Redirect to settings if tenants disabled.
        if (empty($CFG->tenantsenabled)) {
            $settings_url = new \moodle_url("/totara/tui/theme_settings.php", ['theme_name' => $this->theme]);
            redirect($settings_url->out());
        }

        // Get theme_config.
        $theme_config = \theme_config::load($this->theme);

        // Get tenant information for the tui view.
        $tenants = tenant::repository()->select(['id', 'idnumber', 'name'])->get()->to_array();

        // Only get the tenants that the user has access to.
        $tenants = array_filter($tenants, function ($tenant) {
            $context = \context_tenant::instance($tenant['id']);
            return has_capability('totara/tui:themesettings', $context);
        });

        $tenants = array_map(function ($tenant) use ($theme_config) {
            $theme_settings = new \core\theme\settings($theme_config, $tenant['id']);
            $tenant['customBranding'] = $theme_settings->is_tenant_branding_enabled();
            return $tenant;
        }, $tenants);
        $props = [
            'theme' => $this->theme,
            'themeName' => get_string_manager()->string_exists('pluginname', 'theme_' . $this->theme)
                ? get_string('pluginname', 'theme_' . $this->theme)
                : null,
            'tenants' => $tenants
        ];

        $tui_view = tui_view::create('tui/pages/ThemeTenants', $props);
        $tui_view->set_title(get_string('select_tenant', 'totara_tui'));

        return $tui_view;
    }

}