<?php
/**
 * This file is part of Totara TXP
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

use context;
use moodle_url;
use totara_api\model\client;
use totara_core\advanced_feature;
use totara_mvc\tui_view;

class edit_client extends base_clients {
    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        $client_id = $this->get_client_id();
        $client = client::load_by_id($client_id);
        $tenant = $client->get_tenant_entity();
        if (!is_null($tenant)) {
            $this->tenant_id = $tenant->id;
            $this->tenant_suspended = $tenant->suspended;
        }

        return $client->get_context();
    }

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        advanced_feature::require('api');
        $client_id = $this->get_client_id();
        $this->set_url(new moodle_url('/totara/api/client/edit.php', ['id' => $client_id]));

        $client = client::load_by_id($client_id);
        $this->get_page()->navbar->add($client->name);

        return tui_view::create('totara_api/pages/EditClient', $this->get_tui_props());
    }

    /**
     * @return int
     */
    public function get_client_id(): int {
        return $this->get_required_param('id', PARAM_INT);
    }

    /**
     * @return array
     */
    public function get_tui_props(): array {
        return array_merge(parent::get_tui_props(), ['clientId' => $this->get_client_id()]);
    }
}