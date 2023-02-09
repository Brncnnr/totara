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

use totara_core\path;
use totara_tui\local\mediation\mediator;

defined('MOODLE_INTERNAL') || die();

class totara_tui_local_mediation_mediator_testcase extends advanced_testcase {

    public function test_etag() {
        $instance = $this->get_mock_mediator_instance();
        self::assertSame('etag_test', $instance->get_etag());
        $instance->update_etag('test_test');
        self::assertSame('test_test', $instance->get_etag());
    }

    public function test_send_not_found() {
        $this->get_mock_mediator_instance()->send_not_found();
        $actual = $this->getDebuggingMessages();
        $expected = [
            'Header: HTTP/1.0 404 not found',
            'Exiting'
        ];
        self::assertSame($expected, self::strip_debugging_messages($actual));
        $this->resetDebugging();
    }

    public function test_send_unmodified_from_cache() {
        $this->get_mock_mediator_instance()->send_unmodified_from_cache();
        $actual = $this->getDebuggingMessages();
        $this->resetDebugging();

        $expected = [
            'Header: HTTP/1.1 304 Not Modified',
            'Header: Etag: "etag_test"',
            'Header: Date: ' . gmdate('M Y', time()),
            'Header: Last-Modified: ' . gmdate('M Y', time()),
            'Header: Expires: ' . gmdate('M Y', time()),
            'Header: Cache-Control: no-cache',
            'Header: Pragma: no-cache',
            'Exiting'
        ];
        self::assertSame($expected, self::strip_debugging_messages($actual));
    }

    public function test_send_uncached() {
        $instance = $this->get_mock_mediator_instance();
        ob_start();
        $instance->send_uncached('test content');
        $output = ob_get_contents();
        ob_end_clean();
        self::assertSame('test content', $output);
        $actual = $this->getDebuggingMessages();
        $this->resetDebugging();

        $expected = [
            'Header: Etag: "etag_test"',
            'Header: Content-Disposition: inline; filename="'.get_class($instance).'.php"',
            'Header: Date: ' . gmdate('M Y', time()),
            'Header: Last-Modified: ' . gmdate('M Y', time()),
            'Header: Expires: ' . gmdate('M Y', time()),
            'Header: Cache-Control: no-cache',
            'Header: Pragma: no-cache',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/phpunit;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
            'Exiting'
        ];
        self::assertSame($expected, self::strip_debugging_messages($actual));
    }

    public function test_send_cached() {
        global $CFG;
        require_once($CFG->libdir . '/configonlylib.php');
        $instance = $this->get_mock_mediator_instance();
        ob_start();
        $instance->send_cached('test content');
        $output = ob_get_contents();
        ob_end_clean();
        self::assertSame('test content', $output);
        $actual = $this->getDebuggingMessages();
        $this->resetDebugging();

        $expected = [
            'Header: Etag: "etag_test"',
            'Header: Content-Disposition: inline; filename="'.get_class($instance).'.php"',
            'Header: Date: ' . gmdate('M Y', time()),
            'Header: Last-Modified: ' . gmdate('M Y', time()),
            'Header: Expires: ' . gmdate('M Y', $this->get_lifetimestamp()),
            'Header: Pragma: ',
            'Header: Cache-Control: public, max-age=604800, immutable',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/phpunit;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
        ];
        if (!\min_enable_zlib_compression()) {
            $expected[] = 'Header: Content-Length: 12';
        }
        array_push($expected, ...[
            'Header: Vary: Accept-Encoding',
            'Exiting'
        ]);
        self::assertSame($expected, self::strip_debugging_messages($actual));
    }

    public function test_send_cached_file() {
        global $CFG;
        require_once($CFG->libdir . '/configonlylib.php');
        $instance = $this->get_mock_mediator_instance();
        ob_start();
        $instance->send_cached_file(new path(__FILE__));
        ob_end_clean();
        $actual = $this->getDebuggingMessages();
        $this->resetDebugging();

        $expected = [
            'Header: Etag: "etag_test"',
            'Header: Content-Disposition: inline; filename="'.get_class($instance).'.php"',
            'Header: Date: ' . gmdate('M Y', time()),
            'Header: Last-Modified: ' . gmdate('M Y', filemtime(__FILE__)),
            'Header: Expires: ' . gmdate('M Y', $this->get_lifetimestamp()),
            'Header: Pragma: ',
            'Header: Cache-Control: public, max-age=604800, immutable',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/phpunit;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
        ];
        if (!\min_enable_zlib_compression()) {
            $expected[] = 'Header: Content-Length: ' . filesize(__FILE__);
        }
        array_push($expected, ...[
            'Header: Vary: Accept-Encoding',
            'Exiting'
        ]);
        self::assertSame($expected, self::strip_debugging_messages($actual));
    }

    public function test_compare_if_none_match_etag() {
        $original_isset = isset($_SERVER['HTTP_IF_NONE_MATCH']);
        $original_value = $original_isset ? $_SERVER['HTTP_IF_NONE_MATCH'] : null;

        $instance = $this->get_mock_mediator_instance();
        $_SERVER['HTTP_IF_NONE_MATCH'] = null;
        self::assertFalse($instance->compare_if_none_match_etag());

        $_SERVER['HTTP_IF_NONE_MATCH'] = 'blah, etag_test, foo';
        self::assertTrue($instance->compare_if_none_match_etag());

        $_SERVER['HTTP_IF_NONE_MATCH'] = 'blah, W/etag_test, foo';
        self::assertTrue($instance->compare_if_none_match_etag());

        $_SERVER['HTTP_IF_NONE_MATCH'] = 'blah, W/beans, foo';
        self::assertFalse($instance->compare_if_none_match_etag());

        if ($original_isset) {
            $_SERVER['HTTP_IF_NONE_MATCH'] = $original_value;
        } else {
            unset($_SERVER['HTTP_IF_NONE_MATCH']);
        }
    }

    private function get_lifetimestamp() {
        return time() + 7 * 24 * 60 * 60;
    }

    private function get_mock_mediator_instance() {
        $class = $this->getMockForAbstractClass(mediator::class, ['etag_test']);
        $class->expects($this->any())->method('get_mimetype')->willReturn('text/phpunit');
        return $class;
    }

    private static function strip_debugging_messages(array $messages): array {
        $return = [];
        foreach ($messages as $message) {
            $text = $message->message;
            // Get rid of the time from: Fri, 21 Aug 2020 03:26:20 => Fri, 21 Aug 2020
            $text = preg_replace('/\w{3,4}, \d{1,2} (\w{3,4} 20\d{2}) \d{1,2}:\d{1,2}:\d{1,2} GMT/', '$1', $text);
            $return[] = $text;
        }
        return $return;
    }



}