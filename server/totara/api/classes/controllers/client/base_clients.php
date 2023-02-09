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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\controllers\client;

use context_coursecat;
use context_system;
use core\record\tenant;
use totara_mvc\admin_controller;

abstract class base_clients extends admin_controller {
    /**
     * @var string
     */
    protected $admin_external_page_name = 'totara_api_manage_clients';

    /**
     * @var int|null
     */
    protected $tenant_id = null;

    /**
     * @var int|null
     */
    protected $tenant_suspended;

    /**
     * @inheritDoc
     */
    protected function setup_context(): \context {
        $this->tenant_id = $this->get_optional_param('tenant_id', null, PARAM_INT);

        if (is_null($this->tenant_id)) {
            return context_system::instance();
        }

        // Ensure requested tenant exists.
        $tenant = tenant::fetch($this->tenant_id, MUST_EXIST);
        $this->tenant_suspended = $tenant->suspended;

        return context_coursecat::instance($tenant->categoryid);
    }

    /**
     * @return array
     */
    public function get_tui_props(): array {
        $props = [];
        if (!is_null($this->tenant_id)) {
            $props['tenantId'] = $this->tenant_id;
            $props['tenantSuspended'] = (bool)$this->tenant_suspended;
        }
        return $props;
    }
}