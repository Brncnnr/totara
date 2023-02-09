<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\mutation;

use core\orm\collection;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\middleware\require_system_capability;
use core\webapi\mutation_resolver;
use totara_competency\models\assignment as assignment_model;
use totara_competency\min_proficiency_override_for_assignments;

/**
 * Mutation to bulk update min proficiency value overrides for competency assignments.
 */
class update_min_proficiency_override_for_assignments extends mutation_resolver {

    /**
     * Bulk update min proficiency value overrides and return the updated assignments.
     *
     * @param array $args
     *
     * @param execution_context $ec
     * @return collection|assignment_model[]
     */
    public static function resolve(array $args, execution_context $ec) {
        /** @var int|null $scale_value_id */
        $scale_value_id = $args['input']['scale_value_id'];

        /** @var int[] $assignment_ids */
        $assignment_ids = $args['input']['assignment_ids'];

        $assignments = (new min_proficiency_override_for_assignments(
            $scale_value_id,
            $assignment_ids
        ))->process();

        return ['assignments' => $assignments];
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('competency_assignment'),
            new require_system_capability('totara/competency:manage_assignments'),
        ];
    }

}
