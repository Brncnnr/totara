<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency;

/**
 * Class settings
 *
 * Competency related site-wide settings
 *
 * @package totara_competency
 */
class settings {

    /**
     * Check whether continuous tracking is enabled
     *
     * @return bool
     */
    public static function is_continuous_tracking_enabled(): bool {
        $config = (int) get_config('totara_competency', 'continuous_tracking');
        return $config === admin_setting_continuous_tracking::ENABLED;
    }

    /**
     * Check whether records for unassigned users should be kept
     *
     * @return bool
     */
    public static function should_unassign_keep_records(): bool {
        $config = (int) get_config('totara_competency', 'unassign_behaviour');
        return $config === admin_setting_unassign_behaviour::KEEP;
    }

    /**
     * Check whether records for unassigned users should be kept
     *
     * @return bool
     */
    public static function should_unassign_keep_achieved_records(): bool {
        $config = (int) get_config('totara_competency', 'unassign_behaviour');
        return $config === admin_setting_unassign_behaviour::KEEP_NOT_NULL;
    }

    /**
     * Enable site-wide continuous tracking setting
     *
     * @param bool $enable
     * @return bool
     */
    public static function enable_continuous_tracking(bool $enable = true): bool {
        $value = $enable ? admin_setting_continuous_tracking::ENABLED : admin_setting_continuous_tracking::DISABLED;

        return set_config('continuous_tracking', $value, 'totara_competency');
    }

    /**
     * Disable site-wide continuous tracking setting
     *
     * @return bool
     */
    public static function disable_continuous_tracking(): bool {
        return static::enable_continuous_tracking(false);
    }

    /**
     * Set unassign behaviour to keep by default
     *
     * @param int $action
     * @return bool
     */
    public static function unassign_keep_records(int $action = admin_setting_unassign_behaviour::KEEP): bool {
        if (!in_array($action, [
            admin_setting_unassign_behaviour::KEEP,
            admin_setting_unassign_behaviour::KEEP_NOT_NULL,
            admin_setting_unassign_behaviour::DELETE,
        ])) {
            throw new \coding_exception('unassign_behaviour value is incorrect.');
        }

        return set_config('unassign_behaviour', $action, 'totara_competency');
    }

    /**
     * Set unassign behaviour to delete
     *
     * @return bool
     */
    public static function unassign_delete_records(): bool {
        return static::unassign_keep_records(admin_setting_unassign_behaviour::DELETE);
    }

    /**
     * Set unassign behaviour to delete records without achievements
     *
     * @return bool
     */
    public static function unassign_delete_empty_records(): bool {
        return static::unassign_keep_records(admin_setting_unassign_behaviour::KEEP_NOT_NULL);
    }

}