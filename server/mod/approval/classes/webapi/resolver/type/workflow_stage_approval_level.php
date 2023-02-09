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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\type;

use coding_exception;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_approval\formatter\workflow\workflow_stage_approval_level as workflow_stage_approval_level_formatter;
use mod_approval\model\workflow\workflow_stage_approval_level as workflow_stage_approval_level_model;

/**
 * Workflow type resolver
 */
class workflow_stage_approval_level extends type_resolver {
    /**
     * @param string $field
     * @param workflow_stage_approval_level_model|object $workflow
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $workflow, array $args, execution_context $ec) {
        if (!$workflow instanceof workflow_stage_approval_level_model) {
            throw new coding_exception('Expected workflow stage approval level model');
        }
        $format = $args['format'] ?? format::FORMAT_PLAIN;
        $formatter = new workflow_stage_approval_level_formatter($workflow, $ec->get_relevant_context());

        return $formatter->format($field, $format);
    }
}