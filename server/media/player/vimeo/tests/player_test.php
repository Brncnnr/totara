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
 * Test classes for handling embedded media.
 *
 * @package media_vimeo
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test script for media embedding.
 *
 * @package media_vimeo
 * @copyright 2016 Marina Glancy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_vimeo_testcase extends advanced_testcase {

    /**
     * Pre-test setup. Preserves $CFG.
     */
    public function setUp(): void {
        parent::setUp();

        // Reset $CFG and $SERVER.

        // Consistent initial setup: all players disabled.
        \core\plugininfo\media::set_enabled_plugins('vimeo');

        // Pretend to be using Firefox browser (must support ogg for tests to work).
        core_useragent::instance(true, 'Mozilla/5.0 (X11; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0 ');
    }

    /**
     * Test that plugin is returned as enabled media plugin.
     */
    public function test_is_installed() {
        $sortorder = \core\plugininfo\media::get_enabled_plugins();
        $this->assertEquals(['vimeo' => 'vimeo'], $sortorder);
    }

    /**
     * Test supported link types
     */
    public function test_supported() {
        $manager = core_media_manager::instance();

        // Format: standard vimeo
        $url = new moodle_url('http://vimeo.com/1176321');
        $embed_content = $manager->embed_url($url);
        $this->assertStringContainsString('</iframe>', $embed_content);
        $this->assertStringContainsString('https://player.vimeo.com/video/1176321', $embed_content);

        // Format: private vimeo
        $url = new moodle_url('http://vimeo.com/1176321/ac42fea33');
        $embed_content = $manager->embed_url($url);
        $this->assertStringContainsString('</iframe>', $embed_content);
        $this->assertStringContainsString('https://player.vimeo.com/video/1176321?h=ac42fea33', $embed_content);
    }

    /**
     * Test embedding without media filter (for example for displaying URL resorce).
     */
    public function test_embed_url() {
        global $CFG;

        $url = new moodle_url('http://vimeo.com/1176321');

        $manager = core_media_manager::instance();
        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true,
        );

        $this->assertTrue($manager->can_embed_url($url, $embedoptions));
        $content = $manager->embed_url($url, 'Test & file', 0, 0, $embedoptions);

        $this->assertMatchesRegularExpression('~mediaplugin_vimeo~', $content);
        $this->assertMatchesRegularExpression('~</iframe>~', $content);
        $this->assertMatchesRegularExpression('~width="' . $CFG->media_default_width . '" height="' .
            $CFG->media_default_height . '"~', $content);

        // Repeat sending the specific size to the manager.
        $content = $manager->embed_url($url, 'New file', 123, 50, $embedoptions);
        $this->assertMatchesRegularExpression('~width="123" height="50"~', $content);
    }

    /**
     * Test that mediaplugin filter replaces a link to the supported file with media tag.
     *
     * filter_mediaplugin is enabled by default.
     */
    public function test_embed_link() {
        global $CFG;
        $url = new moodle_url('http://vimeo.com/1176321');
        $text = html_writer::link($url, 'Watch this one');
        $content = format_text($text, FORMAT_HTML);

        $this->assertMatchesRegularExpression('~mediaplugin_vimeo~', $content);
        $this->assertMatchesRegularExpression('~</iframe>~', $content);
        $this->assertMatchesRegularExpression('~width="' . $CFG->media_default_width . '" height="' .
            $CFG->media_default_height . '"~', $content);
    }

    /**
     * Test that mediaplugin filter adds player code on top of <video> tags.
     *
     * filter_mediaplugin is enabled by default.
     */
    public function test_embed_media() {
        global $CFG;
        $url = new moodle_url('http://vimeo.com/1176321');
        $trackurl = new moodle_url('http://example.org/some_filename.vtt');
        $text = '<video controls="true"><source src="'.$url.'"/>' .
            '<track src="'.$trackurl.'">Unsupported text</video>';
        $content = format_text($text, FORMAT_HTML);

        $this->assertMatchesRegularExpression('~mediaplugin_vimeo~', $content);
        $this->assertMatchesRegularExpression('~</iframe>~', $content);
        $this->assertMatchesRegularExpression('~width="' . $CFG->media_default_width . '" height="' .
            $CFG->media_default_height . '"~', $content);
        // Video tag, unsupported text and tracks are removed.
        $this->assertDoesNotMatchRegularExpression('~</video>~', $content);
        $this->assertDoesNotMatchRegularExpression('~<source\b~', $content);
        $this->assertDoesNotMatchRegularExpression('~Unsupported text~', $content);
        $this->assertDoesNotMatchRegularExpression('~<track\b~i', $content);

        // Video with dimensions and source specified as src attribute without <source> tag.
        $text = '<video controls="true" width="123" height="35" src="'.$url.'">Unsupported text</video>';
        $content = format_text($text, FORMAT_HTML);
        $this->assertMatchesRegularExpression('~mediaplugin_vimeo~', $content);
        $this->assertMatchesRegularExpression('~</iframe>~', $content);
        $this->assertMatchesRegularExpression('~width="123" height="35"~', $content);
    }

    function test_dimensions() {
        global $CFG;

        $default_w = $CFG->media_default_width;
        $default_h = $CFG->media_default_height;

        // no width/height
        $embedcode = '<video controls=""><source src="http://vimeo.com/1176321" type="video/mp4"></video>';
        $this->assertMatchesRegularExpression(
            '~<div class="mediaplugin mediaplugin_vimeo"><div style="max-width: ' . $default_w . 'px;">'.
            '<div class="mediaplugin__iframe_responsive" style="padding-top: ' . (($default_h / $default_w) * 100) . '%">'.
            '<iframe width="' . $default_w . '" height="' . $default_h . '" src="https://player.vimeo.com/[^>]+></iframe></div></div></div>~',
            format_text($embedcode, FORMAT_HTML)
        );

        // width and height
        $embedcode = '<video controls="" width="500" height="200"><source src="http://vimeo.com/1176321" type="video/mp4"></video>';
        $this->assertMatchesRegularExpression(
            '~<div class="mediaplugin mediaplugin_vimeo"><div style="max-width: 500px;">'.
            '<div class="mediaplugin__iframe_responsive" style="padding-top: 40%">'.
            '<iframe width="500" height="200" src="https://player.vimeo.com/[^>]+></iframe></div></div></div>~',
            format_text($embedcode, FORMAT_HTML)
        );

        // percentage width
        $embedcode = '<video controls="" width="100%"><source src="http://vimeo.com/1176321" type="video/mp4"></video>';
        $this->assertMatchesRegularExpression(
            '~<div class="mediaplugin mediaplugin_vimeo"><div style="max-width: 100%;">'.
            '<div class="mediaplugin__iframe_responsive" style="padding-top: ' . (($default_h / $default_w) * 100) . '%">'.
            '<iframe width="' . $default_w . '" height="' . $default_h . '" src="https://player.vimeo.com/[^>]+></iframe></div></div></div>~',
            format_text($embedcode, FORMAT_HTML)
        );

        // percentage width + invalid height
        $embedcode = '<video controls="" width="100%" height="auto"><source src="http://vimeo.com/1176321" type="video/mp4"></video>';
        $this->assertMatchesRegularExpression(
            '~<div class="mediaplugin mediaplugin_vimeo"><div style="max-width: 100%;">'.
            '<div class="mediaplugin__iframe_responsive" style="padding-top: ' . (($default_h / $default_w) * 100) . '%">'.
            '<iframe width="' . $default_w . '" height="' . $default_h . '" src="https://player.vimeo.com/[^>]+></iframe></div></div></div>~',
            format_text($embedcode, FORMAT_HTML)
        );

        // data-grow
        $embedcode = '<video controls="" width="500" height="200" data-grow><source src="http://vimeo.com/1176321" type="video/mp4"></video>';
        $this->assertMatchesRegularExpression(
            '~<div class="mediaplugin mediaplugin_vimeo"><div class="mediaplugin_grow_limit">'.
            '<div class="mediaplugin__iframe_responsive" style="padding-top: 40%">'.
            '<iframe width="500" height="200" src="https://player.vimeo.com/[^>]+></iframe></div></div></div>~',
            format_text($embedcode, FORMAT_HTML)
        );
    }
}
