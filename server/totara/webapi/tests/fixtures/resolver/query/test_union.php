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
 * @package totara_userstatus
 */

namespace totara_webapi\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\query_resolver;

defined('MOODLE_INTERNAL') || die();

class test_union extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        switch ($args['type']) {
            case 'type1':
                return [
                    'id' => 1,
                    'name' => 'type1',
                    'type' => 1,
                    'is_type1' => true
                ];
            case 'type2':
                return [
                    'id' => 2,
                    'name' => 'type2',
                    'type' => 2,
                    'is_type2' => true
                ];
            case 'invalid':
                return [
                    'id' => 3,
                    'name' => 'type3',
                    'type' => 'invalid'
                ];
            case 'no_type_resolver':
                return [
                    'id' => 4,
                    'name' => 'type4',
                    'type' => 'no_type_resolver'
                ];
            case 'undefined_type':
                return [
                    'id' => 5,
                    'name' => 'type5',
                    'type' => 'undefined_type'
                ];
        }
    }
}