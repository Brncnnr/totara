<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\totara_notification\placeholder;

use coding_exception;
use html_writer;
use moodle_url;
use totara_notification\placeholder\abstraction\single_placeholder;
use totara_notification\placeholder\option;

class catalog_page implements single_placeholder {
    /**
     * @return option[]
     * @throws coding_exception
     */
    public static function get_options(): array {
        return [
            option::create("page_link_placeholder", get_string("page_link_placeholder", "contentmarketplace_linkedin"))
        ];
    }

    /**
     * The keys that we are rendering to html are:
     * + page_link_placeholder
     *
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        return "page_link_placeholder" === $key;
    }

    /**
     * @param string $key
     * @return string
     */
    public function get(string $key): string {
        switch ($key) {
            case "page_link_placeholder":
                return html_writer::link(
                    new moodle_url("/totara/contentmarketplace/explorer.php", ['marketplace' => 'linkedin']),
                    get_string("catalog_title", "contentmarketplace_linkedin")
                );

            default:
                throw new coding_exception("Invalid key {$key}");
        }
    }
}