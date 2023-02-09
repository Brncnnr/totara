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

use coding_exception;
use moodle_url;

/**
 * A class that encapsulates an HTTP request.
 */
final class request {
    /**
     * @var moodle_url
     */
    private $url;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string[]
     */
    private $headers = [];

    /**
     * @var string|formdata|object|null
     */
    private $postdata = null;

    /**
     * Constructor.
     *
     * @param string|moodle_url $url
     * @param string $method
     * @param string|formdata|array|object|null $postdata
     * @param string[]|null $headers
     */
    public function __construct($url, string $method = method::GET, $postdata = null, ?array $headers = null) {
        $this->set_url($url)->set_method($method)->set_headers($headers ?? [])->set_post_data($postdata);
    }

    /**
     * Create a GET request.
     *
     * @param string|moodle_url $url
     * @param string[]|null $headers
     * @return self
     */
    public static function get($url, ?array $headers = null): self {
        return new self($url, method::GET, null, $headers);
    }

    /**
     * Create a HEAD request.
     *
     * @param string|moodle_url $url
     * @param string[]|null $headers
     * @return self
     */
    public static function head($url, ?array $headers = null): self {
        return new self($url, method::HEAD, null, $headers);
    }

    /**
     * Create a POST request.
     *
     * @param string|moodle_url $url
     * @param string|formdata|array|object $postdata
     * @param string[]|null $headers
     * @return self
     */
    public static function post($url, $postdata, ?array $headers = null): self {
        return new self($url, method::POST, $postdata, $headers);
    }

    /**
     * Create a PUT request.
     *
     * @param string|moodle_url $url
     * @param string|formdata|array|object $postdata
     * @param string[]|null $headers
     * @return self
     */
    public static function put($url, $postdata, ?array $headers = null): self {
        return new self($url, method::PUT, $postdata, $headers);
    }

    /**
     * Create a DELETE request.
     *
     * @param string|moodle_url $url
     * @param string[]|null $headers
     * @return self
     */
    public static function delete($url, ?array $headers = null): self {
        return new self($url, method::DELETE, null, $headers);
    }

    /**
     * Create a PATCH request.
     *
     * @param string|moodle_url $url
     * @param string|formdata|array|object $postdata
     * @param string[]|null $headers
     * @return self
     */
    public static function patch($url, $postdata, ?array $headers = null): self {
        return new self($url, method::PATCH, $postdata, $headers);
    }

    /**
     * @param string|moodle_url $url
     * @return self
     */
    public function set_url($url): self {
        $this->url = new moodle_url($url);
        return $this;
    }

    /**
     * @return string
     */
    public function get_url(): string {
        return $this->url->out(false);
    }

    /**
     * @param string $method an HTTP request method
     * @return self
     */
    public function set_method(string $method): self {
        if (($parsedmethod = method::try_parse($method)) === null) {
            throw new coding_exception("Unknown HTTP method: '{$method}'");
        }
        $this->method = $parsedmethod;
        return $this;
    }

    /**
     * @return string an HTTP request method
     */
    public function get_method(): string {
        return $this->method;
    }

    /**
     * @param string[] $headers array of [name => value]
     * @return self
     */
    public function set_headers(array $headers): self {
        foreach ($headers as $name => $value) {
            $this->set_header($name, $value);
        }
        return $this;
    }

    /**
     * Get a request header as array of ['Header1: Value1', 'Header2: Value2', ...]
     *
     * @return array
     */
    public function get_headers(): array {
        $headers = $this->headers;
        if (!isset($headers['Content-Type'])) {
            $type = $this->determine_content_type();
            if ($type) {
                $headers['Content-Type'] = $type;
            }
        }
        return array_map(function ($name, $value) {
            return "{$name}: {$value}";
        }, array_keys($headers), $headers);
    }

    /**
     * @param string $name
     * @param string $value
     * @return self
     */
    public function set_header(string $name, string $value): self {
        if (!preg_match('/^[a-zA-Z][a-zA-Z\-]*$/', $name)) {
            throw new coding_exception("Invalid header name is specified: '{$name}'");
        }
        if (preg_match('/[^\x20-\x7e]/', $value)) {
            throw new coding_exception("Non-ascii character in value: '{$value}'");
        }
        foreach ($this->headers as $header => $unused) {
            if (strcasecmp($header, $name) === 0) {
                unset($this->headers[$header]);
            }
        }
        // Normalise-The-Name-Like-This-Example.
        $name = preg_replace_callback('/(^[a-z]|(?<=-)[a-z])/', function ($matches) {
            return strtoupper($matches[0][0]);
        }, $name);
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @param string|array|object|formdata $data
     * @return self
     */
    public function set_post_data($data): self {
        if (is_object($data)) {
            $this->postdata = clone $data;
        } else {
            $this->postdata = $data;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function get_post_data(): string {
        if ($this->postdata instanceof formdata) {
            return $this->postdata->as_string();
        } else if (is_object($this->postdata) || is_array($this->postdata)) {
            return json_encode($this->postdata, JSON_UNESCAPED_SLASHES);
        } else {
            return (string)$this->postdata;
        }
    }

    /**
     * Guess the content type based on the post body.
     *
     * @return string|null
     */
    private function determine_content_type(): ?string {
        if ($this->postdata instanceof formdata) {
            return 'application/x-www-form-urlencoded; charset=utf-8';
        } else if (is_object($this->postdata) || is_array($this->postdata)) {
            // The charset is not defined for json. See RFC 8259 section 11.
            return 'application/json';
        } else {
            return null;
        }
    }
}
