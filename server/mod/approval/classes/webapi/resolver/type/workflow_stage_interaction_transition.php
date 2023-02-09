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
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_approval\exception\helper\validation;
use mod_approval\formatter\workflow\workflow_stage_interaction_transition as workflow_stage_interaction_transition_formatter;
use mod_approval\model\workflow\workflow_stage_interaction_transition as workflow_stage_interaction_transition_model;

/**
 * Resolves workflow_stage_interaction_transition
 */
final class workflow_stage_interaction_transition extends type_resolver {
    /**
     * @param string $field
     * @param workflow_stage_interaction_transition_model|object $workflow_stage_interaction_transition
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $workflow_stage_interaction_transition, array $args, execution_context $ec) {
        validation::instance_of($workflow_stage_interaction_transition, workflow_stage_interaction_transition_model::class);

        if ($field === 'transition') {
            return $workflow_stage_interaction_transition->get_transition_field();
        }

        // Default transition does not have any condition, and you cannot set any, so it will return null
        if (!$workflow_stage_interaction_transition->is_conditional() && $field == 'condition') {
            return null;
        }

        $format = $args['format'] ?? format::FORMAT_PLAIN;
        $formatter = new workflow_stage_interaction_transition_formatter($workflow_stage_interaction_transition, $ec->get_relevant_context());

        return $formatter->format($field, $format);
    }
}
