<?php
/**
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\oauth;

use totara_core\http\request;
use totara_core\http\response;
use totara_contentmarketplace\token\token;

/**
 * An abstraction to provide oauth2 request and processing the response.
 */
abstract class oauth_2 {
    /**
     * @return request
     */
    abstract public function prepare_request(): request;

    /**
     * @param response $response    The HTTP response, which we need to parse it to obtain the access token.
     * @return token
     */
    abstract public function process_token_response(response $response): token;

    /**
     * @return token|null
     */
    abstract public function get_current_token(): ?token;

    /**
     * @return void
     */
    abstract public function invalidate_current_token(): void;
}