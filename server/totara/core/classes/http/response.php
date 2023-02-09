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
 * @package totara_core
 */

namespace totara_core\http;

use stdClass;
use totara_core\http\exception\auth_exception;
use totara_core\http\exception\bad_format_exception;
use totara_core\http\exception\request_exception;

/**
 * A class that encapsulates an HTTP response.
 */
final class response {
    /**
     * @var string
     */
    private $body;

    /**
     * @var integer
     */
    private $http_code;

    /**
     * @var string
     */
    private $content_type;

    /**
     * @var string[]
     */
    private $response;

    /**
     * Constructor.
     *
     * @param string      $body
     * @param integer     $code
     * @param string[]    $response
     * @param string|null $contenttype
     */
    public function __construct(string $body, int $code, array $response, ?string $contenttype = null) {
        $this->body = $body;
        $this->http_code = $code;
        $this->content_type = $contenttype ?: ($response['Content-type'] ?? '');
        $this->response = $response;
    }

    /**
     * Wait for the request to be completed.
     */
    public function wait(): void {
        // Do nothing; asynchronous requests have not been supported yet.
    }

    /**
     * Return true if the status code is 2xx.
     *
     * @return boolean
     */
    public function is_ok(): bool {
        return response_code::is_successful_response($this->http_code);
    }

    /**
     * Return true if the response contains data.
     *
     * @return boolean
     */
    public function has_body(): bool {
        return $this->body !== '';
    }

    /**
     * Get the response data.
     *
     * @return string
     */
    public function get_body(): string {
        return $this->body;
    }

    /**
     * Parse the response data as JSON.
     *
     * @param boolean $assoc
     * @param boolean $electrify Set true to throw bad_format_exception if the response body is not json.
     * @return stdClass|array|null
     * @throws bad_format_exception
     */
    public function get_body_as_json(bool $assoc = false, bool $electrify = false) {
        if (!$this->has_body()) {
            if ($electrify) {
                throw new bad_format_exception('No data');
            }
            return null;
        }

        $json = @json_decode($this->body, $assoc, 512, JSON_BIGINT_AS_STRING);
        if ($json === null) {
            if ($electrify && json_last_error()) {
                throw new bad_format_exception(json_last_error_msg(), $this->body);
            }
            return null;
        }

        return $json;
    }

    /**
     * Get the array of the response headers.
     *
     * @return string[]
     */
    public function get_response_headers(): array {
        return $this->response;
    }

    /**
     * The the value of the response header by name.
     *
     * @param string $name
     * @return string|false
     */
    public function get_response_header(string $name) {
        foreach ($this->response as $header => $value) {
            if (strcasecmp($header, $name) === 0) {
                return $value;
            }
        }
        return false;
    }

    /**
     * Get the status code.
     *
     * @return int
     */
    public function get_http_code(): int {
        return $this->http_code;
    }

    /**
     * Get the content type.
     *
     * @return string
     */
    public function get_content_type(): string {
        return $this->content_type;
    }

    /**
     * Throw auth_exception or request_exception if fails.
     *
     * @throws auth_exception
     * @throws request_exception
     */
    public function throw_if_error(): void {
        if ($this->is_ok()) {
            return;
        }
        $message = "Request failed with {$this->get_http_code()}";

        if ($this->http_code === response_code::BAD_REQUEST ||
            $this->http_code === response_code::UNAUTHORIZED) {
            $message .= ', Authentication failed';
            throw new auth_exception($message, $this->get_body());
        }

        throw new request_exception($message, $this->get_body());
    }

    /**
     * Extract a human readable error message from the response body.
     *
     * @return string|null
     */
    public function try_get_error_message(): ?string {
        if ($this->is_ok()) {
            return '';
        }
        // The response body must be json at the moment.
        $json = $this->get_body_as_json();
        if ($json === null) {
            return null;
        }
        // 'error' must be supplied at the moment.
        if (!isset($json->error)) {
            return null;
        }
        // RFC 6749 : { "error" : "error code", "error_description" : "error message" }
        if (is_string($json->error)) {
            $message = $json->error;
            if (isset($json->error_description)) {
                $message = "{$message}: {$json->error_description}";
            }
            return $message;
        }
        // Bot API : { "error" : { "code" : "error code", "message": "error message" } }
        if (isset($json->error->message)) {
            $message = $json->error->message;
            if (isset($json->error->code)) {
                $message = "{$json->error->code}: {$message}";
            }
            return $message;
        }
        return null;
    }
}
