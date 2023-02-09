<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use performelement_linked_review\formatter\content_item as content_item_formatter;
use performelement_linked_review\models\linked_review_content;

class content_item extends type_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof linked_review_content) {
            throw new coding_exception('The given type must be an instance of ' . linked_review_content::class);
        }

        $format = $args['format'] ?? null;
        $context = $ec->get_relevant_context();
        $formatter = new content_item_formatter($source, $context);

        return $formatter->format($field, $format);
    }

}
