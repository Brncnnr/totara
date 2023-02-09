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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use coding_exception;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\formatter\activity\activity as activity_formatter;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\settings\visibility_conditions\none;
use mod_perform\models\activity\settings\visibility_conditions\visibility_manager;
use mod_perform\models\activity\settings\visibility_conditions\visibility_option;

/**
 * Note: It is the responsibility of the query to ensure the user is permitted to see an activity.
 */
class activity extends type_resolver {

    /**
     * @param string $field
     * @param activity_model $activity
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $activity, array $args, execution_context $ec) {
        if (!$activity instanceof activity_model) {
            throw new coding_exception('Expected activity model');
        }

        if ($field == 'can_view_participation_reporting') {
            return $activity->can_view_participation_reporting();
        }
        if ($field == 'can_manage') {
            return $activity->can_manage();
        }
        if ($field === 'settings') {
            $settings = $activity->settings;

            return [
                'close_on_completion' => (bool)$settings->lookup(activity_setting::CLOSE_ON_COMPLETION),
                'close_on_due_date' => (bool)$settings->lookup(activity_setting::CLOSE_ON_DUE_DATE),
                'multisection' => $activity->multisection_setting,
                'visibility_condition' => self::get_visibility_option($settings),
                'override_global_participation_settings' => (bool)$settings->lookup(activity_setting::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS),
                'sync_participant_instance_creation' => (bool)$settings->lookup(activity_setting::SYNC_PARTICIPANT_INSTANCE_CREATION),
                'sync_participant_instance_closure' => (bool)$settings->lookup(activity_setting::SYNC_PARTICIPANT_INSTANCE_CLOSURE),
            ];
        }

        $format = $args['format'] ?? format::FORMAT_HTML;
        $formatter = new activity_formatter($activity, $ec->get_relevant_context());

        return $formatter->format($field, $format);
    }

    /**
     * Gets visibility option.
     * @param $settings
     *
     * @return visibility_option
     * @throws coding_exception
     */
    private static function get_visibility_option($settings): visibility_option {
        $visibility_manager = new visibility_manager();
        $value = $settings->lookup(activity_setting::VISIBILITY_CONDITION, none::VALUE);

        return $visibility_manager->get_option_with_value($value);
    }
}