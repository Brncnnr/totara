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

namespace mod_perform\entity\activity;

use coding_exception;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_many_through;
use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\dynamic_source;
use mod_perform\models\activity\trigger\repeating\factory as repeating_trigger_factory;
use mod_perform\models\activity\trigger\repeating\trigger as repeating_trigger;

/**
 * Represents an activity track record in the repository.
 *
 * @property-read int $id record id
 * @property int $activity_id parent activity record id
 * @property string $description track description
 * @property int $status track status
 * @property bool $schedule_is_open
 * @property bool $schedule_is_fixed
 * @property int $subject_instance_generation the system used to decide what subject instances are generated
 * @property int $schedule_fixed_from when schedule type is FIXED, contains the start date of assignment
 * @property int $schedule_fixed_to when schedule type is CLOSED_FIXED, contains the end date of assignment
 * @property string|null $schedule_fixed_timezone
 * @property date_offset|null $schedule_dynamic_from an offset for dynamic schedule (saved as json)
 * @property date_offset|null $schedule_dynamic_to an offset for dynamic schedule (saved as json)
 * @property dynamic_source|null $schedule_dynamic_source a dynamic_source for dynamic schedule (saved as json)
 * @property bool $schedule_use_anniversary should the dynamic schedule use the anniversary (next occurrence)
 *                                          of reference date if it is in the past
 * @property bool $schedule_needs_sync Flag indicating that the schedule sync task should run for this track
 * @property bool $due_date_is_enabled
 * @property bool $due_date_is_fixed
 * @property int $due_date_fixed
 * @property string|null $due_date_fixed_timezone
 * @property date_offset|null $due_date_offset
 * @property bool $repeating_is_enabled
 * @property repeating_trigger|null $repeating_trigger
 * @property int $repeating_type Deprecated since Totara 16, please do not use
 * @property date_offset|null $repeating_offset
 * @property bool $repeating_is_limited
 * @property int $repeating_limit
 * @property int $created_at record creation time
 * @property int $updated_at record modification time
 * @property-read collection|subject_instance[] $subject_instances
 * @property-read activity $activity
 * @property-read collection|track_assignment[] $assignments
 *
 * @method static track_repository repository()
 */
class track extends entity {
    public const TABLE = 'perform_track';
    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';

    public const STATUS_ACTIVE = 1;
    public const STATUS_PAUSED = 0;

    public const SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT = 0;
    public const SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB = 1;

    /**
     * @deprecated since Totara 16; use models\activity\trigger\repeating classes
     * instead.
     */
    public const SCHEDULE_REPEATING_TYPE_AFTER_CREATION = 0;
    /**
     * @deprecated since Totara 16; use models\activity\trigger\repeating classes
     * instead.
     */
    public const SCHEDULE_REPEATING_TYPE_AFTER_CREATION_WHEN_COMPLETE = 1;
    /**
     * @deprecated since Totara 16; use models\activity\trigger\repeating classes
     * instead.
     */
    public const SCHEDULE_REPEATING_TYPE_AFTER_COMPLETION = 2;
    /**
     * @deprecated since Totara 16; temporary placeholder for other new
     * models\activity\trigger\repeating classes.
     */
    public const SCHEDULE_REPEATING_TYPE_UNSET = 99;

    /**
     * Establishes the relationship with activity entities.
     *
     * @return belongs_to the relationship.
     */
    public function activity(): belongs_to {
        return $this->belongs_to(activity::class, 'activity_id');
    }

    /**
     * Establishes the relationship with track assignments.
     *
     * @return has_many the relationship.
     */
    public function assignments(): has_many {
        return $this->has_many(track_assignment::class, 'track_id');
    }

    /**
     * Get all subject instance for this track
     *
     * @return has_many_through
     */
    public function subject_instances(): has_many_through {
        return $this->has_many_through(
            track_user_assignment::class,
            subject_instance::class,
            'id',
            'track_id',
            'id',
            'track_user_assignment_id'
        );
    }

    /**
     * Unserialize schedule_dynamic_source.
     *
     * @return dynamic_source | null
     */
    protected function get_schedule_dynamic_source_attribute(): ?dynamic_source {
        $json_encoded = $this->get_attributes_raw()['schedule_dynamic_source'];

        if ($json_encoded === null) {
            return null;
        }

        return dynamic_source::create_from_json($json_encoded);
    }

    /**
     * Serialize (or skip) schedule_dynamic_source.
     *
     * @param string|dynamic_source|null
     * @return track
     */
    protected function set_schedule_dynamic_source_attribute($dynamic_source = null): self {
        $json_encoded = $this->encode_dynamic_source($dynamic_source);

        return $this->set_attribute_raw('schedule_dynamic_source', $json_encoded);
    }

    protected function encode_dynamic_source($dynamic_source) {
        switch ($dynamic_source) {
            case null:
                return null;
            case is_array($dynamic_source):
            case is_string($dynamic_source):
                return json_encode(dynamic_source::create_from_json($dynamic_source));
            case $dynamic_source instanceof dynamic_source:
                return json_encode($dynamic_source);
            default:
                throw new coding_exception(
                    'schedule dynamic resolver must be a dynamic_source, null, or json encoded dynamic_source'
                );
        }
    }

    /**
     * Unserialize schedule_dynamic_from.
     *
     * @return date_offset | null
     */
    protected function get_schedule_dynamic_from_attribute(): ?date_offset {
        $json_encoded = $this->get_attributes_raw()['schedule_dynamic_from'];

        if ($json_encoded === null) {
            return null;
        }

        return date_offset::create_from_json($json_encoded);
    }

