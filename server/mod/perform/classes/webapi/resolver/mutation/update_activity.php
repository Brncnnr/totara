<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\mutation_resolver;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\settings\visibility_conditions\all_responses;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class update_activity extends mutation_resolver {

    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        // The require_activity middleware loads the activity and passes it along via the args
        /** @var activity $activity */
        $activity = $args['activity'];

        builder::get_db()->transaction(
            function () use ($args, $activity) {
                if (isset($args['relationships'])) {
                    debugging(
                        'Using the mod_perform_update_activity mutation to update relationships is deprecated',
                        DEBUG_DEVELOPER
                    );
                    $activity->update_manual_relationship_selections($args['relationships']);
                }

                $activity->set_general_info($args['name'], $args['description'] ?? null, $args['type_id'] ?? null);

                $visibility_condition_value = null;
                if (isset($args['anonymous_responses'])) {
                    $activity->set_anonymous_setting($args['anonymous_responses']);
                    // Turn on anonymous responses will automatically set the visibility to 'all responses closed'
                    if ($args['anonymous_responses']) {
                        $visibility_condition_value = all_responses::VALUE;
                    }
                }

                $activity->update();

                // Visibility condition should be updated after anonymous setting
                // because visibility condition validation need the updated anonymous setting value
                if (isset($args['visibility_condition'])) {
                    $visibility_condition_value = $visibility_condition_value ?? $args['visibility_condition'];
                    $activity->update_visibility_condition($visibility_condition_value);
                }
            }
        );

        return ['activity' => $activity];
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            require_activity::by_activity_id('activity_id', true),
            require_manage_capability::class
        ];
    }
}