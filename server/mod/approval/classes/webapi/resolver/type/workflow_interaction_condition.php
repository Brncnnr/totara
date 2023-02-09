<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 */

namespace mod_approval\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_approval\model\workflow\interaction\condition\interaction_condition;

/**
 * Workflow interaction transition resolver.
 */
final class workflow_interaction_condition extends type_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof interaction_condition) {
            throw new coding_exception("source must be an instance of " . interaction_condition::class);
        }
        if ($field === 'condition_key') {
            return $source->condition_key_field();
        }
        if ($field === 'condition_data') {
            return $source->condition_data_field();
        }

        return $source->{$field};
    }
}