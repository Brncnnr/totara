<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests of our upstream hacks and behaviour expected in Totara.
 */
class totara_core_weblib_testcase extends advanced_testcase {
    public function test_clean_text() {
        // Make sure that data-core-autoinitialise and data-core-autoinitialise-amd are
        // stripped from from HTML markup added by regular users.
        $html = '<div class="someclass" data-core-autoinitialise="true" data-core-autoinitialise-amd="mod_mymod/myelement" data-x-yyy="2">sometext</div>';
        $expected = '<div class="someclass">sometext</div>';
        $this->assertSame($expected, clean_text($html, FORMAT_HTML));

        // Make sure obsolete messenger schemes are not supported any more.
        $html = '<a href="ymsgr:im?to=example_user">sometext</a>';
        $expected = '<a>sometext</a>';
        $this->assertSame($expected, clean_text($html, FORMAT_HTML));

        $html = '<a href="aim:whatever?go">sometext</a>';
        $expected = '<a>sometext</a>';
        $this->assertSame($expected, clean_text($html, FORMAT_HTML));

        $html = '<a href="myim:whatever">sometext</a>';
        $expected = '<a>sometext</a>';
        $this->assertSame($expected, clean_text($html, FORMAT_HTML));

        $html = '<a href="msnim:chat?contact=nada@example.com">sometext</a>';
        $expected = '<a>sometext</a>';
        $this->assertSame($expected, clean_text($html, FORMAT_HTML));

        $html = '<a href="ftp://xx.yy.zz">sometext</a>';
        $expected = '<a>sometext</a>';
        $this->assertSame($expected, clean_text($html, FORMAT_HTML));

        $html = '<a href="gopher://anything">sometext</a>';
        $expected = '<a>sometext</a>';
        $this->assertSame($expected, clean_text($html, FORMAT_HTML));
    }

