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

use totara_tui\local\locator\bundle;
use totara_tui\local\mediation\styles\mediator;
use \totara_tui\local\mediation\styles\resolver;

defined('MOODLE_INTERNAL') || die();

class totara_tui_local_mediation_styles_resolver_testcase extends advanced_testcase {

    private function skip_if_build_not_present() {
        $vendors_file = bundle::get_vendors_file();
        if (empty($vendors_file) || !file_exists($vendors_file)) {
            $this->markTestSkipped('Tui build files must exist for this test to complete.');
        }
    }

    private function get_etag(int $rev, $mode, $rtl = false) {

        $resolver = new resolver(mediator::class, $rev, 'ventura', 'theme_ventura', $mode, 0, $rtl);
        $method = new ReflectionMethod($resolver, 'calculate_etag');
        $method->setAccessible(true);
        $etag = $method->invoke($resolver);

        return $etag;
    }

    public function test_production_mode_cached() {
        $this->skip_if_build_not_present();

        $rev = time();

        [$css, $messages, $file] = $this->get_resolver($rev, 'p');

        self::assertSame('p', bundle::get_css_suffix_for_url());

        $expected = [
            'Header: Etag: "'.$this->get_etag($rev, 'p' , false).'"',
            'Header: Content-Disposition: inline; filename="styles.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', filemtime($file)),
            'Header: Expires: ' . gmdate('D, d M Y', $this->get_lifetimestamp($rev)),
            'Header: Pragma: ',
            'Header: Cache-Control: public, max-age=604800, immutable',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/css;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
        ];
        if (!\min_enable_zlib_compression()) {
            $expected[] = 'Header: Content-Length: ' . filesize($file);
        }
        array_push($expected, ...[
            'Header: Vary: Accept-Encoding',
            'Exiting',
        ]);
        self::assertSame($expected, self::strip_debugging_messages($messages));

