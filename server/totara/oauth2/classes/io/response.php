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
 * @package totara_oauth2
 */
namespace totara_oauth2\io;

use Psr\Http\Message\StreamInterface;
use totara_oauth2\facade\response_interface;
use Nyholm\Psr7\Response as library_response;

/**
 * A wrapper of OAuth2 Library response
 */
class response implements response_interface {
// phpcs:disable Totara.NamingConventions
    /**
     * @var library_response
     */
    private $response;

    /**
     * @param library_response|null $response
     */
    public function __construct(?library_response $response = null) {
        $this->response = $response ?? new library_response();
    }

    /**
     * @return string
     */
    public function getProtocolVersion() {
        return $this->response->getProtocolVersion();
    }

    /**
     * @param string $version
     * @return response
     */
    public function withProtocolVersion($version) {
        $library_response = $this->response->withProtocolVersion($version);
        return new self($library_response);
    }

    public function getHeaders() {
        return $this->response->getHeaders();
    }

    /**
     * @param string $name
     * @return array|string[]
     */
    public function getHeader($name) {
        return $this->response->getHeader($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name) {
        return $this->response->getHeaderLine($name);
    }

    /**
     * @param string          $name
     * @param string|string[] $value
     *
     * @return response
     */
    public function withHeader($name, $value) {
        $library_response = $this->response->withHeader($name, $value);
        return new self($library_response);
    }

    /**
     * @param string          $name
     * @param string|string[] $value
     * @return response
     */
    public function withAddedHeader($name, $value) {
        $library_response = $this->response->withAddedHeader($name, $value);
        return new self($library_response);
    }

    /**
     * @param string $name
     * @return response
     */
    public function withoutHeader($name) {
        $library_response = $this->response->withoutHeader($name);
        return new self($library_response);
    }

    /**
     * @return StreamInterface
     */
    public function getBody() {
        return $this->response->getBody();
    }

    /**
     * @param StreamInterface $body
     * @return response
     */
    public function withBody(StreamInterface $body) {
        $library_response = $this->response->withBody($body);
        return new self($library_response);
    }

    /**
     * @return int
     */
    public function getStatusCode() {
        return $this->response->getStatusCode();
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     * @return response
     */
    public function withStatus($code, $reasonPhrase = '') {
        $library_response = $this->response->withStatus($code, $reasonPhrase);
        return new self($library_response);
    }

    /**
     * @return string
     */
    public function getReasonPhrase() {
        return $this->response->getReasonPhrase();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name) {
        return $this->response->hasHeader($name);
    }
// phpcs:enable
}