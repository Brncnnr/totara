<?php
/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

namespace totara_api\controllers;

use core\record\tenant;
use totara_core\advanced_feature;
use totara_mvc\controller;

class index extends controller {

    private $tenant_id;

    /**
     * Temporary index action - just redirect back to clients for now.
     */
    public function process(string $action = '') {

        advanced_feature::require('api');

        $url = new \moodle_url('/totara/api/client/');
        if (!is_null($this->tenant_id)) {
            $url->param('tenant_id', $this->tenant_id);
        }

        redirect($url);
    }

    protected function setup_context(): \context {

        $this->tenant_id = $this->get_optional_param('tenant_id', null, PARAM_INT);

        if (is_null($this->tenant_id)) {
            return \context_system::instance();
        }

        // Ensure requested tenant exists.
        $tenant = tenant::fetch($this->tenant_id, MUST_EXIST);

        return \context_coursecat::instance($tenant->categoryid);
    }
}