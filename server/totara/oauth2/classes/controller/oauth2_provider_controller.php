<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_oauth2
 */

namespace totara_oauth2\controller;

use context;
use context_system;
use totara_mvc\admin_controller;
use totara_mvc\tui_view;
use moodle_url;

class oauth2_provider_controller extends admin_controller {
    /**
     * @var string
     */
    protected $admin_external_page_name = 'oauth2providerdetails';

    /**
     * @var string
     */
    protected $layout = 'admin';

    /**
     * oauth2_provider_controller constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->url = new moodle_url('/totara/oauth2/oauth2_provider.php');
    }

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        return context_system::instance();
    }

    /**
     * @inheritDoc
     */
    public function action(): tui_view {
        return new tui_view('totara_oauth2/pages/Oauth2Provider');
    }
}