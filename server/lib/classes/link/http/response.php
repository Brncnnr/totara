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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\link\http;

final class response {
    /**
     * @var int
     */
    public const HTTP_OK = 200;

    /**
     * @var int
     */
    public const HTTP_MULTIPLE_CHOICES = 300;

    /**
     * @var \DOMDocument
     */
    private $html;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var int|null
     */
    private $statuscode;

    /**
     * response constructor.
     */
    private function __construct() {
        $this->headers = [];
        $this->statuscode = null;
        $this->html = new \DOMDocument();
    }

    /**
     * @param string $header
     * @return void
     */
    private function parse_header(string $header): void {
        if (empty($header)) {
            debugging("Header is empty", DEBUG_DEVELOPER);
            return;
        }

        if (!isset($this->headers)) {
            return;
        }

        // There could be mulitple header, which is because from redirection
        $headers = explode("\r\n\r\n", $header);
        $headers = array_filter(
            $headers,
            function (string $header): bool {
                return !empty($header);
            }
        );

        // The actual header of the content is always at the very end of the array of headers.
        // Event true for case of non-redirection.
        $header = end($headers);
        $parts = explode("\r\n", $header);

        foreach ($parts as $i => $part) {
            if (false === stripos($part, ":") && (0 === $i)) {
                // First line index should always be the HTTP code
                $codes = explode(" ", $part);

                foreach ($codes as $code) {
                    if (is_numeric($code)) {
                        // Found status code
                        $this->statuscode = $code;
                        break;
                    }
                }

                continue;
            }

            [$k, $v] = explode(":", $part, 2);
            $this->headers[strtolower($k)] = $v;
        }
    }

    /**
     * @param string $body
     * @param array $headers
     * @param int $code
     *
     * @return response
     */
    public static function create_from_params(string $body, array $headers, int $code = response::HTTP_OK): response {
        if (empty($headers) || empty($body)) {
            throw new \coding_exception("Invalid parameters");
        }

        $response = new static();
        $response->statuscode = $code;

        foreach ($headers as $k => $v) {
            $response->headers[strtolower($k)] = $v;
        }

        if (!$response->is_html() || !$response->is_ok()) {
            // No point to run the document loaded like below.
            return $response;
        }

        $result = $response->html->loadHTML($body);
        if (!$result) {
            throw new \coding_exception("Invalid html body content", DEBUG_DEVELOPER);
        }

        return $response;
    }

    /**
     * @param string $body
     * @param string $header
     *
     * @return response
     */
    public static function create(string $body, string $header): response {
        $response = new static();
        $response->parse_header($header);

        if (!$response->is_html() || !$response->is_ok()) {
            // No point to run the document loaded like below.
            return $response;
        }

        // A hack to make \DOMDocument shutup about invalid node. At least this is better than '@'
        $silent = function_exists('libxml_use_internal_errors');
        if ($silent) {
            libxml_use_internal_errors(true);
        }

        $result = $response->html->loadHTML($body);
        if (!$result) {
            throw new \coding_exception("Invalid html body content", DEBUG_DEVELOPER);
        }

        if ($silent) {
            libxml_use_internal_errors(false);
        }

        return $response;
    }

    /**
     * @return bool
     */
    public function is_html(): bool {
        if (!array_key_exists('content-type', $this->headers)) {
            debugging("No 'content-type' property appears in the header", DEBUG_DEVELOPER);
            return false;
        }

        $value = $this->headers['content-type'];
        return (false !== stripos($value, "text/html"));
    }

    /**
     * @return int|null
     */
    public function get_statuscode(): ?int {
        if (null === $this->statuscode) {
            debugging("The status code was never populated", DEBUG_DEVELOPER);
        }

        return $this->statuscode;
    }

    /**
     * @return bool
     */
    public function is_ok(): bool {
        $statuscode = $this->get_statuscode();
        if (null === $statuscode) {
            return false;
        }

        // Anything that out of range 200 is bad.
        return static::HTTP_MULTIPLE_CHOICES >= $this->statuscode || static::HTTP_OK > $this->statuscode;
    }

    /**
     * @return \DOMDocument
     */
    public function get_body(): \DOMDocument {
        return $this->html;
    }
}