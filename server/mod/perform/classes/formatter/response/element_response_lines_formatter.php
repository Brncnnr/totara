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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter\response;

use core\webapi\formatter\field\base;

/**
 * Generic element response lines formatter.
 *
 * Create the formatter with ::for_element(); this ensures the incoming response
 * data is formatted properly for a given element.
 *
 * @see \mod_perform\formatter\response\section_element_response for an example
 * on how to use it
 */
class element_response_lines_formatter extends base {

    use response_formatter_trait;

    /**
     * @var string
     */
    private static $default_formatter_class = string_response_formatter::class;

    /**
     * @var string
     */
    private static $formatter_class_name = 'response_lines_formatter';
}