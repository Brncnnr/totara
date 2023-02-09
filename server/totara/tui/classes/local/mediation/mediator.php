<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

namespace totara_tui\local\mediation;

use totara_core\path;

defined('MOODLE_INTERNAL') || die();

/**
 * Mediator class
 *
 * Responsible for mediating content back to the client.
 * This class sets heads, and outputs content.
 */
abstract class mediator {

    /**
     * The etag for the content being delivered.
     * @var string
     */
    private $etag;

    /**
     * Mediator constructor
     * @param string $etag The etag for the content being delivered.
     */
    final public function __construct(string $etag) {
        $this->etag = $etag;
    }

    /**
     * Updates the etag for the content being delivered.
     * @param string $etag
     */
    final public function update_etag(string $etag) {
        $this->etag = $etag;
    }

    /**
     * Returns the etag for the content being delivered.
     * @return string
     */
    final public function get_etag(): string {
        return $this->etag;
    }

    /**
     * Checks if the provided header appears in the "If-None-Match" header
     *
     * https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/If-None-Match
     */
    final public function compare_if_none_match_etag(): bool {
        if (!isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return false;
        }
        // There can be multiple etags provided :D
        $tags = array_map(
            function ($tag) {
                $tag = trim($tag, " \t\n\r\0\x0B\"");
                if (substr($tag, 0, 2) === 'W/') {
                    $tag = substr($tag, 2);
                }
                return $tag;
            },
            explode(',', $_SERVER['HTTP_IF_NONE_MATCH'])
        );
        return (in_array($this->etag, $tags));
    }

    /**
     * Returns the mimetype that this mediator returns.
     * @return string
     */
    abstract protected function get_mimetype(): string;

    /**
     * Returns the cache lifetime for this mediator.
     *
     * Can be overridden by extending classes to provide an alternative cache lifetime if desired.
     *
     * @return int
     */
    protected function cache_lifetime(): int {
        // 7 days only, we'll use etags to extend as needed.
        return 60 * 60 * 24 * 7;
    }

