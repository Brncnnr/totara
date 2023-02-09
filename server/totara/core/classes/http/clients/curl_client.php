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

namespace totara_core\http\clients;

use coding_exception;
use curl;
use totara_core\http\client;
use totara_core\http\method;
use totara_core\http\request;
use totara_core\http\response;

/**
 * A RESTful HTTP client that uses cURL.
 */
class curl_client implements client {
    /** @var curl */
    private $curl;

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $this->curl = new curl();
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    private function set_option(string $name, $value): self {
        $this->curl->setopt([$name => $value]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set_connect_timeout(int $timeout): client {
        $this->set_option('CURLOPT_CONNECTTIMEOUT', $timeout);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set_timeout(int $timeout): client {
        $this->set_option('CURLOPT_TIMEOUT', $timeout);
        return $this;
    }

    /**
     * Append the 'Content-Length' header if not present.
     * @param array $headers
     * @param integer $length
     */
    private static function add_content_length(array &$headers, int $length): void {
        foreach ($headers as $i => $header) {
            if (strncasecmp($header, 'Content-Length:', 15) === 0) {
                return;
            }
        }
        $headers[] = 'Content-Length: ' . $length;
    }

    /**
     * @inheritDoc
     */
    public function execute(request $request): response {
        $postdata = $request->get_post_data();
        $headers = $request->get_headers();
        self::add_content_length($headers, strlen($postdata));
        $this->set_option('CURLOPT_RETURNTRANSFER', 1);
        $this->curl->resetHeader();
        $this->curl->setHeader($headers);
        $url = $request->get_url();

        switch ($request->get_method()) {
            case method::GET:
                $ret = $this->curl->get($url);
                break;
            case method::HEAD:
                $ret = $this->curl->head($url);
                break;
            case method::POST:
                $ret = $this->curl->post($url, $postdata);
                break;
            case method::PUT:
                $ret = $this->curl->put_data($url, $postdata);
                break;
            case method::DELETE:
                $ret = $this->curl->delete($url, [], ['CURLOPT_USERPWD' => '']);
                break;
            case method::PATCH:
                $ret = $this->curl->patch($url, $postdata);
                break;
            default:
                throw new coding_exception("Unsupported method: '{$request->get_method()}'");
        }

        return new response($ret, $this->curl->info['http_code'], $this->curl->response, $this->curl->info['content_type'] ?? null);
    }
}
