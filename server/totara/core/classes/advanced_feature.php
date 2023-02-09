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
 * @package totara_core
 */

namespace totara_core;

use coding_exception;

class advanced_feature {

    public const ENABLED = 1;
    public const DISABLED = 3;

    /**
     * Check to see if a feature is set to be enabled (visible) in Advanced Features
     *
     * @param string $feature The name of the feature from the list in {@link self::get_available()}.
     * @return bool True if the feature is set to be visible.
     */
    public static function is_enabled(string $feature): bool {
        return self::check($feature, self::ENABLED);
    }

    /**
     * Check to see if a feature is set to be disabled in Advanced Features
     *
     * @param string $feature The name of the feature from the list in {@link self::get_available()}.
     * @return bool True if the feature is disabled.
     */
    public static function is_disabled(string $feature): bool {
        return self::check($feature, self::DISABLED);
    }

    /**
     * Checks if a feature is enabled (or hidden) and throws an exception if not
     *
     * @param string $feature
     * @throws feature_not_available_exception
     */
    public static function require(string $feature) {
        if (self::is_disabled($feature)) {
            throw new feature_not_available_exception($feature);
        }
    }

    /**
     * Helper method to enable a feature, usually this happens via the settings
     * interface but in some cases like in tests this function can be used..
     *
     * @param string $feature
     */
    public static function enable(string $feature) {
        if (!in_array($feature, self::get_available())) {
            throw new coding_exception("'{$feature}' not supported by Totara feature checking code.");
        }

        if (self::check($feature, self::ENABLED)) {
            return;
        }

        set_config("enable{$feature}", self::ENABLED);
    }

    /**
     * Helper method to disable a feature, usually this happens via the settings
     * interface but in some cases like in tests this function can be used..
     *
     * @param string $feature
     */
    public static function disable(string $feature) {
        if (!in_array($feature, self::get_available())) {
            throw new coding_exception("'{$feature}' not supported by Totara feature checking code.");
        }

        if (self::check($feature, self::DISABLED)) {
            return;
        }

        set_config("enable{$feature}", self::DISABLED);
    }

    /**
     * Check the state of a particular Totara feature against the specified state.
     *
     * Used by the other functions to see if some Totara functionality is visible/disabled.
     *
     * @param string $feature Name of the feature to check, must match options from {@link self::get_available()}.
     * @param integer $state_constant State to check, must match one of constants defined in this file.
     * @return bool True if the feature's config setting is in the specified state.
     */
    public static function check(string $feature, int $state_constant): bool {
        global $CFG;

        if (!in_array($feature, self::get_available())) {
            throw new coding_exception("'{$feature}' not supported by Totara feature checking code.");
        }

        $cfgsetting = "enable{$feature}";
        return (isset($CFG->$cfgsetting) && $CFG->$cfgsetting == $state_constant);
    }

    /**
     * List of strings which can be used with the checks in here
     *
     * Update this list if you add/remove settings in admin/settings/subsystems.php.
     *
     * @return array Array of strings of supported features (should have a matching "enable{$feature}" config setting).
     */
    public static function get_available(): array {
        return [
            'api',
            'appraisals',
            'approval_workflows',
            'certifications',
            'competencies',
            'competency_assignment',
            'completionimport',
            'container_workspace',
            'engage_resources',
            'evidence',
            'feedback360',
            'goals',
            'learningplans',
            'ml_recommender',
            'myteam',
            'organisations',
            'performance_activities',
            'perform_hide_suspended_users',
            'perform_close_suspended_user_instances',
            'positions',
            'programs',
            'recordoflearning',
            'reportgraphs',
            'totara_msteams',
            'totaradashboard',
            'user_reports',
        ];
    }
}
