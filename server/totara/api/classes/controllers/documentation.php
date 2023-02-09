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
use totara_api\views\documentation_view;
use totara_core\advanced_feature;
use totara_mvc\admin_controller;

class documentation extends admin_controller {

    public const URL =  '/totara/api/documentation/';

    protected $admin_external_page_name = 'totara_api_documentation';

    protected $tenant_id = null;

    protected $layout = 'noblocks';

    /**
     * @inheritDoc
     */
    protected function setup_context(): \context {

        $this->tenant_id = $this->get_optional_param('tenant_id', null, PARAM_INT);

        if (is_null($this->tenant_id)) {
            return \context_system::instance();
        }

        // Ensure requested tenant exists.
        $tenant = tenant::fetch($this->tenant_id, MUST_EXIST);

        return \context_coursecat::instance($tenant->categoryid);
    }

    public function action(): documentation_view {

        advanced_feature::require('api');

        $data = [];
        if (!is_null($this->tenant_id)) {
            $data['tenant_id'] = $this->tenant_id;
        }
        return documentation_view::create('totara_api/documentation_view', $data);
    }
}