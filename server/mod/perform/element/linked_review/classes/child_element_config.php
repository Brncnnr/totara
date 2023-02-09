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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review;

use mod_perform\models\activity\helpers\child_element_config as base_child_element_config;

/**
 * Child element configuration for linked review.
 *
 * @package performelement_linked_review
 */
class child_element_config extends base_child_element_config {

    /**
     * @inheritDoc
     */
    public function get_supports_child_elements(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get_supports_repeating_child_elements(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get_repeating_item_identifier(): string {
        return 'contentItemResponses';
    }

}