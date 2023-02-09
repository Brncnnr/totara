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

namespace totara_api\observer;

use core\entity\tenant;
use core\event\tenant_updated;
use totara_api\entity\client;
use totara_api\model\client as client_model;

class api_observer {
    /**
     * @param tenant_updated $event
     * @return void
     */
    public static function on_tenant_updated(tenant_updated $event): void {
        /** @var tenant $entity */
        $entity = tenant::repository()->where('id', $event->objectid)->one();
        $old_tenant = $event->get_record_snapshot(tenant::TABLE, $event->objectid);
        if ($entity->suspended && !$old_tenant->suspended) {
            $clients = client::repository()->where('tenant_id', $entity->id)->get();

            if ($clients->count() > 0) {
                foreach ($clients as $client) {
                    client_model::load_by_entity($client)->set_client_status(false);
                }
            }
        }
    }

}