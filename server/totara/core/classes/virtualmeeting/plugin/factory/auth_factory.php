<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author  Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\virtualmeeting\plugin\factory;

use totara_core\http\client;
use totara_core\virtualmeeting\plugin\provider\auth_provider;

/**
 * Auth factory interface for virtualmeeting plugin factories whose providers require user authentication
 * to delegate permissions
 */
interface auth_factory {
    /**
     * Get an instance of the virtualmeeting plugin's auth service provider
     *
     * @param client $client
     * @return auth_provider
     */
    public function create_auth_service_provider(client $client): auth_provider;
}
