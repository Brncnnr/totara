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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

use core\collection;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity_setting;

/**
 * Helper class for providing the configuration settings for participation synchronisation.
 * These settings can be both global and activity-specific overrides.
 */
class participation_sync_settings_helper {

    private $override_settings;

    /**
     * Create an instance for a collection of subject instance entities.
     * Note: It saves DB queries when they are passed in with the 'track' relation pre-attached.
     *
     * @param collection|subject_instance[] $subject_instances
     * @return static
     */
    public static function create_from_subject_instances(collection $subject_instances): self {
        $helper = new self();
        $helper->init_override_settings($subject_instances);
        return $helper;
    }

    /**
     * @param collection $subject_instances
     * @return void
     */
    private function init_override_settings(collection $subject_instances): void {
        $this->override_settings = [];
        $activity_ids = $subject_instances->map(
            function (subject_instance $subject_instance) {
                return $subject_instance->track->activity_id;
            }
        )->all();

        $activity_ids = array_unique($activity_ids);
        $settings = activity_setting::fetch_for_activity_ids($activity_ids);
        foreach ($settings as $setting) {
            if (in_array($setting->name, [
                activity_setting::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS,
                activity_setting::SYNC_PARTICIPANT_INSTANCE_CLOSURE,
                activity_setting::SYNC_PARTICIPANT_INSTANCE_CREATION,
            ])) {
                $this->override_settings[$setting->activity_id][$setting->name] = $setting->value;
            }
        }
    }

    /**
     * @param int $activity_id
     * @return bool
     */
    public function should_instance_creation_be_synced(int $activity_id): bool {
        $activity_setting_key = activity_setting::SYNC_PARTICIPANT_INSTANCE_CREATION;

        if ($this->is_override_configured($activity_id, $activity_setting_key)) {
            return (bool)$this->override_settings[$activity_id][$activity_setting_key];
        }

        // Fall back to global setting.
        return (bool)get_config(null, 'perform_sync_participant_instance_creation');
    }

    /**
     * @param int $activity_id
     * @return bool
     */
    public function should_instance_closure_be_synced(int $activity_id): bool {
        $activity_setting_key = activity_setting::SYNC_PARTICIPANT_INSTANCE_CLOSURE;

        if ($this->is_override_configured($activity_id, $activity_setting_key)) {
            return (bool)$this->override_settings[$activity_id][$activity_setting_key];
        }

        // Fall back to global setting.
        return (bool)get_config(null, 'perform_sync_participant_instance_closure');
    }

    /**
     * @param int $activity_id
     * @param string $activity_setting_key
     * @return bool
     */
    private function is_override_configured(int $activity_id, string $activity_setting_key): bool {
        if (isset($this->override_settings[$activity_id])) {
            $activity_settings = $this->override_settings[$activity_id];
            if (
                isset(
                    $activity_settings[activity_setting::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS],
                    $activity_settings[$activity_setting_key]
                )
                && (bool)$activity_settings[activity_setting::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS] === true
            ) {
                return true;
            }
        }

        return false;
    }

}
