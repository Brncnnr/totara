<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */
namespace totara_core\hashtag;

/**
 * A model for hashtag.
 */
final class hashtag {
    /**
     * @var \core_tag_tag
     */
    private $tag;

    /**
     * hashtag constructor.
     * @param \core_tag_tag $tag
     */
    public function __construct(\core_tag_tag $tag) {
        $this->tag = $tag;
    }

    /**
     * @param string $value
     * @return hashtag
     */
    public static function create(string $value): hashtag {
        global $CFG;
        if (!property_exists($CFG, 'hashtag_collection_id')) {
            throw new \coding_exception("Cannot find the hashtag collection id");
        }

        $collection_id = $CFG->hashtag_collection_id;

        $tags = \core_tag_tag::create_if_missing($collection_id, [$value], true);
        $tag = reset($tags);

        return new static($tag);
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->tag->id;
    }

    /**
     * @param bool $as_html
     * @return string
     */
    public function get_display_name(bool $as_html = false): string {
        return $this->tag->get_display_name($as_html);
    }
}