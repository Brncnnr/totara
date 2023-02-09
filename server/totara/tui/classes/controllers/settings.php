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

use totara_mvc\admin_controller;

class settings extends admin_controller {

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
    protected $layout = 'noblocks';

    /**
     * settings constructor.
     * @param string $theme
     */
    public function __construct(string $theme) {
        $this->theme = $theme;
        $this->admin_external_page_name = "{$theme}_editor";
        return parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function setup_context(): \context {
        return \context_system::instance();
    }

    /**
     * @inheritDoc
     */
    public function action() {
        global $CFG;

        // If multi-tenancy is enabled then we display tenants otherwise go straight to settings.
        if (!empty($CFG->tenantsenabled)) {
            $tenants_url = new \moodle_url("/totara/tui/theme_tenants.php", ['theme_name' => $this->theme]);
            redirect($tenants_url->out());
        } else {
            $settings_url = new \moodle_url("/totara/tui/theme_settings.php", ['theme_name' => $this->theme]);
            redirect($settings_url->out());
        }
    }

}