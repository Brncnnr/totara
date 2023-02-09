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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\formatter;

use core\webapi\formatter\field\base;
use mod_perform\formatter\response\element_response_formatter;
use mod_perform\models\activity\element;

/**
 * Formats user entered responses for all child elements.
 */
class response_formatter extends element_response_formatter {

    use response_formatter_trait;

    /**
     * {@inheritdoc}
     */
    protected function get_default_format($value) {
        return $this->format_value($value);
    }

    /**
     * @inheritDoc
     */
    private function get_child_element_formatter(element $element): base {
        return element_response_formatter::get_instance($element, $this->format);
    }
}
