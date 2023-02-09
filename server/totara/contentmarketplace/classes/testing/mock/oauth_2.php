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
namespace totara_contentmarketplace\testing\mock;

use totara_contentmarketplace\oauth\oauth_2 as base;
use totara_contentmarketplace\token\token;
use totara_core\http\request;
use totara_core\http\response;

class oauth_2 extends base {
    /**
     * @var string
     */
    public const TOKEN_KEY = 'token';

    /**
     * @var string
     */
    public const EXPIRY_KEY = 'expiry';

    /**
     * @var token|null
     */
    private $token;

    /**
     * oauth_2 constructor.
     * @param token|null $token
     */
    public function __construct(?token $token = null) {
        $this->token = $token;
    }

    /**
     * @return request
     */
    public function prepare_request(): request {
        return request::post("http://example.com", [], []);
    }

    /**
     * We are only expecting the json response.
     * @param response $response
     * @return token
     */
    public function process_token_response(response $response): token {
        $json = $response->get_body_as_json(true, true);
        $this->token = new token(
            $json[self::TOKEN_KEY],
            $json[self::EXPIRY_KEY] ?? null
        );

        return $this->token;
    }

    /**
     * @return token|null
     */
    public function get_current_token(): ?token {
        return $this->token;
    }

    /**
     * @return void
     */
    public function invalidate_current_token(): void {
        $this->token = null;
    }
}