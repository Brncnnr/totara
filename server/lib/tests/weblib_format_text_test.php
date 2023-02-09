<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for format_text defined in weblib.php.
 *
 * @package   core
 * @category  test
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Unit tests for format_text defined in weblib.php.
 *
 * @copyright 2015 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class core_weblib_format_text_testcase extends advanced_testcase {

    /**
     * This function is used to fix inconsistent behaviour of format_text across operating systems when cleaning markup with
     * multiple newlines.
     *
     * In OSX multiple continuous newlines are preserved. In Linux they are not.
     *
     * @param string $text
     * @return string
     */
    private function fix_newlines(string $text): string {
        // We don't replace with another character here, the regex problem means multiple newlines are left in OSX and reduced in
        // linux, the character replacement is therefor inconsistent.
        $text = str_replace("\n", '', $text);
        return $text;
    }

    /**
     * Totara: wrap text in JSON editor doc envelope.
     *
     * @param string $text
     * @return string
     */
    private function json_text(string $text): string {
        return '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"' . addslashes($text) . '"}]}]}';
    }

    public function test_option_filter_at_system_context() {
        global $CFG;

        $CFG->filter_censor_badwords = 'one,red';
        filter_set_global_state('censor', TEXTFILTER_ON);

        $text = 'I have one red balloon';
        $censored_html = 'I have <span class="censoredtext" title="one">**</span> <span class="censoredtext" title="red">**</span> balloon';
        $censored_plain = $text; // Plain text is NEVER filtered!
        $censored_markdown = "<p>I have <span class=\"censoredtext\" title=\"one\">**</span> <span class=\"censoredtext\" title=\"red\">**</span> balloon</p>\n";
        $censored_moodle = '<div class="text_to_html">I have <span class="censoredtext" title="one">**</span> <span class="censoredtext" title="red">**</span> balloon</div>';
        $censored_json = '<p>I have <span class="censoredtext" title="one">**</span> <span class="censoredtext" title="red">**</span> balloon</p>';

        // Filters turned on.
        $options = ['filter' => true];
        self::assertSame($censored_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($censored_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($censored_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($censored_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($censored_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        // Filters turned off.
        $options = ['filter' => false];
        self::assertSame($text, format_text($text, FORMAT_HTML, $options));
        self::assertSame($text, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame('<p>' . $text . "</p>\n", format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame('<div class="text_to_html">' . $text . '</div>', format_text($text, FORMAT_MOODLE, $options));
        self::assertSame('<p>' . $text . '</p>', format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        // Filters default (on).
        $options = [];
        self::assertSame($censored_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($censored_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($censored_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($censored_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($censored_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));
    }

    public function test_option_filter_at_course_context_filter() {
        global $CFG;

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $CFG->filter_censor_badwords = 'one,red';
        filter_set_local_state('censor', $context->id, TEXTFILTER_ON);

        $text = 'I have one red balloon';

        $expected_html = 'I have one red balloon';
        $expected_plain = 'I have one red balloon';
        $expected_markdown = "<p>I have one red balloon</p>\n";
        $expected_moodle = '<div class="text_to_html">I have one red balloon</div>';
        $expected_json = '<p>I have one red balloon</p>';

        $censored_html = 'I have <span class="censoredtext" title="one">**</span> <span class="censoredtext" title="red">**</span> balloon';
        $censored_plain = $text; // Plain text is NEVER filtered!
        $censored_markdown = "<p>I have <span class=\"censoredtext\" title=\"one\">**</span> <span class=\"censoredtext\" title=\"red\">**</span> balloon</p>\n";
        $censored_moodle = '<div class="text_to_html">I have <span class="censoredtext" title="one">**</span> <span class="censoredtext" title="red">**</span> balloon</div>';
        $censored_json = '<p>I have <span class="censoredtext" title="one">**</span> <span class="censoredtext" title="red">**</span> balloon</p>';

        // Filters turned on.
        $options = ['filter' => true, 'context' => \context_system::instance()];
        self::assertSame($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        $options = ['filter' => true, 'context' => $context];
        self::assertSame($censored_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($censored_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($censored_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($censored_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($censored_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        // Filters turned off.
        $options = ['filter' => false, 'context' => \context_system::instance()];
        self::assertSame($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        $options = ['filter' => false, 'context' => $context];
        self::assertSame($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        // Filters default (on).
        $options = ['context' => \context_system::instance()];
        self::assertSame($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        $options = ['context' => $context];
        self::assertSame($censored_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($censored_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($censored_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($censored_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($censored_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        // Finally test with the legacy courseid context arg.
        $options = [];
        self::assertSame($censored_html, format_text($text, FORMAT_HTML, $options, $course->id));
        self::assertSame($censored_plain, format_text($text, FORMAT_PLAIN, $options, $course->id));
        self::assertSame($censored_markdown, format_text($text, FORMAT_MARKDOWN, $options, $course->id));
        self::assertSame($censored_moodle, format_text($text, FORMAT_MOODLE, $options, $course->id));
        self::assertSame($censored_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options, $course->id));
    }

    public function test_utf8_thai() {
        filter_set_global_state('censor', TEXTFILTER_ON);

        $text = 'ฉันนักพัฒนาที่ Totara ขอให้คุณดี';

        $expected_html = $text;
        $expected_plain = $text;
        $expected_markdown = '<p>' . $text . "</p>\n";
        $expected_moodle = '<div class="text_to_html">' . $text . '</div>';
        $expected_json = '<p>' . $text . '</p>';

        self::assertSame($expected_html, format_text($text, FORMAT_HTML));
        self::assertSame($expected_plain, format_text($text, FORMAT_PLAIN));
        self::assertSame($expected_markdown, format_text($text, FORMAT_MARKDOWN));
        self::assertSame($expected_moodle, format_text($text, FORMAT_MOODLE));
        self::assertSame($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR));
    }

    public function test_ut8_anglo_saxan() {
        filter_set_global_state('censor', TEXTFILTER_ON);

        $chars = 'ᚠᛁᛋᚳ᛫ᚠᛚᚩᛞᚢ᛫ᚪᚻᚩᚠᚩᚾᚠᛖᚱᚷ ᛖᚾᛒᛖᚱᛁᚷ ᚹᚪᚱᚦᚷᚪ᛬ᛋᚱᛁᚳᚷᚱᚩᚱᚾᚦᚫᚱᚻᛖᚩᚾᚷᚱᛖᚢᛏᚷᛁᛋᚹᚩᛗ ᚻ' .
            'ᚱᚩᚾᚫᛋᛒᚪᚾ ᛗᚫᚷᛁᚠᛁᛋᚳ᛫ᚠᛚᚩᛞᚢ᛫ᚪᚻᚩᚠᚩᚾᚠᛖᚱᚷ ᛖᚾᛒᛖᚱᛁᚷ ᚹᚪᚱᚦᚷᚪ᛬ᛋᚱᛁᚳᚷᚱᚩᚱᚾᚦᚫᚱᚻᛖᚩᚾᚷᚱᛖᚢᛏᚷᛁᛋᚹᚩᛗ ᚻᚱᚩᚾᚫᛋᛒᚪᚾ ᛗᚫᚷᛁ';
        $text = '<a href="https://en.wikipedia.org/wiki/Franks_Casket#ᛖᚾᛒᛖᚱᛁᚷ">'.$chars.'</a>';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","marks":[{"type":"link","attrs":{"href":"https://en.wikipedia.org/wiki/Franks_Casket#%E1%9B%96%E1%9A%BE%E1%9B%92%E1%9B%96%E1%9A%B1%E1%9B%81%E1%9A%B7"}}],"text":"' . $chars . '"}]}]}';
        $options = ['newlines' => true, 'para' => true, 'blanktarget' => true];

        $expected_html = '<a href="https://en.wikipedia.org/wiki/Franks_Casket#%E1%9B%96%E1%9A%BE%E1%9B%92%E1%9B%96%E1%9A%B1%E1%9B%81%E1%9A%B7" target="_blank" rel="noreferrer noopener">' . $chars . '</a>';
        $expected_plain = '<p>&lt;a href="https://en.wikipedia.org/wiki/Franks_Casket#ᛖᚾᛒᛖᚱᛁᚷ"&gt;' . $chars . '&lt;/a&gt;</p>';
        $expected_markdown = '<p><a href="https://en.wikipedia.org/wiki/Franks_Casket#%E1%9B%96%E1%9A%BE%E1%9B%92%E1%9B%96%E1%9A%B1%E1%9B%81%E1%9A%B7" target="_blank" rel="noreferrer noopener">' . $chars . '</a></p>';
        $expected_moodle = '<div class="text_to_html"><a href="https://en.wikipedia.org/wiki/Franks_Casket#%E1%9B%96%E1%9A%BE%E1%9B%92%E1%9B%96%E1%9A%B1%E1%9B%81%E1%9A%B7" target="_blank" rel="noreferrer noopener">' . $chars . '</a></div>';
        $expected_json = '<p><a href="https://en.wikipedia.org/wiki/Franks_Casket#%E1%9B%96%E1%9A%BE%E1%9B%92%E1%9B%96%E1%9A%B1%E1%9B%81%E1%9A%B7" target="_blank" rel="noreferrer noopener">' . $chars . '</a></p>';

        self::assertSame($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));
    }

    public function test_utf8_thors_map_ruins() {
        filter_set_global_state('censor', TEXTFILTER_ON);

        $text = 'ᛋᛏᚫᚾᛞ ᛒᚣ ᚦᛖ ᚷᚱᛖᚣ ᛋᛏᚩᚾᛖ ᚻᚹᛁᛚᛖ ᚦᛖ ᚦᚱᚢᛋᚻ ᚾᚩᚳᛋ ᚫᚾᛞ ᚦᛖ ᛋᛖᛏᛏᛁᚾᚷ ᛋᚢᚾ ᚹᛁᚦ ᚦᛖ ᛚᚫᛋᛏ ᛚᛁᚷᚻᛏ ᚩᚠ ᛞᚢᚱᛁᚾᛋ ᛞᚫᚣ ᚹᛁᛚᛚ ᛋᚻᛁᚾᛖ ᚢᛈᚩᚾ ᚦᛖ ᚳᛖᚣᚻᚩᛚᛖ';
        $options = ['newlines' => true, 'para' => true, 'blanktarget' => true];

        $expected_html = '<p>' . $text . '</p>';
        $expected_plain = '<p>' . $text . '</p>';
        $expected_markdown = '<p>' . $text . '</p>';
        $expected_moodle = '<div class="text_to_html">' . $text . '</div>';
        $expected_json = '<p>' . $text . '</p>';

        self::assertSame($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertSame($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertSame($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertSame($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertSame($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));
    }

    public function test_empty_values() {
        self::assertSame('', format_text(''));
        self::assertSame('', format_text('', FORMAT_HTML));
        self::assertSame('', format_text('', FORMAT_PLAIN));
        self::assertSame('', format_text('', FORMAT_MARKDOWN));
        self::assertSame('', format_text('', FORMAT_MOODLE));
        self::assertSame('', format_text('', FORMAT_JSON_EDITOR));

        self::assertSame('', format_text(null));
        self::assertSame('', format_text(null, FORMAT_HTML));
        self::assertSame('', format_text(null, FORMAT_PLAIN));
        self::assertSame('', format_text(null, FORMAT_MARKDOWN));
        self::assertSame('', format_text(null, FORMAT_MOODLE));
        self::assertSame('', format_text(null, FORMAT_JSON_EDITOR));

        self::assertSame('<div class="text_to_html">0</div>', format_text(0));
        self::assertSame('0', format_text(0, FORMAT_HTML));
        self::assertSame('0', format_text(0, FORMAT_PLAIN));
        self::assertSame("<p>0</p>\n", format_text(0, FORMAT_MARKDOWN));
        self::assertSame('<div class="text_to_html">0</div>', format_text(0, FORMAT_MOODLE));
        self::assertSame('', format_text(0, FORMAT_JSON_EDITOR));
        $this->resetDebugging();
        self::assertSame('<p>0</p>', format_text($this->json_text(0), FORMAT_JSON_EDITOR));

        self::assertSame('<div class="text_to_html">0</div>', format_text('0'));
        self::assertSame('0', format_text('0', FORMAT_HTML));
        self::assertSame('0', format_text('0', FORMAT_PLAIN));
        self::assertSame("<p>0</p>\n", format_text('0', FORMAT_MARKDOWN));
        self::assertSame('<div class="text_to_html">0</div>', format_text('0', FORMAT_MOODLE));
        self::assertSame('', format_text('0', FORMAT_JSON_EDITOR));
        $this->resetDebugging();
        self::assertSame('<p>0</p>', format_text($this->json_text('0'), FORMAT_JSON_EDITOR));
    }

    public function test_removal_of_onclick_alert() {
        $text = 'I\'m the needle<a onclick="alert(1)">Hack</a>';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"I\'m the needle"},{"type":"text","marks":[{"type":"link","attrs":{"onclick":"alert(1);"}}],"text":"Hack"}]}]}';
        $expected_html = 'I\'m the needle<a>Hack</a>';
        $expected_plain = 'I&#039;m the needle&lt;a onclick=&quot;alert(1)&quot;&gt;Hack&lt;/a&gt;';
        $expected_markdown = "<p>I'm the needle<a>Hack</a></p>\n";
        $expected_moodle = '<div class="text_to_html">I\'m the needle<a>Hack</a></div>';
        $expected_json = '<p>I&#039;m the needle<a>Hack</a></p>';

        self::assertEquals($expected_moodle, format_text($text));
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE));
        self::assertEquals($expected_moodle, format_text($text, 'sam')); // Fake format.
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR));
    }

    public function test_wiki() {
        $text = 'I\'m the needle<a onclick="alert(1)">Hack</a>';
        $filtered = format_text($text, FORMAT_WIKI);
        self::assertStringContainsString(s($text), $filtered);
        self::assertStringNotContainsString($text, $filtered);
        self::assertStringNotContainsString('<a', $filtered);
        self::assertStringContainsString('NOTICE: Wiki-like formatting has been removed from Moodle', $filtered);
    }

    public function test_option_none() {
        $text = 'I\'m the needle<a onclick="alert(1)">Hack</a>';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"I\'m the needle"},{"type":"text","marks":[{"type":"link","attrs":{"onclick":"alert(1);"}}],"text":"Hack"}]}]}';
        $expected_html = 'I\'m the needle<a>Hack</a>';
        $expected_plain = 'I&#039;m the needle&lt;a onclick=&quot;alert(1)&quot;&gt;Hack&lt;/a&gt;';
        $expected_markdown = "<p>I'm the needle<a>Hack</a></p>\n";
        $expected_moodle = '<div class="text_to_html">I\'m the needle<a>Hack</a></div>';
        $expected_json = '<p>I&#039;m the needle<a>Hack</a></p>';

        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, []));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, []));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, []));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, []));
        self::assertEquals($expected_moodle, format_text($text, 'sam', [])); // Fake format.
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, []));

        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, (object)[]));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, (object)[]));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, (object)[]));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, (object)[]));
        self::assertEquals($expected_moodle, format_text($text, 'sam', (object)[])); // Fake format.
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, (object)[]));

        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, 0));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, 0));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, 0));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, 0));
        self::assertEquals($expected_moodle, format_text($text, 'sam', 0)); // Fake format.
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, 0));

        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, '0'));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, '0'));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, '0'));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, '0'));
        self::assertEquals($expected_moodle, format_text($text, 'sam', '0')); // Fake format.
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, '0'));
    }

    public function test_legacy_option_noclean() {
        global $CFG;

        self::assertEmpty($CFG->disableconsistentcleaning);

        // Test it when off
        $text = 'Check out <img src="#" onerror="alert(1)" />';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out "},{"type":"image","attrs":{"filename":"#","url":"#","onerror":"alert(1)"}}]}]}';
        $options = ['noclean' => false];
        $expected_html = 'Check out <img src="#" alt="#" />';
        $expected_plain = 'Check out &lt;img src=&quot;#&quot; onerror=&quot;alert(1)&quot; /&gt;';
        $expected_markdown = "<p>Check out <img src=\"#\" alt=\"#\" /></p>\n";
        $expected_moodle = '<div class="text_to_html">Check out <img src="#" alt="#" /></div>';
        $expected_json = '<p>Check out <div class="jsoneditor-image-block"><img src="#" alt="" class="jsoneditor-image-block__img" /></div></p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test it when on
        $text = 'Check out <img src="#" onerror="alert(1)" />';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out "},{"type":"image","attrs":{"filename":"#","url":"#","onerror":"alert(1)"}}]}]}';
        $options = ['noclean' => true];
        $expected_html = 'Check out <img src="#" alt="#" />';
        $expected_plain = 'Check out &lt;img src=&quot;#&quot; onerror=&quot;alert(1)&quot; /&gt;';
        $expected_markdown = "<p>Check out <img src=\"#\" alt=\"#\" /></p>\n";
        $expected_moodle = '<div class="text_to_html">Check out <img src="#" alt="#" /></div>';
        $expected_json = '<p>Check out <div class="jsoneditor-image-block"><img src="#" alt="" class="jsoneditor-image-block__img" /></div></p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        $CFG->disableconsistentcleaning = true;

        // Test it when off but legacy cleaning is on.
        $text = 'Check out <img src="#" onerror="alert(1)" />';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out "},{"type":"image","attrs":{"filename":"#","url":"#","onerror":"alert(1)"}}]}]}';
        $options = ['noclean' => false];
        $expected_html = 'Check out <img src="#" alt="#" />';
        $expected_plain = 'Check out &lt;img src=&quot;#&quot; onerror=&quot;alert(1)&quot; /&gt;';
        $expected_markdown = "<p>Check out <img src=\"#\" alt=\"#\" /></p>\n";
        $expected_moodle = '<div class="text_to_html">Check out <img src="#" alt="#" /></div>';
        $expected_json = '<p>Check out <div class="jsoneditor-image-block"><img src="#" alt="" class="jsoneditor-image-block__img" /></div></p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test it when on but legacy cleaning is on.
        $text = 'Check out <img src="#" onerror="alert(1)" />';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out "},{"type":"image","attrs":{"filename":"#","url":"#","onerror":"alert(1)"}}]}]}';
        $options = ['noclean' => true];
        $expected_html = 'Check out <img src="#" onerror="alert(1)" />';
        $expected_plain = 'Check out &lt;img src=&quot;#&quot; onerror=&quot;alert(1)&quot; /&gt;';
        $expected_markdown = "<p>Check out <img src=\"#\" onerror=\"alert(1)\" /></p>\n";
        $expected_moodle = '<div class="text_to_html">Check out <img src="#" onerror="alert(1)" /></div>';
        // HTML built from JSON should never have an onerror attribute, no matter what.
        $expected_json = '<p>Check out <div class="jsoneditor-image-block"><img src="#" alt="" class="jsoneditor-image-block__img" /></div></p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));
    }

    public function test_option_nocache() {
        // Test it when off
        $text = 'Check out my <a href="favourite">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['nocache' => false];
        $expected_html = 'Check out my <a href="favourite">favourite course</a> today';
        $expected_plain = 'Check out my &lt;a href=&quot;favourite&quot;&gt;favourite course&lt;/a&gt; today';
        $expected_markdown = "<p>Check out my <a href=\"favourite\">favourite course</a> today</p>\n";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test it when on
        $text = 'Check out my <a href="favourite">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['nocache' => true];
        $expected_html = 'Check out my <a href="favourite">favourite course</a> today';
        $expected_plain = 'Check out my &lt;a href=&quot;favourite&quot;&gt;favourite course&lt;/a&gt; today';
        $expected_markdown = "<p>Check out my <a href=\"favourite\">favourite course</a> today</p>\n";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test default is off
        $text = 'Check out my <a href="favourite">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = [];
        $expected_html = 'Check out my <a href="favourite">favourite course</a> today';
        $expected_plain = 'Check out my &lt;a href=&quot;favourite&quot;&gt;favourite course&lt;/a&gt; today';
        $expected_markdown = "<p>Check out my <a href=\"favourite\">favourite course</a> today</p>\n";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));
    }

    public function test_option_para() {
        // Test it when off
        $text = 'Check out my <a href="favourite">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['para' => false];
        $expected_html = 'Check out my <a href="favourite">favourite course</a> today';
        $expected_plain = 'Check out my &lt;a href=&quot;favourite&quot;&gt;favourite course&lt;/a&gt; today';
        $expected_markdown = "<p>Check out my <a href=\"favourite\">favourite course</a> today</p>\n";
        $expected_moodle = 'Check out my <a href="favourite">favourite course</a> today';
        $expected_json = '<p>Check out my <a href="favourite">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test it when on
        $text = 'Check out my <a href="favourite">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['para' => true];
        $expected_html = 'Check out my <a href="favourite">favourite course</a> today';
        $expected_plain = 'Check out my &lt;a href=&quot;favourite&quot;&gt;favourite course&lt;/a&gt; today';
        $expected_markdown = "<p>Check out my <a href=\"favourite\">favourite course</a> today</p>\n";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test the default is on.
        $text = 'Check out my <a href="favourite">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = [];
        $expected_html = 'Check out my <a href="favourite">favourite course</a> today';
        $expected_plain = 'Check out my &lt;a href=&quot;favourite&quot;&gt;favourite course&lt;/a&gt; today';
        $expected_markdown = "<p>Check out my <a href=\"favourite\">favourite course</a> today</p>\n";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));
    }

    public function test_option_newlines() {
        // Test it when off
        $text = "My favourite fruit:\n\t# Banana's\n\t# Apples \n\t#\tOranges\n\nWhat are your favourite?";
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"My favourite fruit:"},{"type":"hard_break"},{"type":"text","text":" Bananas"},{"type":"hard_break"},{"type":"text","text":" Apples"},{"type":"hard_break"},{"type":"text","text":" Oranges"},{"type":"hard_break"},{"type":"hard_break"},{"type":"text","text":"What are your favourite?"}]}]}';
        $options = ['newlines' => false];
        $expected_html = "My favourite fruit:\n\t# Banana's\n\t# Apples \n\t#\tOranges\n\nWhat are your favourite?";
        $expected_plain = "My favourite fruit:<br />\n\t# Banana&#039;s<br />\n\t# Apples <br />\n\t#\tOranges<br />\n<br />\nWhat are your favourite?";
        $expected_markdown = "<p>My favourite fruit:\n    # Banana's\n    # Apples \n    #   Oranges</p>\n\n<p>What are your favourite?</p>\n";
        $expected_moodle = "<div class=\"text_to_html\">My favourite fruit:\n\t# Banana's\n\t# Apples \n\t#\tOranges\n\nWhat are your favourite?</div>";
        $expected_json = '<p>My favourite fruit:<br/> Bananas<br/> Apples<br/> Oranges<br/><br/>What are your favourite?</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test it when on
        $text = "My favourite fruit:\n\t# Banana's\n\t# Apples \n\t#\tOranges\n\nWhat are your favourite?";
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"My favourite fruit:"},{"type":"hard_break"},{"type":"text","text":" Bananas"},{"type":"hard_break"},{"type":"text","text":" Apples"},{"type":"hard_break"},{"type":"text","text":" Oranges"},{"type":"hard_break"},{"type":"hard_break"},{"type":"text","text":"What are your favourite?"}]}]}';
        $options = ['newlines' => true];
        $expected_html = "My favourite fruit:\n\t# Banana's\n\t# Apples \n\t#\tOranges\n\nWhat are your favourite?";
        $expected_plain = "My favourite fruit:<br />\n\t# Banana&#039;s<br />\n\t# Apples <br />\n\t#\tOranges<br />\n<br />\nWhat are your favourite?";
        $expected_markdown = "<p>My favourite fruit:\n    # Banana's\n    # Apples \n    #   Oranges</p>\n\n<p>What are your favourite?</p>\n";
        $expected_moodle = "<div class=\"text_to_html\">My favourite fruit:<br />\n\t# Banana's<br />\n\t# Apples <br />\n\t#\tOranges<br />\n<br />\nWhat are your favourite?</div>";
        $expected_json = '<p>My favourite fruit:<br/> Bananas<br/> Apples<br/> Oranges<br/><br/>What are your favourite?</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals(
            $this->fix_newlines($expected_moodle),
            $this->fix_newlines(format_text($text, FORMAT_MOODLE, $options))
        );
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test default (on)
        $text = "My favourite fruit:\n\t# Banana's\n\t# Apples \n\t#\tOranges\n\nWhat are your favourite?";
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"My favourite fruit:"},{"type":"hard_break"},{"type":"text","text":" Bananas"},{"type":"hard_break"},{"type":"text","text":" Apples"},{"type":"hard_break"},{"type":"text","text":" Oranges"},{"type":"hard_break"},{"type":"hard_break"},{"type":"text","text":"What are your favourite?"}]}]}';
        $options = [];
        $expected_html = "My favourite fruit:\n\t# Banana's\n\t# Apples \n\t#\tOranges\n\nWhat are your favourite?";
        $expected_plain = "My favourite fruit:<br />\n\t# Banana&#039;s<br />\n\t# Apples <br />\n\t#\tOranges<br />\n<br />\nWhat are your favourite?";
        $expected_markdown = "<p>My favourite fruit:\n    # Banana's\n    # Apples \n    #   Oranges</p>\n\n<p>What are your favourite?</p>\n";
        $expected_moodle = "<div class=\"text_to_html\">My favourite fruit:<br />\n\t# Banana's<br />\n\t# Apples <br />\n\t#\tOranges<br />\n<br />\nWhat are your favourite?</div>";
        $expected_json = '<p>My favourite fruit:<br/> Bananas<br/> Apples<br/> Oranges<br/><br/>What are your favourite?</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals(
            $this->fix_newlines($expected_moodle),
            $this->fix_newlines(format_text($text, FORMAT_MOODLE, $options))
        );
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));
    }

    public function test_option_overflowdiv() {
        $text = 'A test of the overflow div system';

        // Test on.
        $options = ['overflowdiv' => true];
        $expected_html = '<div class="no-overflow">A test of the overflow div system</div>';
        $expected_plain = '<div class="no-overflow">A test of the overflow div system</div>';
        $expected_markdown = "<div class=\"no-overflow\"><p>A test of the overflow div system</p>\n</div>";
        $expected_moodle = '<div class="no-overflow"><div class="text_to_html">A test of the overflow div system</div></div>';
        $expected_json = '<div class="no-overflow"><p>A test of the overflow div system</p></div>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        // Test off.
        $options = ['overflowdiv' => false];
        $expected_html = 'A test of the overflow div system';
        $expected_plain = 'A test of the overflow div system';
        $expected_markdown = "<p>A test of the overflow div system</p>\n";
        $expected_moodle = '<div class="text_to_html">A test of the overflow div system</div>';
        $expected_json = '<p>A test of the overflow div system</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        // Test the default is off.
        $options = [];
        $expected_html = 'A test of the overflow div system';
        $expected_plain = 'A test of the overflow div system';
        $expected_markdown = "<p>A test of the overflow div system</p>\n";
        $expected_moodle = '<div class="text_to_html">A test of the overflow div system</div>';
        $expected_json = '<p>A test of the overflow div system</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));
    }

    public function test_option_allowid() {
        $text = 'The <span id="frank">allowid</span> option';

        // Test on.
        $options = ['allowid' => true];
        $expected_html = 'The <span id="frank">allowid</span> option';
        $expected_plain = 'The &lt;span id=&quot;frank&quot;&gt;allowid&lt;/span&gt; option';
        $expected_markdown = "<p>The <span id=\"frank\">allowid</span> option</p>\n";
        $expected_moodle = '<div class="text_to_html">The <span id="frank">allowid</span> option</div>';
        $expected_json = '<p>The &lt;span id=&quot;frank&quot;&gt;allowid&lt;/span&gt; option</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        // Test off.
        $options = ['allowid' => false];
        $expected_html = 'The <span>allowid</span> option';
        $expected_plain = 'The &lt;span id=&quot;frank&quot;&gt;allowid&lt;/span&gt; option';
        $expected_markdown = "<p>The <span>allowid</span> option</p>\n";
        $expected_moodle = '<div class="text_to_html">The <span>allowid</span> option</div>';
        $expected_json = '<p>The &lt;span id=&quot;frank&quot;&gt;allowid&lt;/span&gt; option</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        // Test the default is off.
        $options = [];
        $expected_html = 'The <span>allowid</span> option';
        $expected_plain = 'The &lt;span id=&quot;frank&quot;&gt;allowid&lt;/span&gt; option';
        $expected_markdown = "<p>The <span>allowid</span> option</p>\n";
        $expected_moodle = '<div class="text_to_html">The <span>allowid</span> option</div>';
        $expected_json = '<p>The &lt;span id=&quot;frank&quot;&gt;allowid&lt;/span&gt; option</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));
    }

    public function test_option_blanktarget() {
        // Test on, basic text
        $text = 'Check out my <a href="favourite">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['blanktarget' => true];
        $expected_html = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        $expected_plain = '<p>Check out my &lt;a href="favourite"&gt;favourite course&lt;/a&gt; today</p>';
        $expected_markdown = "<p>Check out my <a href=\"favourite\" target=\"_blank\" rel=\"noreferrer noopener\">favourite course</a> today</p>";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test on, many properties
        $text = 'Check out my <a href="favourite" title="Basic 101" tabindex=0>favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite","title":"Basic 101","tabindex":"0"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['blanktarget' => true];
        $expected_html = '<p>Check out my <a href="favourite" title="Basic 101" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        $expected_plain = '<p>Check out my &lt;a href="favourite" title="Basic 101" tabindex=0&gt;favourite course&lt;/a&gt; today</p>';
        $expected_markdown = "<p>Check out my <a href=\"favourite\" title=\"Basic 101\" target=\"_blank\" rel=\"noreferrer noopener\">favourite course</a> today</p>";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite" title="Basic 101" target="_blank" rel="noreferrer noopener">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test on, already defined but to parent - it changes them to blank :(
        $text = 'Check out my <a href="favourite" target="_parent" rel="nofollow">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite","target":"_parent","rel":"nofollow"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['blanktarget' => true];
        $expected_html = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        $expected_plain = '<p>Check out my &lt;a href="favourite" target="_parent" rel="nofollow"&gt;favourite course&lt;/a&gt; today</p>';
        $expected_markdown = "<p>Check out my <a href=\"favourite\" target=\"_blank\" rel=\"noreferrer noopener\">favourite course</a> today</p>";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test on, already defined to blank
        $text = 'Check out my <a href="favourite" target="_blank" rel="nofollow">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite","target":"_blank","rel":"nofollow"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['blanktarget' => true];
        $expected_html = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        $expected_plain = '<p>Check out my &lt;a href="favourite" target="_blank" rel="nofollow"&gt;favourite course&lt;/a&gt; today</p>';
        $expected_markdown = "<p>Check out my <a href=\"favourite\" target=\"_blank\" rel=\"noreferrer noopener\">favourite course</a> today</p>";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test on, already defined to _parent and allowxss which avoids cleaning.
        $text = 'Check out my <a href="favourite" target="_parent" rel="nofollow">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite","target":"_parent","rel":"nofollow"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['blanktarget' => true, 'allowxss' => true];
        $expected_html = '<p>Check out my <a href="favourite" target="_parent" rel="nofollow">favourite course</a> today</p>';
        $expected_plain = '<p>Check out my &lt;a href="favourite" target="_parent" rel="nofollow"&gt;favourite course&lt;/a&gt; today</p>';
        $expected_markdown = "<p>Check out my <a href=\"favourite\" target=\"_parent\" rel=\"nofollow\">favourite course</a> today</p>";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite" target="_parent" rel="nofollow">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test on, defined only with a rel
        $text = 'Check out my <a href="favourite" rel="nofollow">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite","rel":"nofollow"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['blanktarget' => true];
        $expected_html = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        $expected_plain = '<p>Check out my &lt;a href="favourite" rel="nofollow"&gt;favourite course&lt;/a&gt; today</p>';
        $expected_markdown = "<p>Check out my <a href=\"favourite\" target=\"_blank\" rel=\"noreferrer noopener\">favourite course</a> today</p>";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test on, defined only with a rel and with allowxss which avoids cleaning.
        $text = 'Check out my <a href="favourite" rel="nofollow">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite","rel":"nofollow"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['blanktarget' => true, 'allowxss' => true];
        $expected_html = '<p>Check out my <a href="favourite" rel="nofollow noreferrer noopener" target="_blank">favourite course</a> today</p>';
        $expected_plain = '<p>Check out my &lt;a href="favourite" rel="nofollow"&gt;favourite course&lt;/a&gt; today</p>';
        $expected_markdown = "<p>Check out my <a href=\"favourite\" rel=\"nofollow noreferrer noopener\" target=\"_blank\">favourite course</a> today</p>";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite" rel="nofollow noreferrer noopener" target="_blank">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite" target="_blank" rel="noreferrer noopener">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test it when off
        $text = 'Check out my <a href="favourite" target="_parent" rel="nofollow">favourite course</a> today';
        $json_text = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Check out my "},{"type":"text","marks":[{"type":"link","attrs":{"href":"favourite","target":"_parent","rel":"nofollow"}}],"text":"favourite course"},{"type":"text","text":" today"}]}]}';
        $options = ['blanktarget' => false];
        $expected_html = 'Check out my <a href="favourite">favourite course</a> today';
        $expected_plain = 'Check out my &lt;a href=&quot;favourite&quot; target=&quot;_parent&quot; rel=&quot;nofollow&quot;&gt;favourite course&lt;/a&gt; today';
        $expected_markdown = "<p>Check out my <a href=\"favourite\">favourite course</a> today</p>\n";
        $expected_moodle = '<div class="text_to_html">Check out my <a href="favourite">favourite course</a> today</div>';
        $expected_json = '<p>Check out my <a href="favourite">favourite course</a> today</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($json_text, FORMAT_JSON_EDITOR, $options));

        // Test known problem case if blanktarget is called before clean_text().
        $text = 'I am >super< awesome';
        $options = ['blanktarget' => true];
        $expected_html = '<p>I am &gt;super&lt; awesome</p>';
        $expected_plain = '<p>I am &gt;super&lt; awesome</p>';
        $expected_markdown = "<p>I am &gt;super&lt; awesome</p>";
        $expected_moodle = '<div class="text_to_html">I am &gt;super&lt; awesome</div>';
        $expected_json = '<p>I am &gt;super&lt; awesome</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));

        $text = 'I am >super< awesome';
        $options = ['blanktarget' => false];
        $expected_html = 'I am &gt;super&lt; awesome';
        $expected_plain = 'I am &gt;super&lt; awesome';
        $expected_markdown = "<p>I am &gt;super&lt; awesome</p>\n";
        $expected_moodle = '<div class="text_to_html">I am &gt;super&lt; awesome</div>';
        $expected_json = '<p>I am &gt;super&lt; awesome</p>';
        self::assertEquals($expected_html, format_text($text, FORMAT_HTML, $options));
        self::assertEquals($expected_plain, format_text($text, FORMAT_PLAIN, $options));
        self::assertEquals($expected_markdown, format_text($text, FORMAT_MARKDOWN, $options));
        self::assertEquals($expected_moodle, format_text($text, FORMAT_MOODLE, $options));
        self::assertEquals($expected_json, format_text($this->json_text($text), FORMAT_JSON_EDITOR, $options));
    }

    public function test_option_allowxss() {
        $text = 'I\'m the needle<a onclick="alert(1)">Hack</a>';
        self::assertSame($text, format_text($text, FORMAT_HTML, ['allowxss' => true]));
    }

    public function test_low_version_formatting() {
        global $CFG;
        $CFG->version = 2013051400 - 1;
        filter_set_global_state('emoticon', TEXTFILTER_ON);
        $text = '<p>:-)</p>';
        self::assertSame($text, format_text($text, FORMAT_HTML, ['filter' => true]));
    }

    public function test_pluginfile_debugging() {
        global $CFG;
        $text = '<a href="@@PLUGINFILE@@/test.jpg">Test</a>';
        $expected = '<a href="@@PLUGINFILE@@/test.jpg">Test</a>';

        $CFG->debugdeveloper = 0;
        self::assertSame($expected, format_text($text, FORMAT_HTML));

        $CFG->debugdeveloper = 1;
        self::assertSame($expected, format_text($text, FORMAT_HTML));
        $this->assertDebuggingCalled('Before calling format_text(), the content must be processed with file_rewrite_pluginfile_urls()');
    }

    public function test_with_cleantext_compatible_filters() {
        global $CFG, $DB, $PAGE;

        $file =  $CFG->dirroot . '/filter/multilang/filter.php';
        if (!file_exists($file)) {
            $this->markTestSkipped('The multilang filter is not installed.');
            return;
        }
        require_once($file);

        $context = \context_system::instance();
        $DB->execute('DELETE FROM {filter_active}');
        $DB->insert_record('filter_active', [
            'filter' => 'multilang',
            'contextid' => $context->id,
            'active' => '1',
            'softorder' => '1',
        ]);

        $filtermanager = filter_manager::instance();
        $filtermanager->setup_page_for_filters($PAGE, $context);
        self::assertTrue($filtermanager->result_is_compatible_with_text_cleaning($context));

        // Prep some data.
        $text = '<span lang="en" class="multilang">English</span><span lang="xx" class="multilang">Klingon</span>';
        $filtered = 'English';

        self::assertSame($filtered, format_text($text, FORMAT_HTML, ['context' => $context]));
    }

    public function test_script_tag_persistence() {
        $text = '<div>Test</div><script>alert(1);</script>';

        self::assertSame('<div>Test</div>', format_text($text, FORMAT_HTML));
        self::assertSame(
            $this->fix_newlines('<div>Test</div>'),
            $this->fix_newlines(format_text($text, FORMAT_MARKDOWN))
        );
        self::assertSame('&lt;div&gt;Test&lt;/div&gt;&lt;script&gt;alert(1);&lt;/script&gt;', format_text($text, FORMAT_PLAIN));
        self::assertSame('<div class="text_to_html"><div>Test</div></div>', format_text($text, FORMAT_MOODLE));

        $text = '<div>Test</div><script type="text/javascript">alert(1);</script>';

        self::assertSame('<div>Test</div>', format_text($text, FORMAT_HTML));
        self::assertSame(
            $this->fix_newlines('<div>Test</div>'),
            $this->fix_newlines(format_text($text, FORMAT_MARKDOWN))
        );
        self::assertSame('&lt;div&gt;Test&lt;/div&gt;&lt;script type=&quot;text/javascript&quot;&gt;alert(1);&lt;/script&gt;', format_text($text, FORMAT_PLAIN));
        self::assertSame('<div class="text_to_html"><div>Test</div></div>', format_text($text, FORMAT_MOODLE));

        $text = '<script>alert(1);</script><div>Test</div>';

        self::assertSame('<div>Test</div>', format_text($text, FORMAT_HTML));
        self::assertSame(
            $this->fix_newlines('<div>Test</div>'),
            $this->fix_newlines(format_text($text, FORMAT_MARKDOWN))
        );
        self::assertSame('&lt;script&gt;alert(1);&lt;/script&gt;&lt;div&gt;Test&lt;/div&gt;', format_text($text, FORMAT_PLAIN));
        self::assertSame('<div class="text_to_html"><div>Test</div></div>', format_text($text, FORMAT_MOODLE));

        $text = '<div>Te<script>alert(1);</script>st</div>';

        self::assertSame('<div>Test</div>', format_text($text, FORMAT_HTML));
        self::assertSame(
            $this->fix_newlines('<div>Test</div>'),
            $this->fix_newlines(format_text($text, FORMAT_MARKDOWN))
        );
        self::assertSame('&lt;div&gt;Te&lt;script&gt;alert(1);&lt;/script&gt;st&lt;/div&gt;', format_text($text, FORMAT_PLAIN));
        self::assertSame('<div class="text_to_html"><div>Test</div></div>', format_text($text, FORMAT_MOODLE));
    }

    public function test_style_tag_persistence() {
        $text = '<div>Test</div><style>background-color:red;</style>';

        self::assertSame('<div>Test</div>', format_text($text, FORMAT_HTML));
        self::assertSame(
            $this->fix_newlines('<div>Test</div>'),
            $this->fix_newlines(format_text($text, FORMAT_MARKDOWN))
        );
        self::assertSame('&lt;div&gt;Test&lt;/div&gt;&lt;style&gt;background-color:red;&lt;/style&gt;', format_text($text, FORMAT_PLAIN));
        self::assertSame('<div class="text_to_html"><div>Test</div></div>', format_text($text, FORMAT_MOODLE));

        $text = '<div>Test</div><script type="text/css">alert(1);</script>';

        self::assertSame('<div>Test</div>', format_text($text, FORMAT_HTML));
        self::assertSame(
            $this->fix_newlines('<div>Test</div>'),
            $this->fix_newlines(format_text($text, FORMAT_MARKDOWN))
        );
        self::assertSame('&lt;div&gt;Test&lt;/div&gt;&lt;script type=&quot;text/css&quot;&gt;alert(1);&lt;/script&gt;', format_text($text, FORMAT_PLAIN));
        self::assertSame('<div class="text_to_html"><div>Test</div></div>', format_text($text, FORMAT_MOODLE));

        $text = '<style>background-color:red;</style><div>Test</div>';

        self::assertSame('<div>Test</div>', format_text($text, FORMAT_HTML));
        self::assertSame(
            $this->fix_newlines('<div>Test</div>'),
            $this->fix_newlines(format_text($text, FORMAT_MARKDOWN))
        );
        self::assertSame('&lt;style&gt;background-color:red;&lt;/style&gt;&lt;div&gt;Test&lt;/div&gt;', format_text($text, FORMAT_PLAIN));
        self::assertSame('<div class="text_to_html"><div>Test</div></div>', format_text($text, FORMAT_MOODLE));

        $text = '<div>Te<style>background-color:red;</style>st</div>';

        self::assertSame('<div>Test</div>', format_text($text, FORMAT_HTML));
        self::assertSame(
            $this->fix_newlines('<div>Test</div>'),
            $this->fix_newlines(format_text($text, FORMAT_MARKDOWN))
        );
        self::assertSame('&lt;div&gt;Te&lt;style&gt;background-color:red;&lt;/style&gt;st&lt;/div&gt;', format_text($text, FORMAT_PLAIN));
        self::assertSame('<div class="text_to_html"><div>Test</div></div>', format_text($text, FORMAT_MOODLE));
    }

    /**
     * A test to make sure that
     * @return void
     */
    public function test_format_json_content(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();
        $context = context_user::instance($USER->id);

        $file_record = new stdClass();
        $file_record->itemid = file_get_unused_draft_itemid();
        $file_record->filename = 'file.png';
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->filepath = '/';
        $file_record->contextid = $context->id;

        $fs = get_file_storage();
        $stored_file = $fs->create_file_from_string($file_record, 'some content by bolobala');

        $document = [
            'type' => 'doc',
            'content' => [
                \core\json_editor\node\image::create_raw_node_from_image($stored_file)
            ]
        ];

        $content = json_encode($document);

        // We will skip filter for now, as the file is only draft file.
        $output = format_text($content, FORMAT_JSON_EDITOR, ['filter' => false]);

        $url = moodle_url::make_draftfile_url(
            $file_record->itemid,
            $file_record->filepath,
            $file_record->filename
        );

        $expected = html_writer::div(
            html_writer::empty_tag(
                'img',
                [
                    'src' => $url->out(false),
                    'alt' => '',
                    'class' => 'jsoneditor-image-block__img',
                ]
            ),
            'jsoneditor-image-block'
        );

        $this->assertEquals($expected, $output);
    }

    /**
     * A test to make sure the new configure option 'allowrole' behaves as expected.
     * @return void
     */
    public function test_with_allowrole_config(): void {
        purge_all_caches();
        $text = '<table class="mod-data-default-template" role="presentation"><tbody><tr><th>Test</th></tr></tbody></table>';
        self::assertEquals($text, format_text($text, FORMAT_HTML, ['allowrole' => ['tag' => 'table', 'role' => 1]]));
        self::assertEquals($text, format_text($text, FORMAT_HTML, ['allowrole' => ['tag' => 'table', 'role' => true]]));

        purge_all_caches();
        $text = '<span role="presentation">Test</span>';
        self::assertEquals($text, format_text($text, FORMAT_HTML, ['allowrole' => ['tag' => 'span', 'role' => 1]]));

        purge_all_caches();
        $text = '<table class="mod-data-default-template" role="presentation"><tbody><tr><th>Test</th></tr></tbody></table>';
        $expect_html = '<table class="mod-data-default-template"><tbody><tr><th>Test</th></tr></tbody></table>';
        self::assertEquals($expect_html, format_text($text, FORMAT_HTML));
        self::assertEquals($expect_html, format_text($text, FORMAT_HTML, ['allowrole' => ['tag' => 'table', 'role' => 0]]));

        format_text($text, FORMAT_HTML, ['allowrole' => ['tag' => 'table', 'role' => 1], 'allowxss' => 1]);
        self::assertDebuggingCalled('Option allowrole can not be set with option allowxss');

        format_text($text, FORMAT_HTML, ['allowrole' => ['role' => 1]]);
        self::assertDebuggingCalled('Tag has to be set for option allowrole');
    }
}
