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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\union;

use coding_exception;
use core\entity\user as user_entity;
use core\webapi\resolver\type\user as user_resolver;
use core\webapi\union_resolver;
use GraphQL\Type\Definition\ResolveInfo;
use totara_core\relationship\relationship as relationship_model;
use totara_core\webapi\resolver\type\relationship as relationship_resolver;

/**
 * approver_entity_union class
 */
class approver_entity_union implements union_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve_type($objectvalue, $context, ResolveInfo $info): string {
        if ($objectvalue instanceof user_entity) {
            return user_resolver::class;
        }
        if ($objectvalue instanceof relationship_model) {
            return relationship_resolver::class;
        }
        throw new coding_exception('Unknown union type: ' . get_class($objectvalue));
    }
}
