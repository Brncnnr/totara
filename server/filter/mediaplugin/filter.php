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
 *  Media plugin filtering
 *
 *  This filter will replace any links to a media file with
 *  a media plugin that plays that media inline
 *
 * @package    filter
 * @subpackage mediaplugin
 * @copyright  2004 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Automatic media embedding filter class.
 *
 * It is highly recommended to configure servers to be compatible with our slasharguments,
 * otherwise the "?d=600x400" may not work.
 *
 * @package    filter
 * @subpackage mediaplugin
 * @copyright  2004 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_mediaplugin extends moodle_text_filter {
    /** @var bool True if currently filtering trusted text */
    private $trusted;

    /**
     * Setup page with filter requirements and other prepare stuff.
     *
     * @param moodle_page $page The page we are going to add requirements to.
     * @param context $context The context which contents are going to be filtered.
     */
    public function setup($page, $context) {
        // This only requires execution once per request.
        static $jsinitialised = false;
        if ($jsinitialised) {
            return;
        }
        $jsinitialised = true;

        // Set up the media manager so that media plugins requiring JS are initialised.
        $mediamanager = core_media_manager::instance($page);
    }

    public function filter($text, array $options = array()) {
        global $CFG, $PAGE;

        if (!is_string($text) or empty($text)) {
            // non string data can not be filtered anyway
            return $text;
        }

        if (stripos($text, '</a>') === false && stripos($text, '</video>') === false && stripos($text, '</audio>') === false) {
            // Performance shortcut - if there are no </a>, </video> or </audio> tags, nothing can match.
            return $text;
        }

        if (isset($options['noclean'])) {
            debugging('The noclean option is no longer available. Please set allowxss for system cases, or use the capabilities.', DEBUG_DEVELOPER);
            $options['allowxss'] = (bool) $options['noclean'];
        }

        // Looking for tags.
        $matches = preg_split('/(<[^>]*>)/i', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if (!$matches) {
            return $text;
        }

        // Check if the user is trusted to embed content.
        // This is given if the text allows XSS, if the site has allowed anyone to embed objects, or if the user holds the embedobject
        // capability at the given context.
        $this->trusted = !empty($options['allowxss']);

        // Regex to find media extensions in an <a> tag.
        $embedmarkers = core_media_manager::instance()->get_embeddable_markers();
        $re = '~<a\s[^>]*href="([^"]*(?:' .  $embedmarkers . ')[^"]*)"[^>]*>([^>]*)</a>~is';

        $newtext = '';
        $validtag = '';
        $tagname = '';
        $sizeofmatches = count($matches);

        // We iterate through the given string to find valid <a> tags
        // and build them so that the callback function can check it for
        // embedded content. Then we rebuild the string.
        foreach ($matches as $idx => $tag) {
            if (preg_match('|</'.$tagname.'>|', $tag) && !empty($validtag)) {
                $validtag .= $tag;

                // Given we now have a valid <a> tag to process it's time for
                // ReDoS protection. Stop processing if a word is too large.
                if (strlen($validtag) < 4096) {
                    if ($tagname === 'a') {
                        $processed = preg_replace_callback($re, array($this, 'callback'), $validtag);
                    } else {
                        // For audio and video tags we just process them without precheck for embeddable markers.
                        $processed = $this->process_media_tag($validtag);
                    }
                }
                // Rebuilding the string with our new processed text.
                $newtext .= !empty($processed) ? $processed : $validtag;
                // Wipe it so we can catch any more instances to filter.
                $validtag = '';
                $processed = '';
            } else if (preg_match('/<(a|video|audio)\s[^>]*/', $tag, $tagmatches) && $sizeofmatches > 1 &&
                    (empty($validtag) || $tagname === strtolower($tagmatches[1]))) {
                // Looking for a starting tag. Ignore tags embedded into each other.
                $validtag = $tag;
                $tagname = strtolower($tagmatches[1]);
            } else {
                // If we have a validtag add to that to process later,
                // else add straight onto our newtext string.
                if (!empty($validtag)) {
                    $validtag .= $tag;
                } else {
                    $newtext .= $tag;
                }
            }
        }

        // Return the same string except processed by the above.
        return $newtext;
    }

    /**
     * Replace link with embedded content, if supported.
     *
     * @param array $matches
     * @return string
     */
    private function callback(array $matches) {
        $mediamanager = core_media_manager::instance();

        global $CFG, $PAGE;
        // Check if we ignore it.
        if (preg_match('/class="[^"]*nomediaplugin/i', $matches[0])) {
            return $matches[0];
        }

        // Get name.
        $name = trim($matches[2]);
        if (empty($name) or strpos($name, 'http') === 0) {
            $name = ''; // Use default name.
        }

        // Split provided URL into alternatives.
        $urls = $mediamanager->split_alternatives($matches[1], $width, $height);

        $options = [core_media_manager::OPTION_ORIGINAL_TEXT => $matches[0]];
        return $this->embed_alternatives($urls, $name, $width, $height, $options);
    }

    /**
     * Renders media files (audio or video) using suitable embedded player.
     *
     * Wrapper for {@link core_media_manager::embed_alternatives()}
     *
     * @param array $urls Array of moodle_url to media files
     * @param string $name Optional user-readable name to display in download link
     * @param int $width Width in pixels (optional)
     * @param int $height Height in pixels (optional)
     * @param array $options Array of key/value pairs
     * @return string HTML content of embed
     */
    protected function embed_alternatives($urls, $name, $width, $height, $options) {

        // Allow SWF (or not).
        if ($this->trusted) {
            $options[core_media_manager::OPTION_TRUSTED] = true;
        }

        // We could test whether embed is possible using can_embed, but to save
        // time, let's just embed it with the 'fallback to blank' option which
        // does most of the same stuff anyhow.
        $options[core_media_manager::OPTION_FALLBACK_TO_BLANK] = true;

        // NOTE: Options are not passed through from filter because the 'embed'
        // code does not recognise filter options (it's a different kind of
        // option-space) as it can be used in non-filter situations.
        $result = core_media_manager::instance()->embed_alternatives($urls, $name, $width, $height, $options);

        // If something was embedded, return it, otherwise return original.
        if ($result !== '') {
            return $result;
        } else {
            return $options[core_media_manager::OPTION_ORIGINAL_TEXT];
        }
    }

    /**
     * Replaces <video> or <audio> tag with processed contents
     *
     * @param string $fulltext complete HTML snipped "<video ...>...</video>" or "<audio ...>....</audio>"
     * @return string
     */
    protected function process_media_tag($fulltext) {
        // Check if we ignore it.
        if (preg_match('/^<[^>]*class="[^"]*nomediaplugin/im', $fulltext)) {
            return $fulltext;
        }

        // Find all sources both as <video src=""> and as embedded <source> tags.
        $urls = [];
        if (preg_match('/^<[^>]*\bsrc="(.*?)"/im', $fulltext, $matches)) {
            $urls[] = new moodle_url($matches[1]);
        }
        if (preg_match_all('/<source\b[^>]*\bsrc="(.*?)"/im', $fulltext, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = new moodle_url($url);
            }
        }
        // Extract width/height/title attributes and call embed_alternatives to find a suitable media player.
        if ($urls) {
            $options = [core_media_manager::OPTION_ORIGINAL_TEXT => $fulltext];
            $width = $this->parse_dimension(core_media_player_native::get_attribute($fulltext, 'width', PARAM_RAW));
            $height = $this->parse_dimension(core_media_player_native::get_attribute($fulltext, 'height', PARAM_RAW));
            $name = core_media_player_native::get_attribute($fulltext, 'title');
            if (core_media_player_native::get_attribute($fulltext, 'data-grow') !== null) {
                $options[core_media_manager::OPTION_GROW] = true;
            }
            return $this->embed_alternatives($urls, $name, $width, $height, $options);
        }
        return $fulltext;
    }

    /**
     * Sanitize and parse dimension.
     *
     * Supported formats: 100, 100px, 100%
     * Other formats will be treated as px for backwards compatibility
     *
     * @param string Input dimension
     * @return int|string|null Int for px, string for percentage or other CSS value, or null if invalid.
     */
    private function parse_dimension($dimension) {
        if (!$dimension) {
            return null;
        }

        $dimension = trim($dimension);

        // pixels
        if (preg_match('/^(\d+)(?:px)?$/', $dimension, $matches)) {
            return (int)$matches[1];
        }

        // percentage (non-standard on video/audio elements, but supported by all browsers)
        if (preg_match('/^\d+(?:\.\d+)?%$/', $dimension)) {
            return $dimension;
        }
        
        // leading numeric -- keep pre Totara 13.6 behavior and treat as a px value
        if (!preg_match('/^\d/', $dimension)) {
            return (int)$dimension;
        }

        return null;
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
        return false; // Tags would be stripped by cleaning.
    }
}
