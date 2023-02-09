<?php
/**
 * This file is part of Totara Perform
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class override_global_participation_settings extends mutation_resolver {

    /**
     * Override global settings:
     * perform_sync_participant_instance_creation and/or
     * perform_sync_participant_instance_closure
     *
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $settings = $args['input'] ?? null;
        if (!$settings) {
            throw new \invalid_parameter_exception('activity override global settings not given');
        }

        /** @type $activity activity */
        $activity = $args['activity'];
        $updates = [
            activity_setting::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS => (bool)$args['input'][activity_setting::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS],
            activity_setting::SYNC_PARTICIPANT_INSTANCE_CREATION => (bool)$args['input'][activity_setting::SYNC_PARTICIPANT_INSTANCE_CREATION],
            activity_setting::SYNC_PARTICIPANT_INSTANCE_CLOSURE => (bool)$args['input'][activity_setting::SYNC_PARTICIPANT_INSTANCE_CLOSURE],
        ];
        $activity->settings->update($updates);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_authenticated_user(),
            require_activity::by_activity_id('input.activity_id', true),
            require_manage_capability::class
        ];
    }
}