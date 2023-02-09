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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\details;

use coding_exception;
use core\orm\query\builder;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\entity\activity\track_user_assignment as track_user_assignment_entity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use stdClass;

/**
 * @property-read integer $id
 * @property-read integer $subject_user_id
 * @property-read integer|null $job_assignment_id
 * @property-read integer|null $due_date
 * @property-read integer $instance_created_at
 */
class subject_instance_notification {
    /** @var stdClass */
    private $record;

    /**
     * Private constructor.
     *
     * @param stdClass $record
     */
    private function __construct(stdClass $record) {
        $this->record = $record;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if (!property_exists($this->record, $name)) {
            throw new coding_exception("Unknown property {$name}");
        }
        return $this->record->{$name};
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function __isset($name) {
        return property_exists($this->record, $name);
    }

    /**
     * @param string $name
     * @param boolean $value
     */
    public function __set($name, $value) {
        throw new coding_exception('Cannot modify a read-only object');
    }

    /**
     * @param string $name
     */
    public function __unset($name) {
        throw new coding_exception('Cannot modify a read-only object');
    }

    /**
     * Load subject_instance_notification records related to the activity.
     *
     * @param activity_entity $activity
     * @return subject_instance_notification[]
     *
     * @deprecated since Totara 13.2.
     */
    public static function load_by_activity(activity_entity $activity): array {
        debugging(
            'subject_instance_notification::load_by_activity has been deprecated. Use load_by_subject_instance() instead',
            DEBUG_DEVELOPER
        );

        return builder::table(subject_instance_entity::TABLE, 'si')
            ->join([track_user_assignment_entity::TABLE, 'tua'], 'si.track_user_assignment_id', 'tua.id')
            ->join([track_entity::TABLE, 't'], 'tua.track_id', 't.id')
            ->where('t.activity_id', $activity->id)
            ->where_null('si.completed_at')
            ->where('tua.deleted', false)
            ->select(['si.id', 'tua.subject_user_id', 'tua.job_assignment_id', 'si.due_date AS due_date'])
            ->add_select('si.created_at AS instance_created_at')
            ->map_to(function ($item) {
                return new self($item);
            })
            ->get()
            ->all(false);
    }


    /**
     * Creates an instance of this object from the incoming subject instance.
     *
     * @param subject_instance_entity $subject_instance the reference object.
     *
     * @return subject_instance_notification an instance of this class.
     */
    public static function load_by_subject_instance(subject_instance_entity $subject_instance): self {
        $record = new stdClass();
        $record->id = $subject_instance->id;
        $record->subject_user_id = $subject_instance->subject_user_id;
        $record->job_assignment_id = $subject_instance->user_assignment->job_assignment_id;
        $record->due_date = $subject_instance->due_date;
        $record->instance_created_at = $subject_instance->created_at;

        return new self($record);
    }
}