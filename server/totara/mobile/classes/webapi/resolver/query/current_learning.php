<?php
/**
 *
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\webapi\resolver\query;

use core\webapi\execution_context;
use mobile_currentlearning\webapi\resolver\query\my_items as plugin_query;

/**
 * @deprecated since totara 15.0 and replaced by the sub-plugin mobile_currentlearning
 */
class current_learning extends plugin_query {

    public static function resolve(array $args, execution_context $ec) {
        debugging('This class has been deprecated, please use my_items in mobile_currentlearning');

        return parent::resolve($args, $ec);
    }
}
