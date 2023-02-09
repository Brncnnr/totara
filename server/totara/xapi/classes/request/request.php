<?php
/**
 * This file is part of Totara Core
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
 * @package totara_xapi
 */
namespace totara_xapi\request;

use coding_exception;
use totara_core\http\util;

class request {
    /**
     * The hashmap header of the request. Where key is the header name, and value
     * is the value associated with the header name.
     *
     * @var array<string, string>
     */
    private $header_parameters;

    /**
     * The hashmap of global $_POST.
     * @var array<string, mixed>
     */
    private $post_parameters;

    /**
     * The hashmap of global $_GET.
     * @var array<string, mixed>
     */
    private $get_parameters;

    /**
     * The hashmap of global $_SERVER
     * @var array<string, mixed>
     */
    private $server_parameters;

    /**
     * The http content.
     * @var string|null
     */
    private $content;

    /**
     * @param array<string, mixed> $post_parameters
     * @param array<string, mixed> $get_parameters
     * @param array<string, mixed> $server_parameters
     * @param array<string, string> $header_parameters
     */
    public function __construct(
        array $post_parameters,
        array $get_parameters,
        array $server_parameters,
        array $header_parameters
    ) {
        $this->post_parameters = $post_parameters;
        $this->get_parameters = $get_parameters;
        $this->header_parameters = [];
        $this->server_parameters = [];

        // Change the header and server parameters into all upper cases. This will make sure that
        // we can predict the data hash-map and hence fetching data via key in these map would be
        // a lot easier.
        foreach ($header_parameters as $k => $v) {
            $this->header_parameters[strtoupper($k)] = $v;
        }

        foreach ($server_parameters as $k => $v) {
            $this->server_parameters[strtoupper($k)] = $v;
        }

        $this->content = null;
    }

    /**
     * @param string $content
     * @return void
     */
    public function set_content(string $content): void {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function get_content(): string {
        if (null === $this->content) {
            $this->content = file_get_contents("php://input");
        }

        return $this->content;
    }

    /**
     * @return array
     */
    public function get_content_as_decoded_json(): array {
        $content = $this->get_content();
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Get the parameter, which is not a cleaned parameter by default.
     * Ideally the cleaning process should be done separately from
     * fetching the value. However, the parameter $param is in place to
     * allow us cleaning the value if desire to.
     * Only when value of parameter $param presents.
     *
     * @param string $field
     * @param null|mixed $default
     * @param string|null $param
     *
     * @return mixed|null
     */
    public function get_parameter(string $field, $default = null, ?string $param = null) {
        $value = $default;

        if (array_key_exists($field, $this->get_parameters)) {
            $value = $this->get_parameters[$field];
        } else if (array_key_exists($field, $this->post_parameters)) {
            $value = $this->post_parameters[$field];
        }

        if (null !== $param) {
            $value = clean_param($value, $param);
        }

        return $value;
    }

    /**
     * Get the parameter as required parameter.
     *
     * @param string $field
     * @param string|null $param
     *
     * @return mixed
     */
    public function get_required_parameter(string $field, ?string $param = null) {
        $this->require_parameter($field);
        return $this->get_parameter($field, null, $param);
    }

    /**
     * @param string $field
     * @return bool
     */
    public function has_parameter(string $field): bool {
        return array_key_exists($field, $this->get_parameters) || array_key_exists($field, $this->post_parameters);
    }

    /**
     * Throw an exception, if the given $field is not provided within the
     * parameter list.
     *
     * @param string $field
     * @return void
     */
    public function require_parameter(string $field): void {
        if ($this->has_parameter($field)) {
            return;
        }

        throw new coding_exception("The field {$field} is required, but missing from the request");
    }

    /**
     * Returns the value of the request's header, or null if it is not present in the request.
     *
     * @param string $field
     * @return string|null
     */
    public function get_header(string $field): ?string {
        // Upper case the field name for header, since our Hashmap is all in uppercase.
        $field = strtoupper($field);
        return $this->header_parameters[$field] ?? null;
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    public function server(string $field) {
        $field = strtoupper($field);
        return $this->server_parameters[$field] ?? null;
    }

    /**
     * Returns all the parameters from {@see request::$get_parameters}
     *
     * @return array<string, mixed>
     */
    public function get_get_parameters(): array {
        return $this->get_parameters;
    }

    /**
     * Returns all the parameters from {@see request::$post_parameters}
     *
     * @return array<string, mixed>
     */
    public function get_post_parameters(): array {
        return $this->post_parameters;
    }

    /**
     * Returns all the parameters from {@see request::$header_parameters}
     *
     * @return array<string, mixed>
     */
    public function get_header_parameters(): array {
        return $this->header_parameters;
    }

    /**
     * Returns all the parameters from {@see request::$server_parameters}
     *
     * @return array<string, mixed>
     */
    public function get_server_parameters(): array {
        return $this->server_parameters;
    }

    /**
     * @param array<string, mixed> $get_parameters   Global $_GET - passing the value to this argument, in order
     *                                              to override the global $_GET
     *
     * @param array<string, mixed> $post_parameters  Global $_POST - passing the value to this argument, in order
     *                                              to override the global $_POST.
     *
     * @param array<string, mixed> $servers         Global $_SERVER - passing the value to this argument, in order to
     *                                              to override the global $_SERVER
     *
     * @param array<string, string> $headers        The array of headers, which is mostly taken from global $_SERVER.
     *
     * @return request
     */
    public static function create_from_global(
        array $get_parameters = [],
        array $post_parameters = [],
        array $headers = [],
        array $servers = []
    ): request {
        global $_GET, $_POST, $_SERVER;
        if (empty($post_parameters)) {
            $post_parameters = $_POST;
        }

        if (empty($get_parameters)) {
            $get_parameters = $_GET;
        }

        if (empty($servers)) {
            $servers = $_SERVER;
        }

        if (empty($headers)) {
            $headers = util::get_request_headers();
            $headers = $headers ?: [];
        }

        return new self($post_parameters, $get_parameters, $servers, $headers);
    }
}