        self::assertStringStartsWith('@import \'definitions_only!internal_absolute:', $css);
    }

    public function test_production_legacy_mode_cached() {
        $this->skip_if_build_not_present();

        $rev = time();

        // Fake IE.
        \core_useragent::instance(true, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; KTXN)');

        [$css, $messages, $file] = $this->get_resolver($rev, 'pl');
        self::assertSame('pl', bundle::get_css_suffix_for_url());

        $expected = [
            'Header: Etag: "'.$this->get_etag($rev, 'pl' , false).'"',
            'Header: Content-Disposition: inline; filename="styles.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', filemtime($file)),
            'Header: Expires: ' . gmdate('D, d M Y', $this->get_lifetimestamp($rev)),
            'Header: Pragma: ',
            'Header: Cache-Control: public, max-age=604800, immutable',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/css;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
        ];
        if (!\min_enable_zlib_compression()) {
            $expected[] = 'Header: Content-Length: ' . filesize($file);
        }
        array_push($expected, ...[
            'Header: Vary: Accept-Encoding',
            'Exiting',
        ]);
        self::assertSame($expected, self::strip_debugging_messages($messages));

        self::assertStringStartsWith('@import \'definitions_only!internal_absolute:', $css);
    }

    public function test_development_mode_cached() {
        $this->skip_if_build_not_present();

        global $CFG;
        $CFG->forced_plugin_settings['totara_tui'] = ['development_mode' => true];

        $rev = time();

        [$css, $messages, $file] = $this->get_resolver($rev, 'd');

        self::assertSame('d', bundle::get_css_suffix_for_url());

        $expected = [
            'Header: Etag: "'.$this->get_etag($rev, 'd' , false).'"',
            'Header: Content-Disposition: inline; filename="styles.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', filemtime($file)),
            'Header: Expires: ' . gmdate('D, d M Y', $this->get_lifetimestamp($rev)),
            'Header: Pragma: ',
            'Header: Cache-Control: public, max-age=604800, immutable',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/css;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
        ];
        if (!\min_enable_zlib_compression()) {
            $expected[] = 'Header: Content-Length: ' . filesize($file);
        }
        array_push($expected, ...[
            'Header: Vary: Accept-Encoding',
            'Exiting',
        ]);
        self::assertSame($expected, self::strip_debugging_messages($messages));

        self::assertStringStartsWith('@import \'definitions_only!internal_absolute:', $css);
    }

    public function test_development_legacy_mode_cached() {
        $this->skip_if_build_not_present();

        global $CFG;
        $CFG->forced_plugin_settings['totara_tui'] = ['development_mode' => true];

        $rev = time();

        // Fake IE.
        \core_useragent::instance(true, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; KTXN)');

        [$css, $messages, $file] = $this->get_resolver($rev, 'dl');

        self::assertSame('dl', bundle::get_css_suffix_for_url());

        $expected = [
            'Header: Etag: "'.$this->get_etag($rev, 'dl' , false).'"',
            'Header: Content-Disposition: inline; filename="styles.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', filemtime($file)),
            'Header: Expires: ' . gmdate('D, d M Y', $this->get_lifetimestamp($rev)),
            'Header: Pragma: ',
            'Header: Cache-Control: public, max-age=604800, immutable',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/css;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
        ];
        if (!\min_enable_zlib_compression()) {
            $expected[] = 'Header: Content-Length: ' . filesize($file);
        }
        array_push($expected, ...[
            'Header: Vary: Accept-Encoding',
            'Exiting',
        ]);
        self::assertSame($expected, self::strip_debugging_messages($messages));

        self::assertStringStartsWith('@import \'definitions_only!internal_absolute:', $css);
    }

    public function test_production_mode_uncached() {
        $this->skip_if_build_not_present();

        $rev = -1;

        [$css, $messages, $file] = $this->get_resolver($rev, 'p');

        self::assertSame('p', bundle::get_css_suffix_for_url());

        self::assertSame([
            'Header: Etag: "'.$this->get_etag($rev, 'p' , false).'"',
            'Header: Content-Disposition: inline; filename="styles.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', time()),
            'Header: Expires: ' . gmdate('D, d M Y', time()),
            'Header: Cache-Control: no-cache',
            'Header: Pragma: no-cache',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/css;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
            'Exiting',
        ], self::strip_debugging_messages($messages));

        self::assertStringStartsWith('@import \'definitions_only!internal_absolute:', $css);
    }

    public function test_production_legacy_mode_uncached() {
        $this->skip_if_build_not_present();

        $rev = -1;

        // Fake IE.
        \core_useragent::instance(true, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; KTXN)');

        [$css, $messages, $file] = $this->get_resolver($rev, 'pl');
        self::assertSame('pl', bundle::get_css_suffix_for_url());

        self::assertSame([
            'Header: Etag: "'.$this->get_etag($rev, 'pl' , false).'"',
            'Header: Content-Disposition: inline; filename="styles.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', time()),
            'Header: Expires: ' . gmdate('D, d M Y', time()),
            'Header: Cache-Control: no-cache',
            'Header: Pragma: no-cache',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/css;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
            'Exiting',
        ], self::strip_debugging_messages($messages));

        self::assertStringStartsWith('@import \'definitions_only!internal_absolute:', $css);
    }

    public function test_development_mode_uncached() {
        $this->skip_if_build_not_present();

        global $CFG;
        $CFG->forced_plugin_settings['totara_tui'] = ['development_mode' => true];

        $rev = -1;

        [$css, $messages, $file] = $this->get_resolver($rev, 'd');

        self::assertSame('d', bundle::get_css_suffix_for_url());

        self::assertSame([
            'Header: Etag: "'.$this->get_etag($rev, 'd' , false).'"',
            'Header: Content-Disposition: inline; filename="styles.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', time()),
            'Header: Expires: ' . gmdate('D, d M Y', time()),
            'Header: Cache-Control: no-cache',
            'Header: Pragma: no-cache',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/css;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
            'Exiting',
        ], self::strip_debugging_messages($messages));

        self::assertStringStartsWith('@import \'definitions_only!internal_absolute:', $css);
    }

    public function test_development_legacy_mode_uncached() {
        $this->skip_if_build_not_present();

        global $CFG;
        $CFG->forced_plugin_settings['totara_tui'] = ['development_mode' => true];

        $rev = -1;

        // Fake IE.
        \core_useragent::instance(true, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; KTXN)');

        [$css, $messages, $file] = $this->get_resolver($rev, 'dl');

        self::assertSame('dl', bundle::get_css_suffix_for_url());

        self::assertSame([
            'Header: Etag: "'.$this->get_etag($rev, 'dl' , false).'"',
            'Header: Content-Disposition: inline; filename="styles.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', time()),
            'Header: Expires: ' . gmdate('D, d M Y', time()),
            'Header: Cache-Control: no-cache',
            'Header: Pragma: no-cache',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: text/css;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
            'Exiting',
        ], self::strip_debugging_messages($messages));

        self::assertStringStartsWith('@import \'definitions_only!internal_absolute:', $css);
    }

    public function test_development_mode_uncached_stale_in_cache() {
        global $CFG;
        $CFG->forced_plugin_settings['totara_tui'] = ['development_mode' => true];
        $rev = -1;

        // Set the expected etag as an IF_NONE_MATCH header
        $resolver = new resolver(mediator::class, $rev, 'ventura', 'theme_ventura', 'd', 0);
        $method = new ReflectionMethod($resolver, 'calculate_etag');
        $method->setAccessible(true);
        $_SERVER['HTTP_IF_NONE_MATCH'] = $etag = $method->invoke($resolver);

        // Once to prime the cache
        $this->get_resolver($rev, 'd');
        // And a second to check we've got the expected outcome aftewards.
        [$css, $messages, $file] = $this->get_resolver($rev, 'd');

        self::assertSame('d', bundle::get_css_suffix_for_url());

        self::assertSame([
            'Header: HTTP/1.1 304 Not Modified',
            'Header: Etag: "'.$etag.'"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', time()),
            'Header: Expires: ' . gmdate('D, d M Y', time()),
            'Header: Cache-Control: no-cache',
            'Header: Pragma: no-cache',
            'Exiting',
        ], self::strip_debugging_messages($messages));

        self::assertEmpty($css);
    }

    private function get_resolver(int $rev, $mode = 'p') {
        global $CFG;
        require_once($CFG->libdir . '/configonlylib.php');
        $resolver = new resolver(
            mediator::class,
            $rev,
            'ventura',
            'theme_ventura',
            $mode,
            0
        );

        ob_start();
        $resolver->resolve();
        $css = ob_get_contents();
        ob_end_clean();
        $messages = $this->getDebuggingMessages();
        $this->resetDebugging();

        $prop = new ReflectionProperty(\totara_tui\local\mediation\resolver::class, 'cachefile');
        $prop->setAccessible(true);

        return [$css, $messages, $prop->getValue($resolver)];
    }

    private static function strip_debugging_messages(array $messages): array {
        $return = [];
        foreach ($messages as $message) {
            $text = $message->message;
            // Get rid of the time from: Fri, 21 Aug 2020 03:26:20 => Fri, 21 Aug 2020
            $text = preg_replace('/(\w{3,4}, \d{1,2} \w{3,4} 20\d{2}) \d{1,2}:\d{1,2}:\d{1,2} GMT/', '$1', $text);
            $return[] = $text;
        }
        return $return;
    }

    private function get_lifetimestamp(?int $time = null) {
        return ($time ?? time()) + 7 * 24 * 60 * 60;
    }

}