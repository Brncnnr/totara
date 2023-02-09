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
 * @package    filter
 * @subpackage multilang
 * @copyright  Gaetan Frenoy <gaetan@frenoy.net>
 * @copyright  2004 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\collection;
use core\json_editor\document;
use jsoneditor_simple_multi_lang\json_editor\node\lang_blocks;

// Given XML multilinguage text, return relevant text according to
// current language:
//   - look for multilang blocks in the text.
//   - if there exists texts in the currently active language, print them.
//   - else, if there exists texts in the current parent language, print them.
//   - else, print the first language in the text.
// Please note that English texts are not used as default anymore!
//
// This version is based on original multilang filter by Gaetan Frenoy,
// rewritten by Eloy and skodak.
//
// Following new syntax is not compatible with old one:
//   <span lang="XX" class="multilang">one lang</span><span lang="YY" class="multilang">another language</span>

class filter_multilang extends moodle_text_filter {
    function filter($text, array $options = array()) {
        global $CFG;

        // [pj] I don't know about you but I find this new implementation funny :P
        // [skodak] I was laughing while rewriting it ;-)
        // [nicolasconnault] Should support inverted attributes: <span class="multilang" lang="en"> (Doesn't work curently)
        // [skodak] it supports it now, though it is slower - any better idea?

        if (empty($text) or is_numeric($text)) {
            return $text;
        }

        if (empty($CFG->filter_multilang_force_old) and !empty($CFG->filter_multilang_converted)) {
            // new syntax
            $search = '/(<span(\s+lang="[a-zA-Z0-9_-]+"|\s+class="multilang"){2}\s*>.*?<\/span>)(\s*<span(\s+lang="[a-zA-Z0-9_-]+"|\s+class="multilang"){2}\s*>.*?<\/span>)+/is';
        } else {
            // old syntax
            $search = '/(<(?:lang|span) lang="[a-zA-Z0-9_-]*".*?>.*?<\/(?:lang|span)>)(\s*<(?:lang|span) lang="[a-zA-Z0-9_-]*".*?>.*?<\/(?:lang|span)>)+/is';
        }

        $result = preg_replace_callback($search, 'filter_multilang_impl', $text);

        if (is_null($result)) {
            return $text; //error during regex processing (too many nested spans?)
        } else {
            return $result;
        }
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
        return true; // Removes tags, doesn't add any content.
    }

    /**
     * For a reason that sometimes the multi lang block can have the placeholder inline node in it
     * which result the span tag within another span tag when rendered.
     *
     * Hence we are going to filter it from JSON text itself.
     *
     * @return bool
     */
    public function support_json_content(): bool {
        return class_exists('jsoneditor_simple_multi_lang\\json_editor\\node\\lang_blocks');
    }

    /**
     * @param string $json_text
     * @return string
     */
    public function filter_json(string $json_text): string {
        if (!class_exists('jsoneditor_simple_multi_lang\\json_editor\\node\\lang_blocks')) {
            // Nothing to filter here, because lang block collection does not exist.
            return $json_text;
        }

        // Optimisation: early return if node is not present
        if (strpos($json_text, lang_blocks::get_type()) === false) {
            return $json_text;
        }

        $document = document::create($json_text);
        $current_lang = current_language();

        // Strip lang_blocks wrapping and replace with the content of the matching language block.
        $document->modify_node(
            lang_blocks::get_type(),
            function (array $raw_node) use ($current_lang): ?array {
                $nodes = collection::new($raw_node['content']);

                // Find the block that matches the current language.
                $matching_block = $nodes->find(function (array $block_node) use ($current_lang): bool {
                    return isset($block_node['attrs']['lang']) && $block_node['attrs']['lang'] === $current_lang;
                });

                // If there's no matching block, default to the first node.
                $block = $matching_block ?? $nodes->first();

                if (!$block) {
                    // No content at all, just delete the node
                    return null;
                }

                // Strip wrapping. This will return an array of multiple nodes.
                return $block['content'];
            }
        );

        return $document->to_json();
    }
}

function filter_multilang_impl($langblock) {
    global $CFG;

    $mylang = current_language();
    static $parentcache;
    if (!isset($parentcache)) {
        $parentcache = array();
    }
    if (!array_key_exists($mylang, $parentcache)) {
        $parentlang = get_parent_language($mylang);
        $parentcache[$mylang] = $parentlang;
    } else {
        $parentlang = $parentcache[$mylang];
    }

    $searchtosplit = '/<(?:lang|span)[^>]+lang="([a-zA-Z0-9_-]+)"[^>]*>(.*?)<\/(?:lang|span)>/is';

    if (!preg_match_all($searchtosplit, $langblock[0], $rawlanglist)) {
        //skip malformed blocks
        return $langblock[0];
    }

    $langlist = array();
    foreach ($rawlanglist[1] as $index=>$lang) {
        $lang = str_replace('-','_',strtolower($lang)); // normalize languages
        $langlist[$lang] = $rawlanglist[2][$index];
    }

    if (array_key_exists($mylang, $langlist)) {
        return $langlist[$mylang];
    } else if (array_key_exists($parentlang, $langlist)) {
        return $langlist[$parentlang];
    } else {
        $first = array_shift($langlist);
        return $first;
    }
}