    /**
     * Adds standard headers to all deliveries.
     */
    protected function standard_headers() {
        if (defined('TUI_RESOLUTION_START') && (!defined('PHPUNIUT_TEST') || !PHPUNIT_TEST)) {
            // Don't prefix with X- see https://tools.ietf.org/html/rfc6648
            // Specifically cannot get here in unit tests.
            // @codeCoverageIgnoreStart
            self::header('Totara-Tui-resolution-time: ' . (microtime(true) - TUI_RESOLUTION_START));
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Returns the filename that should be used in the content disposition header.
     *
     * Defaults to the static class name, with .php appended.
     * Can be overridden to return a more specific name.
     *
     * @return string|null
     */
    protected function get_content_disposition_filename(): ?string {
        $class = static::class;
        if (strrpos($class, '\\') !== false) {
            $bits = explode('\\', $class);
            array_pop($bits); // Throw this away, it'll be "mediator"
            $class = array_pop($bits);
        }
        return substr($class, strrpos($class, '\\')) . '.php';
    }

    /**
     * Returns the charset to use in the content type header.
     *
     * If null is returned then no charset is added to the content type header.
     *
     * @return string|null
     */
    protected function get_content_type_charset(): ?string {
        return 'utf-8';
    }

    /**
     * Returns true if xsendfile can be used on the cached files.
     *
     * @return bool
     */
    protected function can_use_xsendfile_on_cached_files(): bool {
        return true;
    }

    /**
     *
     */
    public static function send_not_found() {
        self::header('HTTP/1.0 404 not found');
        self::exit();
    }

    /**
     * Sends the given content, and ensures it is not cached.
     * @param string $content
     */
    final public function send_uncached(string $content) {
        $this->header_etag();
        $this->header_content_disposition_inline();
        $this->header_do_not_cache();
        $this->header_accept_types();
        $this->header_content_type();
        $this->standard_headers();
        echo $content;
        self::exit();
    }

    /**
     * Sends the given content, and ensures that it is cached.
     * @param string $content
     */
    final public function send_cached(string $content) {
        $this->header_etag();
        $this->header_content_disposition_inline();
        $this->header_cache(time());
        $this->header_accept_types();
        $this->header_content_type();
        if (!\min_enable_zlib_compression()) {
            $this->header_content_length(strlen($content));
        }
        $this->header_vary_accept_encoding();
        $this->standard_headers();
        echo $content;
        self::exit();
    }

    /**
     * Sends the given file, and ensures that it is cached.
     * @param path $absolutefilepath
     */
    final public function send_cached_file(path $absolutefilepath) {
        $absolutefilepath = $absolutefilepath->out(true);
        $this->header_etag();
        $this->header_content_disposition_inline();
        $this->header_cache(filemtime($absolutefilepath));
        $this->header_accept_types();
        $this->header_content_type();
        if ($this->can_use_xsendfile_on_cached_files()) {
            require_once(__DIR__ . '/../../../../../lib/xsendfilelib.php');
            if (\xsendfile($absolutefilepath)) {
                self::exit();
            }
        }
        if (!\min_enable_zlib_compression()) {
            $this->header_content_length(filesize($absolutefilepath));
        }
        $this->header_vary_accept_encoding();
        $this->standard_headers();
        readfile($absolutefilepath);
        self::exit();
    }

    /**
     * Sends headers to inform the client that the content is unmodified.
     *
     * Please note that Firefox and Chrome will strip unexpected headers on 304 responses.
     * If you get here looking for pragma, or Totara headers that is why.
     */
    final public function send_unmodified_from_cache() {
        self::header("HTTP/1.1 304 Not Modified");
        $this->header_etag();
        $this->header_do_not_cache();
        $this->standard_headers();
        self::exit();
    }

    /**
     * Sets the given header.
     *
     * This method when called during PHPUnit triggers a debugging notice with the header, instead of setting the header.
     *
     * @param string $content
     */
    private static function header(string $content) {
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            debugging('Header: ' . $content, DEBUG_DEVELOPER);
            return;
        }
        // PHPUnit tests take the path above, enabling the testing of headers.
        // @codeCoverageIgnoreStart
        header($content);
        // @codeCoverageIgnoreEnd
    }

    /**
     * Exits the current script. For internal use only.
     * During PHPUnit this method calls debugging and returns.
     */
    private static function exit() {
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            debugging('Exiting', DEBUG_DEVELOPER);
            return;
        }
        // PHPUnit tests take the path above, enabling the testing of headers.
        // @codeCoverageIgnoreStart
        exit;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Adds the etag header.
     */
    private function header_etag() {
        self::header('Etag: "' . $this->etag . '"');
    }

    /**
     * Adds the content disposition inline header.
     */
    private function header_content_disposition_inline() {
        $filename = $this->get_content_disposition_filename();
        if ($filename !== null && preg_match('#^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*$#', $filename)) {
            self::header('Content-Disposition: inline; filename="'.$filename.'"');
        } else {
            self::header('Content-Disposition: inline;');
        }
    }

    /**
     * Adds the do not cache headers.
     */
    private function header_do_not_cache() {
        $datetime = gmdate('D, d M Y H:i:s', time());
        self::header('Date: '. $datetime .' GMT'); // Inform the client, and everything in between the server time. Used for date calculations.
        self::header('Last-Modified: ' .$datetime . ' GMT');
        self::header('Expires: ' . $datetime . ' GMT');
        self::header('Cache-Control: no-cache'); // HTTP 1.1
        self::header('Pragma: no-cache'); // HTTP 1.0 same as 'Cache-Control: no-cache' above but for older clients.
    }

    /**
     * Adds the accept ranges header.
     */
    private function header_accept_types() {
        self::header('Accept-Ranges: none');
    }

    /**
     * Adds the content type header.
     */
    private function header_content_type() {
        $mimetype = $this->get_mimetype();
        $charset = $this->get_content_type_charset();
        if ($charset !== null) {
            self::header('Content-Type: ' . $mimetype . ';charset=' . $charset);
        } else {
            self::header('Content-Type: ' . $mimetype);
        }
        self::header('X-Content-Type-Options: nosniff');
    }

    /**
     * Adds the headers to cache this mediated content.
     * @param int $timestamp_lastmodified
     */
    private function header_cache(int $timestamp_lastmodified) {
        $lifetime = $this->cache_lifetime();
        self::header('Date: '. gmdate('D, d M Y H:i:s', time()) .' GMT'); // Inform the client, and everything in between the server time. Used for date calculations.
        self::header('Last-Modified: '. gmdate('D, d M Y H:i:s', $timestamp_lastmodified) .' GMT');
        self::header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
        self::header('Pragma: ');
        self::header('Cache-Control: public, max-age='.$lifetime.', immutable');
    }

    /**
     * Ass the content length header.
     * @param int $size
     */
    private function header_content_length(int $size) {
        self::header('Content-Length: '.$size);
    }

    /**
     * Sets the vary header to accept-encoding.
     */
    private function header_vary_accept_encoding() {
        self::header('Vary: Accept-Encoding');
    }
}