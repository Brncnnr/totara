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

namespace mod_perform\formatter\activity;

use coding_exception;
use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\string_field_formatter;
use mod_perform\constants;
use mod_perform\entity\activity\track as track_entity;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps the track model class into the GraphQL mod_perform_track type.
 */
class track extends entity_model_formatter {

    /**
     * @var \mod_perform\models\activity\track
     */
    protected $object;

    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'description' => string_field_formatter::class,
            'status' => null,
            'subject_instance_generation' => null,
            'schedule_is_open' => null,
            'schedule_is_fixed' => null,
            'schedule_fixed_from' => function () {
                return $this->object->get_schedule_fixed_from_setting();
            },
            'schedule_fixed_to' => function () {
                return $this->object->get_schedule_fixed_to_setting();
            },
            'schedule_dynamic_from' => null,
            'schedule_dynamic_to' => null,
            'schedule_dynamic_source' => null,
            'schedule_use_anniversary' => null,
            'due_date_is_enabled' => null,
            'due_date_is_fixed' => null,
            'due_date_fixed' => function () {
                return $this->object->get_due_date_fixed_setting();
            },
            'due_date_offset' => null,
            'repeating_is_enabled' => null,
            'repeating_trigger_interval' => null,
            'repeating_trigger_type' => null,
            'repeating_type' => null,
            'repeating_offset' => null,
            'repeating_is_limited' => null,
            'repeating_limit' => null,
            'created_at' => null,
            'updated_at' => null,
            'assignments' => null,
            'can_assign_positions' => null,
            'can_assign_organisations' => null
        ];
    }

    /**
     * @param string $field
     *
     * @return mixed
     */
    protected function get_field(string $field) {
        $repeating_trigger = $this->object->repeating_trigger;

        switch ($field) {
            // There is already a track::get_repeating_type() method but it has
            // been deprecated in favor of the new repeating_trigger class. So
            // this is just a workaround during the migration.
            case 'repeating_type':
                // Contrary to convention, track::get_repeating_type() does not
                // return the value of the underlying entity field; instead its
                // sole purpose is to return a value that corresponds to an enum
                // value defined in the graphql interface.
                // To get the actual value, we have to get it from the model's
                // entity.
                $type = $this->object->get_entity_copy()->$field;
                if (is_null($type)) {
                    return null;
                }

                switch ((int)$type) {
                    case track_entity::SCHEDULE_REPEATING_TYPE_AFTER_CREATION:
                        return constants::SCHEDULE_REPEATING_AFTER_CREATION;

                    case track_entity::SCHEDULE_REPEATING_TYPE_AFTER_CREATION_WHEN_COMPLETE:
                        return constants::SCHEDULE_REPEATING_AFTER_CREATION_WHEN_COMPLETE;

                    case track_entity::SCHEDULE_REPEATING_TYPE_AFTER_COMPLETION:
                        return constants::SCHEDULE_REPEATING_AFTER_COMPLETION;

                    case track_entity::SCHEDULE_REPEATING_TYPE_UNSET:
                        return constants::SCHEDULE_REPEATING_UNSET;

                    default:
                        throw new coding_exception("cannot stringify repeating type: '$type'");
                }

            // These fields are not put in the model because it makes no sense to
            // have them there just to get a model formatter to work.
            case 'repeating_trigger_type':
                return $repeating_trigger
                    ? $repeating_trigger->get_name()
                    : null;

            case 'repeating_trigger_interval':
                return $repeating_trigger
                    ? $repeating_trigger->get_interval()
                    : null;
            default:
                return parent::get_field($field);
        }
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function has_field(string $field): bool {
        $computed = [
            'repeating_trigger_interval',
            'repeating_trigger_type'
        ];

        return in_array($field, $computed)
            ? true
            : parent::has_field($field);
    }
}
