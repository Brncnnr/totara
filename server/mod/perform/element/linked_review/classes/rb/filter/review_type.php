<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\rb\filter;

use performelement_linked_review\content_type_factory;
use mod_perform\rb\filter\perform_filter_type as rb_perform_filter_type;

/**
 * Filter based on selecting multiple review types via a dialog
 */
class review_type extends rb_perform_filter_type {

    // $this->name is 'additional-linked_review_content_type'

    /**
     * @inheritDoc
     */
    protected static function get_modal_title(): array {
        return ['choose_review_type_plural', 'performelement_linked_review'];
    }

    /**
     * @inheritDoc
     */
    protected static function get_modal_css(): string {
        return 'rb-filter-choose-review-type';
    }

    /**
     * @inheritDoc
     */
    public static function get_item_options(): array {
        static $review_types = [];
        if (!empty($review_types)) {
            return $review_types;
        }

        $types = content_type_factory::get_all_enabled();
        foreach ($types as $i => $type) {
            $identifier = $type::get_identifier();
            $review_types[$identifier] = format_string($type::get_display_name());
        }
        asort($review_types);

        return $review_types;
    }
}
