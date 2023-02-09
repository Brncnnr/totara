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
use contentmarketplace_linkedin\entity\learning_object;
use core\orm\collection;
use core_collator;
use html_writer;
use moodle_url;
use totara_notification\placeholder\abstraction\single_placeholder;
use totara_notification\placeholder\option;

class learning_object_list implements single_placeholder {
    /**
     * @var collection
     */
    private $learning_objects;

    /**
     * learning_object_list constructor.
     * @param collection $learning_objects A collection of {@see learning_object} type
     */
    public function __construct(collection $learning_objects) {
        $this->learning_objects = $learning_objects;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            // Note that this title_list placeholder key will be deprecated once CN system support
            // looping with placeholder(s).
            option::create('titles_list', get_string('learning_object_titles', 'contentmarketplace_linkedin')),
            option::create('catalog_import_link', get_string("page_link_placeholder", "contentmarketplace_linkedin"))
        ];
    }

    /**
     * The keys that we are rendering to html are:
     * + titles_list
     * + catalog_import_link
     *
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        $keys = ["titles_list", "catalog_import_link"];
        return in_array($key, $keys, true);
    }

    /**
     * @param string $key
     * @return string
     */
    public function get(string $key): string {
        switch ($key) {
            case 'titles_list':
                $titles = $this->learning_objects->pluck('title');
                core_collator::asort($titles, core_collator::SORT_NATURAL);
                return implode(
                    '<br/>',
                    array_map(
                        function (string $title): string {
                            return get_string(
                                "learning_object_title_list_item",
                                "contentmarketplace_linkedin",
                                format_string($title)
                            );
                        },
                        $titles
                    )
                );

            case 'catalog_import_link':
                return html_writer::link(
                    new moodle_url("/totara/contentmarketplace/explorer.php", ["marketplace" => "linkedin"]),
                    get_string("catalog_title", "contentmarketplace_linkedin")
                );

            default:
                throw new coding_exception("Invalid key {$key}");
        }
    }
}