    public function test_purify_uri() {
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc', true));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc', true, true));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc', false));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://www.example.com/test.php?xx=1&bb=2#abc', false, true));

        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc', true));
        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc', true, true));
        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc', false));
        $this->assertSame('https://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('https://www.example.com/test.php?xx=1&bb=2#abc', false, true));

        $this->assertSame('www.example.com/test.php', purify_uri('www.example.com/test.php'));
        $this->assertSame('www.example.com/test.php', purify_uri('www.example.com/test.php', true));
        $this->assertSame('www.example.com/test.php', purify_uri('www.example.com/test.php', true, false));
        $this->assertSame('', purify_uri('www.example.com/test.php', true, true));
        $this->assertSame('www.example.com/test.php', purify_uri('www.example.com/test.php', false));
        $this->assertSame('', purify_uri('www.example.com/test.php', false, true));
        $this->assertSame('', purify_uri('www.example.com/test.php', false, true));

        // Blocking wrong schemas.

        $this->assertSame('', purify_uri('ftp://www.example.com/test.txt'));
        $this->assertSame('', purify_uri('ftp://www.example.com/test.txt', true));
        $this->assertSame('', purify_uri('ftp://www.example.com/test.txt', true, true));
        $this->assertSame('', purify_uri('ftp://www.example.com/test.txt', false));

        $this->assertSame('', purify_uri('test: test'));
        $this->assertSame('', purify_uri('test: test', true));
        $this->assertSame('', purify_uri('test: test', false));

        $this->assertSame('', purify_uri(' test: test'));
        $this->assertSame('', purify_uri(' test: test', true));
        $this->assertSame('', purify_uri(' test: test', false));

        $this->assertSame('', purify_uri(null));
        $this->assertSame('', purify_uri(null, true));
        $this->assertSame('', purify_uri(null, false));

        $this->assertSame('', purify_uri(''));
        $this->assertSame('', purify_uri('', true));
        $this->assertSame('', purify_uri('', false));

        $this->assertSame('', purify_uri('javascript:alert(1)'));
        $this->assertSame('', purify_uri('javascript:alert(1)', true));
        $this->assertSame('', purify_uri('javascript:alert(1)', false));

        $this->assertSame('', purify_uri('<javascript>'));
        $this->assertSame('', purify_uri('<javascript>', true));
        $this->assertSame('', purify_uri('<javascript>', false));

        // Automatic fixing.

        $this->assertSame('test%20test', purify_uri('test test'));
        $this->assertSame('test%20test', purify_uri('test test', true));
        $this->assertSame('test%20test', purify_uri('test test', false));
        $this->assertSame('', purify_uri('test test', true, true));

        $this->assertSame('test%20%3A%20test', purify_uri('test : test'));
        $this->assertSame('test%20%3A%20test', purify_uri('test : test', true));
        $this->assertSame('test%20%3A%20test', purify_uri('test : test', false));
        $this->assertSame('', purify_uri('test : test', true, true));

        $this->assertSame('http://www.example.com/test.php?xx=%271%27&bb=%222%22#abc%20c', purify_uri(" http://www.example.com/test.php?xx='1'&amp;bb=\"2\n\"#abc\t c "));

        $this->assertSame('/www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http:/www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('', purify_uri('http:/www.example.com/test.php?xx=1&bb=2#abc', true, true));
        $this->assertSame('www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http:www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('', purify_uri('http:www.example.com/test.php?xx=1&bb=2#abc', true, true));

        // No user names and passwords in URIs.
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://username:password@www.example.com/test.php?xx=1&bb=2#abc'));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://username:password@www.example.com/test.php?xx=1&bb=2#abc', true));
        $this->assertSame('http://www.example.com/test.php?xx=1&bb=2#abc', purify_uri('http://username:password@www.example.com/test.php?xx=1&bb=2#abc', false));

    }

    public function test_purify_css_color() {
        $this->assertSame('#ffAAbb', purify_css_color('#ffAAbb'));
        $this->assertSame('#f0f', purify_css_color('#f0f'));
        $this->assertSame('rgb(99%,0%,0%)', purify_css_color('rgb(99%, 0%, 0%)'));
        $this->assertSame('rgb(255,0,0)', purify_css_color('rgb(255,0,0)'));
        $this->assertSame('rgba(255,0,0,0)', purify_css_color('rgba(255,0,0,0)'));
        $this->assertSame('hsla(50,80%,50%,0.1)', purify_css_color('hsla(50, 80%, 50%, 0.1)'));

        $this->assertSame('#FF0000', purify_css_color('red'));
        $this->assertSame('#0f0', purify_css_color('0f0'));
        $this->assertSame('#ffAAbb', purify_css_color(' #ffAAbb '));

        $this->assertFalse(purify_css_color(''));
        $this->assertFalse(purify_css_color(' '));
        $this->assertFalse(purify_css_color('#fxf'));
        $this->assertFalse(purify_css_color('# f0f'));
        $this->assertFalse(purify_css_color('opr'));
        $this->assertFalse(purify_css_color('expression(1)'));
        $this->assertFalse(purify_css_color('=#f0f'));
    }

    /**
     * tests for proprietary CSS allowed in \HTMLPurifier_CSSDefinition::doSetupProprietary()
     */
    public function test_purify_html_css_proprietary() {
        // Include just a few options here to make sure it was enabled.
        $this->assertSame('<div style="border-radius:5px;"></div>', purify_html('<div style="border-radius: 5px" />'));
        $this->assertSame('<div style="page-break-before:always;"></div>', purify_html('<div style="page-break-before: always" />'));
    }

    public function test_clean_string() {
        $data = '&amp;&lt;&gt;&quot;&apos;<>"\'{}';
        $expected = '&#38;&#60;&#62;&#34;&#39;&#60;&#62;&#34;&#39;&#123;&#125;';
        $this->assertSame($expected, clean_string($data));
        $this->assertSame(core_text::entities_to_utf8($data), core_text::entities_to_utf8($expected));

        $this->assertSame('&#38;', clean_string('&'));
        $this->assertSame(' &#38;', clean_string(' &'));
        $this->assertSame('&#38; ', clean_string('& '));
        $this->assertSame('&#38;amp&#38;;', clean_string('&amp&amp;;'));

        $this->assertSame('&num;', clean_string('&num;'));
    }

    public function test_format_string() {
        global $CFG;
        $this->assertSame('hokus &#38; &#34;pokus&#34;', format_string('<b>hokus & "pokus"</b>', true));
        $this->assertSame('&#60;b&#62;hokus &#38; &#34;pokus&#34;&#60;/b&#62;', format_string('<b>hokus & "pokus"</b>', false));
        $this->assertSame('hokus &#38; &#34;pokus&#34;', format_string('<b>hokus & "pokus"</b>', true, ['escape' => false]));
        $this->assertSame('&#60;b&#62;hokus &#38; &#34;pokus&#34;&#60;/b&#62;', format_string('<b>hokus & "pokus"</b>', true, ['escape' => true]));
        $this->assertSame('hokus &#38; &#34;pokus&#34;', format_string('<b>hokus & "pokus"</b>', false, ['escape' => false]));
        $this->assertSame('&#60;b&#62;hokus &#38; &#34;pokus&#34;&#60;/b&#62;', format_string('<b>hokus & "pokus"</b>', false, ['escape' => true]));

        // Make sure the removed setting is ignored.
        $CFG->formatstringstriptags = '0';
        $this->assertSame('hokus &#38; &#34;pokus&#34;', format_string('<b>hokus & "pokus"</b>', true));
        $this->assertSame('&#60;b&#62;hokus &#38; &#34;pokus&#34;&#60;/b&#62;', format_string('<b>hokus & "pokus"</b>', false));
        $this->assertSame('hokus &#38; &#34;pokus&#34;', format_string('<b>hokus & "pokus"</b>', true, ['escape' => false]));
        $this->assertSame('&#60;b&#62;hokus &#38; &#34;pokus&#34;&#60;/b&#62;', format_string('<b>hokus & "pokus"</b>', true, ['escape' => true]));
        $this->assertSame('hokus &#38; &#34;pokus&#34;', format_string('<b>hokus & "pokus"</b>', false, ['escape' => false]));
        $this->assertSame('&#60;b&#62;hokus &#38; &#34;pokus&#34;&#60;/b&#62;', format_string('<b>hokus & "pokus"</b>', false, ['escape' => true]));
    }
}