    /**
     * Serialize (or skip) schedule_dynamic_from.
     *
     * @param string | date_offset | null
     * @return track
     */
    protected function set_schedule_dynamic_from_attribute($dynamic_offset = null): self {
        $json_encoded = $this->encode_dynamic_offset($dynamic_offset);

        return $this->set_attribute_raw('schedule_dynamic_from', $json_encoded);
    }

    /**
     * Unserialize schedule_dynamic_to.
     *
     * @return date_offset | null
     */
    protected function get_schedule_dynamic_to_attribute(): ?date_offset {
        $json_encoded = $this->get_attributes_raw()['schedule_dynamic_to'];

        if ($json_encoded === null) {
            return null;
        }

        return date_offset::create_from_json($json_encoded);
    }

    /**
     * Serialize (or skip) schedule_dynamic_to.
     *
     * @param string | date_offset | null
     * @return track
     */
    protected function set_schedule_dynamic_to_attribute($dynamic_offset = null): self {
        $json_encoded = $this->encode_dynamic_offset($dynamic_offset);

        return $this->set_attribute_raw('schedule_dynamic_to', $json_encoded);
    }

    /**
     * Unserializes a repeating_trigger from the value in the table.
     *
     * @return ?repeating_trigger the trigger if it was registered.
     */
    protected function get_repeating_trigger_attribute(): ?repeating_trigger {
        $identifier = $this->get_attributes_raw()['repeating_trigger'];

        return $identifier
            ? repeating_trigger_factory::get_instance()->create_trigger($identifier)
            : null;
    }

    /**
     * Serializes a repeating_trigger to the value to be stored in the table
     *
     * @param ?repeating_trigger $trigger the trigger to store.
     *
     * @return self this object.
     */
    protected function set_repeating_trigger_attribute(
        ?repeating_trigger $trigger = null
    ): self {
        $value = $trigger ? get_class($trigger) : null;
        return $this->set_attribute_raw('repeating_trigger', $value);
    }

    /**
     * Unserialize repeating_offset.
     *
     * @return date_offset | null
     */
    protected function get_repeating_offset_attribute(): ?date_offset {
        $json_encoded = $this->get_attributes_raw()['repeating_offset'];

        if ($json_encoded === null) {
            return null;
        }

        return date_offset::create_from_json($json_encoded);
    }

    /**
     * Serialize (or skip) repeating_offset.
     *
     * @param string | date_offset | null
     * @return track
     */
    protected function set_repeating_offset_attribute($dynamic_offset = null): self {
        $json_encoded = $this->encode_dynamic_offset($dynamic_offset);

        return $this->set_attribute_raw('repeating_offset', $json_encoded);
    }

    /**
     * Unserialize due_date_offset.
     *
     * @return date_offset | null
     */
    protected function get_due_date_offset_attribute(): ?date_offset {
        $json_encoded = $this->get_attributes_raw()['due_date_offset'];

        if ($json_encoded === null) {
            return null;
        }

        return date_offset::create_from_json($json_encoded);
    }

    /**
     * Serialize (or skip) due_date_offset.
     *
     * @param string | date_offset | null
     * @return track
     */
    protected function set_due_date_offset_attribute($dynamic_offset = null): self {
        $json_encoded = $this->encode_dynamic_offset($dynamic_offset);

        return $this->set_attribute_raw('due_date_offset', $json_encoded);
    }

    protected function encode_dynamic_offset($dynamic_offset) {
        switch ($dynamic_offset) {
            case null:
                return null;
            case is_array($dynamic_offset):
            case is_string($dynamic_offset):
                return json_encode(date_offset::create_from_json($dynamic_offset));
            case $dynamic_offset instanceof date_offset:
                return json_encode($dynamic_offset);
            default:
                throw new coding_exception(
                    'dynamic offset must be a dynamic_offset, null, or json encoded dynamic_offset'
                );
        }
    }

    /**
     * Cast schedule_is_fixed to bool type.
     *
     * @return bool
     */
    protected function get_schedule_is_fixed_attribute(): bool {
        return (bool) $this->get_attributes_raw()['schedule_is_fixed'];
    }

    /**
     * Cast schedule_is_open to bool type.
     *
     * @return bool
     */
    protected function get_schedule_is_open_attribute(): bool {
        return (bool) $this->get_attributes_raw()['schedule_is_open'];
    }

    /**
     * Cast due_date_is_enabled to bool type.
     *
     * @return bool
     */
    protected function get_due_date_is_enabled_attribute(): bool {
        return (bool) $this->get_attributes_raw()['due_date_is_enabled'];
    }

    /**
     * Cast due_date_is_fixed to bool type.
     *
     * @return bool|null
     */
    protected function get_due_date_is_fixed_attribute(): ?bool {
        $value = $this->get_attributes_raw()['due_date_is_fixed'];
        if (is_null($value)) {
            return null;
        } else {
            return (bool) $this->get_attributes_raw()['due_date_is_fixed'];
        }
    }

    /**
     * Cast repeating_is_enabled to bool type.
     *
     * @return bool
     */
    protected function get_repeating_is_enabled_attribute(): bool {
        return (bool) $this->get_attributes_raw()['repeating_is_enabled'];
    }

    /**
     * Cast repeating_is_limited to bool type.
     *
     * @return bool|null
     */
    protected function get_repeating_is_limited_attribute(): ?bool {
        $value = $this->get_attributes_raw()['repeating_is_limited'];
        if (is_null($value)) {
            return null;
        } else {
            return (bool) $this->get_attributes_raw()['repeating_is_limited'];
        }
    }

    /**
     * Cast schedule_use_anniversary to bool type, and guard from being true
     * when using fixed schedule.
     *
     * @return bool
     */
    protected function get_schedule_use_anniversary_attribute(): bool {
        if ($this->schedule_is_fixed) {
            return false;
        }

        return (bool) $this->get_attributes_raw()['schedule_use_anniversary'];
    }

}
