<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package pathway_criteria_group
 */

namespace pathway_criteria_group\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use pathway_criteria_group\entity\criteria_group as criteria_group_entity;

/**
 * Fetches all criterions within the given group
 */
class achievements extends query_resolver {

    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        $instance_id = $args['instance_id'];

        $result = [];

        /** @var criteria_group_entity $criteria_group */
        $criteria_group = criteria_group_entity::repository()->find($instance_id);

        if (!empty($criteria_group)) {
            $criterions = $criteria_group->criterions()->order_by('id')->get();
            foreach ($criterions as $criterion) {
                $result[] = [
                    'instance_id' => $criterion->criterion_id,
                    'type' => $criterion->criterion_type
                ];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('competency_assignment'),
        ];
    }

}
