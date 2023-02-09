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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use core\collection;
use core\entity\user;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\response\participant_section;
use mod_perform\state\participant_instance\participant_instance_availability;
use mod_perform\state\participant_instance\participant_instance_progress;
use mod_perform\state\state;
use ReflectionClass;
use totara_core\relationship\relationship as relationship_model;

/**
 * Class anonymous_participant_instance
 *
 * @package mod_perform\models\activity
 *
 * @property-read int $id
 * @property-read int $progress
 * @property-read int $participant_id
 * @property-read subject_instance $subject_instance
 * @property-read int $subject_instance_id
 * @property-read user $participant
 * @property-read collection|participant_section[] $participant_sections
 * @property-read string $progress_status internal name of current progress state
 * @property-read participant_instance_progress|state $progress_state Current progress state
 * @property-read participant_instance_availability|state $availability_state Current availability state
 * @property-read relationship_model $core_relationship The core relationship
 */
class anonymous_participant_instance extends participant_instance {

    /**
     * @var participant_instance
     */
    private $original;

    public function __construct(participant_instance $original) {
        $this->original = $original;
        parent::__construct($original->entity);
    }

    /**
     * @var participant_instance_entity
     */
    protected $entity;

    protected $entity_attribute_whitelist = [
        'id',
        'progress',
        'availability',
        'subject_instance_id',
        'core_relationship_id',
        'created_at',
    ];

    protected $model_accessor_whitelist = [
        'progress_status',
        'availability_status',
        'progress_state',
        'availability_state',
        'subject_instance',
        'participant',
        'participant_id',
        'participant_source',
        'core_relationship',
        'participant_sections',
        'is_for_current_user',
        'is_overdue',
    ];

    /**
     * Get the object type.
     * By convention that is the object's short class name.
     *
     * @return string
     */
    protected function get_object_type(): string {
        return (new ReflectionClass($this->original))->getShortName();
    }

    /**
     * @inheritDoc
     */
    public function get_participant(): ?participant {
        return null;
    }

    public function get_core_relationship(): ?relationship_model {
        return null;
    }

    public function get_participant_id(): ?int {
        return null;
    }

    public function get_participant_source(): ?int {
        return null;
    }


}
