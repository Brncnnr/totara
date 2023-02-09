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

namespace totara_core\virtualmeeting\plugin\provider;

use core\entity\user;
use totara_core\virtualmeeting\exception\auth_exception;

/**
 * Virtualmeeting plugin auth service provider interface, communicates directly with the
 * third-party virtual meeting provider's authentication/authorization API
 */
interface auth_provider {
    /**
     * Get the authentication endpoint URL for the current user.
     * The system opens a popup window with the URL when authentication/authorization is required.
     *
     * @return string
     */
    public function get_authentication_endpoint(): string;

    /**
     * Get the user's external profile for display purposes.
     * If the user has not been authenticated, the function must throw an exception.
     *
     * @param user $user
     * @param bool $update if set to true, the function must refresh the user's token.
     *                     otherwise the function may refresh it only if necessary.
     * @return array comprising the following keys
     * - name: account name etc.
     * - email: email address (optional)
     * - friendly_name: user's name (optional)
     * @throws auth_exception
     */
    public function get_profile(user $user, bool $update): array;

    /**
     * Authorise a user.
     * This function is called by the common authentication redirection point.
     *
     * @param user $user always the current user
     * @param string $method
     * @param array $headers
     * @param string $body
     * @param array $query_get
     * @param array $query_post
     */
    public function authorise(user $user, string $method, array $headers, string $body, array $query_get, array $query_post): void;
}
