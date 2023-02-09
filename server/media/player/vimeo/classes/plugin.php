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
 * Main class for plugin 'media_vimeo'
 *
 * @package   media_vimeo
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Player that embeds Vimeo links.
 *
 * @package   media_vimeo
 * @copyright 2016 Marina Glancy
 * @author    2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class media_vimeo_plugin extends core_media_player_external {

    protected function embed_external(moodle_url $url, $name, $width, $height, $options) {
        $video_id = $this->matches[1];
        $info = trim($name ?? '');
        if (empty($info) or strpos($info, 'http') === 0) {
            $info = get_string('pluginname', 'media_vimeo');
        }

        // If there's another extension send this through as well
        if (!empty($this->matches[3])) {
            $private_id = $this->matches[3];

            $iframe_url = "https://player.vimeo.com/video/$video_id?h=$private_id";
        } else {
            $iframe_url = "https://player.vimeo.com/video/$video_id";
        }

        // Note: resizing via url is not supported, user can click the fullscreen
        // button instead. iframe embedding is not xhtml strict but it is the only
        // option that seems to work on most devices.
        self::pick_video_size($width, $height);

        $grow = !empty($options[core_media_manager::OPTION_GROW]);

        $content = $this->responsive_iframe($iframe_url, $width, $height, $info);

        $content = html_writer::tag('div', $content, [
            'class' => $grow ? 'mediaplugin_grow_limit' : null,
            'style' => $grow ? null : 'max-width: ' . $this->dimension_to_css($width) . ';',
        ]);

        return html_writer::tag('div', $content, [
            'class' => 'mediaplugin mediaplugin_vimeo',
        ]);
    }

    /**
     * Returns regular expression to match vimeo URLs.
     * @return string
     */
    protected function get_regex() {
        // Initial part of link.
        $start = '~^https?://vimeo\.com/';
        // Middle bit: either watch?v= or v/.
        $middle = '([0-9]+)';
        // Could be a private url with another hash at the end
        $end = '(\/([a-zA-Z0-9]*))?';
        return $start . $middle . $end . core_media_player_external::END_LINK_REGEX_PART;
    }

    public function get_embeddable_markers() {
        return array('vimeo.com/');
    }

    /**
     * Default rank
     * @return int
     */
    public function get_rank() {
        return 1010;
    }
}
