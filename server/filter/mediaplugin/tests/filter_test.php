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
 * Unit test for the filter_mediaplugin
 *
 * @package    filter_mediaplugin
 * @category   phpunit
 * @copyright  2011 Rossiani Wijaya <rwijaya@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/mediaplugin/filter.php'); // Include the code to test

/**
 * Class filter_mediaplugin_testcase
 *
 * @group filter
 */
class filter_mediaplugin_testcase extends advanced_testcase {

    function test_filter_mediaplugin_link() {
        global $CFG;
        
        $default_w = $CFG->media_default_width;
        $default_h = $CFG->media_default_height;

        $context = \context_system::instance();

        // we need to enable the plugins somehow
        \core\plugininfo\media::set_enabled_plugins('vimeo,youtube,videojs,html5video,html5audio');

        $filterplugin = new filter_mediaplugin($context, array());

        $longurl = '<a href="http://moodle/.mp4">my test file</a>';
        $longhref = '';

        do {
            $longhref .= 'a';
        } while(strlen($longhref) + strlen($longurl) < 4095);

        $longurl = '<a href="http://moodle/' . $longhref . '.mp4">my test file</a>';

        $validtexts = array (
            '<a href="http://moodle.org/testfile/test.mp3">test mp3</a>',
            '<a href="http://moodle.org/testfile/test.ogg">test ogg</a>',
            '<a id="movie player" class="center" href="http://moodle.org/testfile/test.mp4">test mp4</a>',
            '<a href="http://moodle.org/testfile/test.webm">test</a>',
            '<a href="http://www.youtube.com/watch?v=JghQgA2HMX8" class="href=css">test file</a>',
            '<a href="http://www.youtube-nocookie.com/watch?v=JghQgA2HMX8" class="href=css">test file</a>',
            '<a href="http://youtu.be/JghQgA2HMX8" class="href=css">test file</a>',
            '<a href="http://y2u.be/JghQgA2HMX8" class="href=css">test file</a>',
            '<a class="youtube" href="http://www.youtube.com/watch?v=JghQgA2HMX8">test file</a>',
            '<a class="hrefcss" href="http://www.youtube.com/watch?v=JghQgA2HMX8">test file</a>',
            '<a  class="content"     href="http://moodle.org/testfile/test.ogg">test ogg</a>',
            '<a     id="audio"      href="http://moodle.org/testfile/test.mp3">test mp3</a>',
            '<a  href="http://moodle.org/testfile/test.mp3">test mp3</a>',
            '<a     href="http://moodle.org/testfile/test.mp3">test mp3</a>',
            '<a     href="http://www.youtube.com/watch?v=JghQgA2HMX8?d=200x200">youtube\'s</a>',
            '<a
                            href="http://moodle.org/testfile/test.mp3">
                            test mp3</a>',
            '<a                         class="content"


                            href="http://moodle.org/testfile/test.wav">test wav
                                    </a>',
            '<a             href="http://www.youtube.com/watch?v=JghQgA2HMX8?d=200x200"     >youtube\'s</a>',
            // Test a long URL under 4096 characters.
            $longurl
        );

        //test for valid link
        foreach ($validtexts as $text) {
            $msg = "Testing text: ". $text;
            $filter = $filterplugin->filter($text);
            $this->assertNotEquals($text, $filter, $msg);
        }

        $insertpoint = strrpos($longurl, 'http://');
        $longurl = substr_replace($longurl, 'http://pushover4096chars', $insertpoint, 0);

        $originalurl = '<p>Some text.</p><pre style="color: rgb(0, 0, 0); line-height: normal;">' .
            '<a href="https://www.youtube.com/watch?v=uUhWl9Lm3OM">Valid link</a></pre><pre style="color: rgb(0, 0, 0); line-height: normal;">';
        $paddedurl = str_pad($originalurl, 6000, 'z');
        $validpaddedurl = '<p>Some text.</p><pre style="color: rgb(0, 0, 0); line-height: normal;"><div class="mediaplugin mediaplugin_youtube"><div style="max-width: ' . $default_w . 'px;">' . 
            '<div class="mediaplugin__iframe_responsive" style="padding-top: ' . (($default_h / $default_w) * 100) . '%"><iframe width="' . $default_w . '" height="' . $default_h . '" src="https://www.youtube.com/embed/uUhWl9Lm3OM?rel=0&amp;wmode=transparent" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="1" title="Valid link"></iframe></div>' .
            '</div></div></pre><pre style="color: rgb(0, 0, 0); line-height: normal;">';
        $validpaddedurl = str_pad($validpaddedurl, 6000 + (strlen($validpaddedurl) - strlen($originalurl)), 'z');

        $invalidtexts = array(
            '<a class="_blanktarget">href="http://moodle.org/testfile/test.mp3"</a>',
            '<a>test test</a>',
            '<a >test test</a>',
            '<a     >test test</a>',
            '<a >test test</a>',
            '<ahref="http://moodle.org/testfile/test.mp3">sample</a>',
            '<a href="" test></a>',
            '<a href="http://www.moodle.com/path/to?#param=29">test</a>',
            '<a href="http://moodle.org/testfile/test.mp3">test mp3',
            '<a href="http://moodle.org/testfile/test.mp3"test</a>',
            '<a href="http://moodle.org/testfile/">test</a>',
            '<href="http://moodle.org/testfile/test.avi">test</a>',
            '<abbr href="http://moodle.org/testfile/test.mp3">test mp3</abbr>',
            '<ahref="http://moodle.org/testfile/test.mp3">test mp3</a>',
            '<aclass="content" href="http://moodle.org/testfile/test.mp3">test mp3</a>',
            '<a class="_blanktarget" href="http://moodle.org/testfile/test.flv?d=100x100">test flv</a>',
            // Test a long URL over 4096 characters.
            $longurl
        );

        //test for invalid link
        foreach ($invalidtexts as $text) {
            $msg = "Testing text: ". $text;
            $filter = $filterplugin->filter($text);
            $this->assertEquals($text, $filter, $msg);
        }

        // Valid mediaurl followed by a longurl.
        $precededlongurl = '<a href="http://moodle.org/testfile/test.mp3">test.mp3</a>'. $longurl;
        $filter = $filterplugin->filter($precededlongurl);
        $this->assertEquals(1, substr_count($filter, '</audio>'));
        $this->assertStringContainsString($longurl, $filter);

        // Testing for cases where: to be filtered content has 6+ text afterwards.
        $filter = $filterplugin->filter($paddedurl);
        $this->assertEquals($validpaddedurl, $filter, $msg);
    }

    /**
     * Returns true is text can be cleaned using clean text AFTER having been filtered.
     *
     * If false is returned then this filter must be run after clean text has been run.
     * If null is returned then the filter has not yet been updated by a developer to answer the question.
     * This should be done as a priority.
     *
     * @since Totara 13.0
     * @return bool
     */
    protected static function is_compatible_with_clean_text() {
        return false;
    }
}
