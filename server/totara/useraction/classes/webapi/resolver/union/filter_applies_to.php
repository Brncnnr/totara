<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package
 */

namespace totara_useraction\webapi\resolver\union;

use core\webapi\union_resolver;
use GraphQL\Type\Definition\ResolveInfo;
use totara_useraction\filter\applies_to;
use totara_useraction\webapi\resolver\type\filters_applies_to_all_users;
use totara_useraction\webapi\resolver\type\filters_applies_to_audiences;

/**
 * Type resolver for scheduled_rule action
 */
class filter_applies_to implements union_resolver {
    public static function resolve_type($objectvalue, $context, ResolveInfo $info): string {
        if (!$objectvalue instanceof applies_to) {
            throw new \coding_exception('Invalid filter type passed to union');
        }

        if ($objectvalue->is_all_users()) {
            return filters_applies_to_all_users::class;
        }

        return filters_applies_to_audiences::class;
    }
}