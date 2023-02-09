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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\model\helpers;

use context;
use context_coursecat;
use context_system;
use core\entity\tenant;
use core\entity\user;
use moodle_exception;
use require_login_exception;
use totara_api\model\client;

/**
 * This class abstracts much of the logic behind checking capabilities for API client
 *
 * @package totara_api\models\helpers
 */
class client_capability_helper {

    /**
     * @var context
     */
    protected $context;

    /**
     * @var client
     */
    protected $client;

    protected function __construct(?int $category_id = null) {
        if (!empty($category_id)) {
            $this->context = context_coursecat::instance($category_id);
        } else {
            $this->context = context_system::instance();
        }
    }

    /**
     * For if we want to check the client capabilities in general
     *
     * @param tenant|null $tenant
     *
     * @return client_capability_helper
     */
    public static function for_tenant(?tenant $tenant = null): self {
        return new static($tenant->categoryid ?? null);
    }

    /**
     * For if we want to check capabilities for a specific client
     *
     * @param client $client
     *
     * @return client_capability_helper
     */
    public static function for_client(client $client): self {
        $helper = new static($client->tenant_entity->categoryid ?? null);
        $helper->client = $client;
        return $helper;
    }

    /**
     * Check that the user has the specified capability in the specified context
     *
     * @param string $capability
     * @param bool   $require    If true throws an exception, otherwise returns false if not allowed
     * @return bool
     */
    final protected function check_capability(string $capability, bool $require): bool {
        if ($require) {
            require_capability($capability, $this->context);
        }

        return has_capability($capability, $this->context);
    }

    /**
     * Check if the user can manage clients
     *
     * @param bool $require If true throws an exception, otherwise returns false if not allowed
     * @return bool
     * @throws moodle_exception
     */
    public function can_manage(bool $require = false): bool {
        if ($require && !user::logged_in()) {
            throw new require_login_exception('You are not logged in');
        }
        return $this->check_capability('totara/api:manageclients', $require);
    }

    /**
     * @return context
     */
    public function get_execution_context(): context {
        return $this->context;
    }

}