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
use Psr\Http\Message\UriInterface;
use totara_oauth2\facade\request_interface;
use Nyholm\Psr7\ServerRequest as library_request;
use totara_core\http\util;

/**
 * A wrapper request.
 */
class request implements request_interface {
// phpcs:disable Totara.NamingConventions
    /**
     * @var library_request
     */
    private $request;

    /**
     * @param library_request $request
     */
    public function __construct(library_request $request) {
        $this->request = $request;
    }

    /**
     * @param string|null $method
     * @param string|null $uri
     * @param array       $query_parameters
     * @param array       $post_parameters
     * @param array       $headers
     * @param array       $server_parameters
     * @return request
     */
    public static function create_from_global(
        array $query_parameters = [],
        array $post_parameters = [],
        array $headers = [],
        array $server_parameters = [],
        ?string $method = null,
        ?string $uri = null
    ): request {
        global $_POST, $_GET, $_SERVER;

        if (empty($server_parameters)) {
            $server_parameters = $_SERVER;
        }

        if (empty($query_parameters)) {
            // Use default global $_GET
            $query_parameters = $_GET;
        }

        if (empty($post_parameters)) {
            // Use default global $_POST
            $post_parameters = $_POST;
        }

        if (empty($method)) {
            // default to get if $_SERVER["REQUEST_METHOD"] does not exist,
            // but it should not be a case
            $method = $server_parameters["REQUEST_METHOD"] ?? "GET";
        }

        if (empty($uri)) {
            $uri = $server_parameters["REQUEST_URI"] ?? "/";
        }

        if (empty($headers)) {
            $headers = util::get_request_headers()?: [];
        }

        $library_request = new library_request($method, $uri, $headers);
        $library_request = $library_request->withQueryParams($query_parameters)->withParsedBody($post_parameters);

        return new self($library_request);
    }

    /**
     * @return string
     */
    public function getProtocolVersion(): string {
        return $this->request->getProtocolVersion();
    }

    /**
     * @param string $version
     * @return request
     */
    public function withProtocolVersion($version) {
        $library_request = $this->request->withProtocolVersion($version);
        return new self($library_request);
    }

    /**
     * @return array
     */
    public function getHeaders() {
        return $this->request->getHeaders();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name) {
        return $this->request->hasHeader($name);
    }

    /**
     * @param string $name
     * @return string[]|string
     */
    public function getHeader($name) {
        return $this->request->getHeader($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name) {
        return $this->request->getHeaderLine($name);
    }

    /**
     * @param string          $name
     * @param string|string[] $value
     *
     * @return request
     */
    public function withHeader($name, $value) {
        $library_request = $this->request->withHeader($name, $value);
        return new self($library_request);
    }

    /**
     * @param string          $name
     * @param string|string[] $value
     *
     * @return request
     */
    public function withAddedHeader($name, $value) {
        $library_request = $this->request->withAddedHeader($name, $value);
        return new self($library_request);
    }

    /**
     * @param string $name
     * @return request
     */
    public function withoutHeader($name) {
        $library_request = $this->request->withoutHeader($name);
        return new self($library_request);
    }

    /**
     * @return StreamInterface
     */
    public function getBody() {
        return $this->request->getBody();
    }

    /**
     * @param StreamInterface $body
     * @return request
     */
    public function withBody(StreamInterface $body) {
        $library_request = $this->request->withBody($body);
        return new self($library_request);
    }

    /**
     * @return string
     */
    public function getRequestTarget() {
        return $this->request->getRequestTarget();
    }

    /**
     * @param mixed $requestTarget
     * @return request
     */
    public function withRequestTarget($requestTarget) {
        $library_request = $this->request->withRequestTarget($requestTarget);
        return new self($library_request);
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->request->getMethod();
    }

    /**
     * @param string $method
     * @return request
     */
    public function withMethod($method) {
        $library_request = $this->request->withMethod($method);
        return new self($library_request);
    }

    /**
     * @return UriInterface
     */
    public function getUri() {
        return $this->request->getUri();
    }

    /**
     * @param UriInterface $uri
     * @param false        $preserveHost
     * @return request
     */
    public function withUri(UriInterface $uri, $preserveHost = false) {
        $library_request = $this->request->withUri($uri, $preserveHost);
        return new self($library_request);
    }

    /**
     * @return array
     */
    public function getServerParams() {
        return $this->request->getServerParams();
    }

    /**
     * @return array
     */
    public function getCookieParams() {
        return $this->request->getCookieParams();
    }

    /**
     * @param array $cookies
     * @return request
     */
    public function withCookieParams(array $cookies) {
        return new self(
            $this->request->withCookieParams($cookies)
        );
    }

    /**
     * @return array
     */
    public function getQueryParams() {
        return $this->request->getQueryParams();
    }

    /**
     * @param array $query
     * @return request
     */
    public function withQueryParams(array $query) {
        return new self(
            $this->request->withQueryParams($query)
        );
    }

    /**
     * @return array|\Psr\Http\Message\UploadedFileInterface[]
     */
    public function getUploadedFiles() {
        return $this->request->getUploadedFiles();
    }

    /**
     * @param array $uploadedFiles
     * @return request
     */
    public function withUploadedFiles(array $uploadedFiles) {
        return new self(
            $this->request->withUploadedFiles($uploadedFiles)
        );
    }

    /**
     * @return array|object|null
     */
    public function getParsedBody() {
        return $this->request->getParsedBody();
    }

    /**
     * @param array|object|null $data
     * @return request
     */
    public function withParsedBody($data) {
        $library_request = $this->request->withParsedBody($data);
        return new self($library_request);
    }

    /**
     * @return array
     */
    public function getAttributes() {
        return $this->request->getAttributes();
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @return mixed|null
     */
    public function getAttribute($name, $default = null) {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return request
     */
    public function withAttribute($name, $value) {
        $library_request = $this->request->withAttribute($name, $value);
        return new self($library_request);
    }

    /**
     * @param string $name
     * @return request
     */
    public function withoutAttribute($name) {
        $library_request = $this->request->withoutAttribute($name);
        return new self($library_request);
    }
// phpcs:enable
}