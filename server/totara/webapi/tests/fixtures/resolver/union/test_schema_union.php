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

namespace totara_webapi\webapi\resolver\union;

use core\webapi\union_resolver;
use GraphQL\Type\Definition\ResolveInfo;
use totara_webapi\webapi\resolver\query\status as status_query;
use totara_webapi\webapi\resolver\type\test_schema_type1;
use totara_webapi\webapi\resolver\type\test_schema_type2;
use totara_webapi\webapi\resolver\type\test_schema_type3;

defined('MOODLE_INTERNAL') || die();

/**
 * This resolver is just for testing the graphql server
 *
 * @package totara_webapi\webapi\resolver\union
 */
class test_schema_union implements union_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve_type($objectvalue, $context, ResolveInfo $info): string {
        global $CFG;
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/type/test_schema_type1.php';
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/type/test_schema_type2.php';
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/type/test_schema_type3.php';

        switch ($objectvalue['type']) {
            case 1:
                return test_schema_type1::class;
            case 2:
                return test_schema_type2::class;
            case 'invalid':
                return '\this\class\does\not\exist';
            case 'no_type_resolver':
                return status_query::class;
            case 'undefined_type':
                return test_schema_type3::class;
        }
    }
}