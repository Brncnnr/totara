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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\query_resolver;

defined('MOODLE_INTERNAL') || die();

class test_deprecation extends query_resolver {

    public static function resolve(array $args, execution_context $ec) {
        return [
            'nondeprecated' => 'not deprecated',
            'deprecated' => 'deprecated with a reason',
            'deprecated_without_reason' => 'deprecated without a reason',
        ];
    }

}