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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\auth\token;

use stdClass;
use totara_core\http\exception\auth_exception;
use totara_core\http\exception\request_exception;
use totara_core\http\formdata;
use totara_core\http\request;
use totara_core\http\response;
use totara_msteams\botfw\auth\jwt;
use totara_msteams\botfw\context;
use totara_msteams\botfw\exception\jwt_exception;

/**
 * A class to manage the authorization token of the Totara bot.
 */
final class bot_token extends token {
    private const LOGIN_URL = 'https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token';
    private const DEFAULT_EXPIRY = 3480; // 58 mins
    private const KEY = '@access_token';

    /**
     * Give it a very little clock-skew.
     */
    private const CLOCK_SKEW = 30; // 30 seconds

    /** @var integer */
    private $timeexpiry;

    /**
     * Constructor.
     *
     * @param jwt $token
     * @param integer $timeexpiry
     */
    protected function __construct(jwt $token, int $timeexpiry) {
        parent::__construct($token);
        $this->timeexpiry = $timeexpiry;
    }

    /**
     * Get the access token as string.
     *
     * @return string
     */
    public function get(): string {
        return $this->get_jwt()->as_string();
    }

    /**
     * Save the current state back to the database.
     *
     * @param context $context
     */
    private function store(context $context): void {
        $accesstoken = new stdClass();
        /** @var \totara_msteams\botfw\internal\bot_access_token $accesstoken */
        $accesstoken->token = $this->get_jwt()->as_string();
        $accesstoken->timeexpiry = $this->timeexpiry;
        $context->get_storage()->bot_store(self::KEY, $accesstoken);
    }

    /**
     * Load the stored access token.
     *
     * @param context $context
     * @return bot_token|null A new instance, or null if the token is expired or invalid
     */
    public static function try_load_cache(context $context): ?bot_token {
        $accesstoken = $context->get_storage()->bot_load(self::KEY);
        if (!$accesstoken) {
            return null;
        }
        /** @var \totara_msteams\botfw\internal\bot_access_token $accesstoken */
        // See if the stored token expires in 30 seconds now.
        if (time() >= $accesstoken->timeexpiry - self::CLOCK_SKEW) {
            return null;
        }
        if (empty($accesstoken->token)) {
            return null;
        }
        if (($jwt = jwt::try_load($accesstoken->token)) === null) {
            return null;
        }
        return new self($jwt, $accesstoken->timeexpiry);
    }

    /**
     * Refresh an access token and return it.
     *
     * @param context $context
     * @return bot_token
     * @throws auth_exception
     * @throws jwt_exception
     * @throws request_exception
     */
    public static function refresh(context $context): bot_token {
        global $CFG;
        $time = time();

        $request = request::post(self::LOGIN_URL, new formdata([
            'client_id' => $context->get_storage()->get_app_id(),
            'client_secret' => $context->get_storage()->get_app_secret(),
            'grant_type' => 'client_credentials',
            'scope' => 'https://api.botframework.com/.default',
        ]));

        $response = $context->get_client()->execute($request);
        if (!$response->is_ok()) {
            self::handle_error($response);
        }

        $json = $response->get_body_as_json();
        if ($json === null || empty($json->access_token) || empty($json->token_type) || strcasecmp($json->token_type, 'bearer')) {
            throw new auth_exception('Invalid response', $response->get_body());
        }

        // Default to 58 minute.
        $expires_in_default = ($CFG->totara_msteams_token_expire ?? 0) ?: self::DEFAULT_EXPIRY;
        $expires_in = $json->expires_in ?? $expires_in_default;
        if ($expires_in <= self::CLOCK_SKEW) {
            // Reject an access token that expires within 30 seconds.
            throw new auth_exception('Invalid expiration', $response->get_body());
        }
        $timeexpiry = $time + $expires_in;

        $jwt = jwt::load($json->access_token);
        $self = new self($jwt, $timeexpiry);

        // Save the current access token.
        $self->store($context);

        // Return the access token.
        return $self;
    }

    /**
     * Handle an authorisation error.
     * Call this function only if $response->is_ok() returns false, so that it always throws an exception.
     *
     * @param response $response
     * @throws auth_exception
     * @throws request_exception
     */
    private static function handle_error(response $response): void {
        $statuscode = $response->get_http_code();
        if ($statuscode === 400 || $statuscode === 401) {
            $message = "Authorization error: {$statuscode}";
            $debugmessage = '';
            if (($json = $response->get_body_as_json()) !== null && !empty($json->error)) {
                $message .= ', '.$json->error;
                if (isset($json->error_description)) {
                    $message .= ', '.$json->error_description;
                }
                if (isset($json->error_uri)) {
                    $debugmessage = $json->error_uri;
                }
            } else if ($response->has_body()) {
                $debugmessage = $response->get_body();
            }
            throw new auth_exception($message, $debugmessage);
        } else {
            $response->throw_if_error();
        }
    }
}
