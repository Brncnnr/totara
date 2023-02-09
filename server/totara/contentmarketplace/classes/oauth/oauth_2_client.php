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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\oauth;

use totara_contentmarketplace\token\token;
use totara_core\http\client;

class oauth_2_client {
    /**
     * @var oauth_2
     */
    private $oauth;

    /**
     * @var client
     */
    private $client;

    /**
     * @var int
     */
    private $time_now;

    /**
     * oauth_client constructor.
     * @param oauth_2  $oauth
     * @param client   $client
     * @param int|null $time_now
     */
    public function __construct(oauth_2 $oauth, client $client, ?int $time_now = null) {
        $this->client = $client;
        $this->oauth = $oauth;
        $this->time_now = $time_now ?? time();
    }

    /**
     * If the token is already existing in the system, we will get that token. Otherwise we are fetching
     * the provided server to get the token.
     *
     * proceed
     * @return token
     */
    public function request_token(): token {
        $token = $this->oauth->get_current_token();
        if (null !== $token && !$token->is_expired($this->time_now)) {
            return $token;
        }

        $request = $this->oauth->prepare_request();
        $response = $this->client->execute($request);

        $response->throw_if_error();
        return $this->oauth->process_token_response($response);
    }

    /**
     * @return token
     */
    public function refresh_token(): token {
        $token = $this->oauth->get_current_token();
        if (null !== $token) {
            $this->oauth->invalidate_current_token();
        }

        return $this->request_token();
    }

    /**
     * @return client
     */
    public function get_client(): client {
        return $this->client;
    }
}