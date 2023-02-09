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
use totara_tui\local\mediation\json\mediator;
use totara_tui\local\mediation\json\resolver;

defined('MOODLE_INTERNAL') || die();

class totara_tui_local_mediation_json_resolver_testcase extends advanced_testcase {

    private function skip_if_build_not_present() {
        $vendors_file = bundle::get_vendors_file();
        if (empty($vendors_file) || !file_exists($vendors_file)) {
            $this->markTestSkipped('Tui build files must exist for this test to complete.');
        }
    }

    public function test_valid_css_variables_request() {
        $this->skip_if_build_not_present();

        $rev = time();
        $component = 'tui';
        $file = 'css_variables';

        [$json, $messages, $file_path] = $this->get_resolver($rev, $component, $file);

        self::assertTrue(resolver::validate_requested_file($file));

        $expected = [
            'Header: Etag: "'.sha1('tui-json ' . $rev . ' '.$component.' '.$file).'"',
            'Header: Content-Disposition: inline; filename="json.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', filemtime($file_path)),
            'Header: Expires: ' . gmdate('D, d M Y', $this->get_lifetimestamp($rev)),
            'Header: Pragma: ',
            'Header: Cache-Control: public, max-age=604800, immutable',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: application/json;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
        ];
        if (!\min_enable_zlib_compression()) {
            $expected[] = 'Header: Content-Length: ' . filesize($file_path);
        }
        array_push($expected, ...[
            'Header: Vary: Accept-Encoding',
            'Exiting',
        ]);

        self::assertSame($expected, self::strip_debugging_messages($messages));

        self::assertStringStartsWith('{', $json);
        self::assertStringEndsWith("}", $json);
    }

    public function test_invalid_tui_request() {
        global $CFG;
        require_once($CFG->libdir . '/configonlylib.php');

        $rev = time();
        $component = 'tui';
        $file = 'tui';

        self::assertFalse(resolver::validate_requested_file($file));

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('You whitelisted a json file name, and didn\'t handle it! ('.$file.')');
        (new resolver(mediator::class, $rev, $component, $file))->resolve();
    }

    public function test_invalid_component_request() {
        $rev = time();
        $component = 'bananas';
        $file = 'css_variables';

        [$json, $messages, $file_path] = $this->get_resolver($rev, $component, $file);

        self::assertTrue(resolver::validate_requested_file($file));

        $expected = [
            'Header: Etag: "'.sha1('tui-json ' . $rev . ' '.$component.' '.$file).'"',
            'Header: Content-Disposition: inline; filename="json.php"',
            'Header: Date: ' . gmdate('D, d M Y', time()),
            'Header: Last-Modified: ' . gmdate('D, d M Y', filemtime($file_path)),
            'Header: Expires: ' . gmdate('D, d M Y', $this->get_lifetimestamp($rev)),
            'Header: Pragma: ',
            'Header: Cache-Control: public, max-age=604800, immutable',
            'Header: Accept-Ranges: none',
            'Header: Content-Type: application/json;charset=utf-8',
            'Header: X-Content-Type-Options: nosniff',
        ];
        if (!\min_enable_zlib_compression()) {
            $expected[] = 'Header: Content-Length: ' . filesize($file_path);
        }
        array_push($expected, ...[
            'Header: Vary: Accept-Encoding',
            'Exiting',
        ]);

        self::assertSame($expected, self::strip_debugging_messages($messages));

        self::assertStringContainsString('null', $json);
    }

    private function get_resolver(int $rev, $component, $file = 'p') {
        global $CFG;
        require_once($CFG->libdir . '/configonlylib.php');
        $resolver = new resolver(
            mediator::class,
            $rev,
            $component,
            $file
        );
        ob_start();
        $resolver->resolve();
        $json = ob_get_contents();
        ob_end_clean();
        $messages = $this->getDebuggingMessages();
        $this->resetDebugging();


        $prop = new ReflectionProperty(\totara_tui\local\mediation\resolver::class, 'cachefile');
        $prop->setAccessible(true);

        return [trim($json), $messages, $prop->getValue($resolver)];
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