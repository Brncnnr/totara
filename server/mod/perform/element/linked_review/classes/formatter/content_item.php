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

namespace performelement_linked_review\formatter;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use performelement_linked_review\models\linked_review_content;

/**
 * Formatter for a content item
 *
 * @property linked_review_content $object
 */
class content_item extends entity_model_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'content_id' => null,
            'content' => function ($value) {
                return json_encode($value);
            },
            'meta_data' => null,
            'selector_id' => null,
            'selector' => null,
            'created_at' => date_field_formatter::class,
        ];
    }
}
