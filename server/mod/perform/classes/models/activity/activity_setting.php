<?php
/**
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use coding_exception;
use core\orm\entity\model;
use mod_perform\entity\activity\activity_setting as activity_setting_entity;
use mod_perform\models\activity\settings\visibility_conditions\all_responses;
use mod_perform\models\activity\settings\visibility_conditions\visibility_manager;
use moodle_exception;

/**
 * Represents a single setting for a parent activity.
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string $value
 * @property-read activity $activity
 */
class activity_setting extends model {
    /**
     * @var activity_setting_entity
     */
    protected $entity;

    // List of known "out of the box" setting names.
    public const CLOSE_ON_COMPLETION = 'close_on_completion';
    public const CLOSE_ON_DUE_DATE = 'close_on_due_date';
    public const MULTISECTION = 'multisection';
    public const VISIBILITY_CONDITION = 'visibility_condition';
    public const OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS = 'override_global_participation_settings';
    public const SYNC_PARTICIPANT_INSTANCE_CREATION = 'sync_participant_instance_creation';
    public const SYNC_PARTICIPANT_INSTANCE_CLOSURE = 'sync_participant_instance_closure';
    protected $entity_attribute_whitelist = [
        'id',
        'name',
        'value'
    ];

    protected $model_accessor_whitelist = [
        'activity'
    ];

    /**
     * Get a setting object by activity id and setting name
     * This will return null if setting does not exist
     *
     * @param int $activity_id
     * @param string $name
     * @return activity_setting|null
     * @throws \coding_exception
     */
    public static function load_by_name(int $activity_id, string $name) {
        $activity_setting = activity_setting_entity::repository()
            ->where('activity_id', $activity_id)
            ->where("name", $name)
            ->one();

        return $activity_setting ? new activity_setting($activity_setting) : null;
    }

    /**
     * Get a setting object by activity id and setting name
     * This will create a new setting with null value if does not exist
     *
     * @param int $activity_id
     * @param string $name
     * @return activity_setting|null
     * @throws \coding_exception
     * @throws moodle_exception
     */
    public static function load_by_name_or_create(int $activity_id, string $name) {
        $activity_setting = self::load_by_name($activity_id, $name);
        if ($activity_setting == null) {
            $activity = activity::load_by_id($activity_id);
            $activity_setting = self::create($activity, $name, null);
        }
        return $activity_setting;
    }

    /**
     * {@inheritdoc}
     */
    protected static function get_entity_class(): string {
        return activity_setting_entity::class;
    }

    /**
     * Creates a setting record for the given parent activity.
     *
     * @param activity $parent parent activity.
     * @param string $name setting name.
     * @param mixed $value setting value.
     *
     * @return activity_setting the newly created setting model.
     */
    public static function create(
        activity $parent,
        string $name,
        $value
    ): activity_setting {
        $allowed = [
            self::CLOSE_ON_COMPLETION,
            self::CLOSE_ON_DUE_DATE,
            self::MULTISECTION,
            self::VISIBILITY_CONDITION,
            self::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS,
            self::SYNC_PARTICIPANT_INSTANCE_CREATION,
            self::SYNC_PARTICIPANT_INSTANCE_CLOSURE,
        ];
        if (!in_array($name, $allowed)) {
            throw new coding_exception("invalid activity setting name: $name");
        }

        if (is_bool($value)) {
            $value = (int)$value;
        }

        self::validate($parent, $name, $value);

        $entity = new activity_setting_entity();
        $entity->activity_id = $parent->id;
        $entity->name = $name;
        $entity->value = (string)$value;

        $entity->save();

        return new activity_setting($entity);
    }

    /**
     * Get the parent activity model.
     *
     * @return activity the parent activity.
     */
    public function get_activity(): activity {
        return activity::load_by_entity($this->entity->activity);
    }

    /**
     * Sets the setting value.
     *
     * @param mixed $value new value.
     *
     * @return activity_setting the setting model.
     * @throws moodle_exception
     */
    public function update($value): activity_setting {
        if (!$this->get_activity()->can_manage()) {
            throw new moodle_exception('nopermissions', '', '', 'update setting');
        }

        if (is_bool($value)) {
            $value = (int)$value;
        }

        self::validate($this->get_activity(), $this->name, $value);

        $this->entity->value = (string)$value;
        $this->entity->save();

        return $this;
    }

    /**
     * Deletes the activity settings. Note: after this, the model is invalid.
     */
    public function delete(): void {
        if (!$this->activity->can_manage()) {
            throw new moodle_exception('nopermissions', '', '', 'delete setting');
        }
        $this->entity->delete();
    }

    /**
     * Activity setting update validation
     *
     * @param activity $activity
     * @param string $name
     * @param mixed $value
     * @throws moodle_exception
     */
    public static function validate(activity $activity, string $name, $value): void {
        if ($name === self::VISIBILITY_CONDITION) {
            if ($activity->is_active() && $activity->anonymous_responses) {
                throw new coding_exception("Can not update visibility condition for activated activity when anonymity is enabled.");
            }
            if ($value !== null && $activity->anonymous_responses && $value != all_responses::VALUE) {
                throw new coding_exception(
                    "Anonymous activities have to be set to show responses after all participants completed their instances."
                );
            }
            $visibility_manager = new visibility_manager();
            if ($value && !$visibility_manager->has_option_with_value($value)) {
                throw new coding_exception("invalid visibility condition value: $value");
            }
        }
    }

    public static function fetch_for_activity_ids(array $activity_ids): array {
        return activity_setting_entity::repository()
            ->select(['id', 'activity_id', 'name', 'value'])
            ->where_in('activity_id', $activity_ids)
            ->get()
            ->all();
    }

}
