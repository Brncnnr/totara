<?php
/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

namespace totara_api\views;

use core\output\notification;
use GraphQL\Utils\SchemaPrinter;
use totara_mvc\view;
use totara_webapi\endpoint_type\external;
use totara_webapi\graphql;

class documentation_view extends view {

    protected const BUILD_DIRECTORY =  '/../extensions/api_docs/build/spectaql';

    /**
     * Returns the well-known locations of the built asset files for external GraphQL API documentation.
     *
     * @return array|string[] Path to out.html and out.json as an array
     */
    public static function built_asset_files(): array {
        global $CFG;
        $docs_file = $CFG->dirroot . self::BUILD_DIRECTORY . '/out.html';
        $docs_metadata = $CFG->dirroot . self::BUILD_DIRECTORY . '/out.json';
        if (!is_readable($docs_file) || !is_readable($docs_metadata)) {
            return [];
        } else {
            return ['file' => $docs_file, 'meta' => $docs_metadata];
        }
    }

    /**
     * Loads the built assets, parses them into HTML, CSS, and JS for output, and replaces dummy URLs in the HTML.
     *
     * @param array|\stdClass|string $output
     * @return array|\stdClass|string
     */
    public function prepare_output($output) {
        global $CFG, $OUTPUT;

        $url = new \moodle_url('/totara/api/');
        if (!empty($this->data['tenant_id'])) {
            $url->param('tenant_id', $this->data['tenant_id']);
        }
        $output['return_url'] = $url;

        $browser_support = new notification(get_string('error_documentation_browser_support', 'totara_api'), notification::NOTIFY_ERROR);
        $output['browser_error_html'] = addslashes_js($OUTPUT->render($browser_support));

        $docs_built = self::built_asset_files();
        if (empty($docs_built)) {
            $docs_not_found = new notification(get_string('error_documentation_not_found', 'totara_api'), notification::NOTIFY_ERROR);
            $output['docs_not_found'] = $docs_not_found->export_for_template($OUTPUT);
            return $output;
        }

        $docs_file = $docs_built['file'];
        $docs_metadata = $docs_built['meta'];

        // TODO check it was json
        $build_metadata = json_decode(file_get_contents($docs_metadata));
        $docs_hash = $build_metadata->schemahash;

        // Just external for now - could support multiple types via a parameter in future.
        $schema = graphql::get_schema(new external());
        $schema = SchemaPrinter::doPrint($schema);
        $schema_hash = sha1($schema);

        // TODO probably need to consider other files too, specifically guides/*.md and config.yml.
        $output['docs_match'] = $docs_hash == $schema_hash;
        if (!$output['docs_match']) {
            $docs_mismatch = new notification(get_string('error_documentation_schema_changed', 'totara_api'), notification::NOTIFY_WARNING);
            $output['docs_mismatch'] = $docs_mismatch->export_for_template($OUTPUT);
        }

        $docs = file_get_contents($docs_file);

        $matches = null;
        // Multi-line regex to split CSS, JS and HTML
        $match_count = preg_match_all('|<style>(.*)</style>.*<script>(.*)</script>(.*)|sim', $docs, $matches, PREG_PATTERN_ORDER);
        // Check contents of docs file matches expected pattern.
        $docs_parsed = (!empty($match_count) && !empty($matches[1][0]) && !empty($matches[2][0]) && !empty($matches[3][0]));
        if (!$docs_parsed) {
            $docs_parse_error = new notification(get_string('error_documentation_parse_error', 'totara_api'), notification::NOTIFY_ERROR);
            $output['docs_parse_error'] = $docs_parse_error->export_for_template($OUTPUT);
        } else {
            // Find/replace site URL so actual wwwroot is shown.
            $docs_html = addslashes_js(str_replace('https://YOUR-SITE-URL', $CFG->wwwroot, $matches[3][0]));
            $docs_js = addslashes_js($matches[2][0]);
            $docs_css = addslashes_js($matches[1][0]);

            $output['docs_html'] = $docs_html;
            $output['docs_js'] = $docs_js;
            $output['docs_css'] = $docs_css;
        }

        return $output;
    }
}
