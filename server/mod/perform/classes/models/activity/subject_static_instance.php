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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use mod_perform\state\state;
use stdClass;
use core\orm\entity\model;
use mod_perform\entity\activity\subject_static_instance as subject_static_instance_entity;
use mod_perform\state\state_aware;
use totara_job\job_assignment;
use totara_job\entity\job_assignment as job_assignment_entity;

/**
 * Class subject_static_instance
 *
 * This class represents a specific subject about a specific person (subject_static_instance)
 *
 * @property-read int $id
 * @property-read subject_instance $subject_instance model created from subject_instance entity
 * @property-read job_assignment|null $job_assignment The job assignment for this subject at instance creation
 * @property-read job_assignment|null $manager_job_assignment The manager job assignment for this subject at instance creation
 * @property-read int $position_id The position ID for this subject at instance creation
 * @property-read int $organisation_id The organisation ID for this subject at instance creation
 *
 * @package mod_perform\models\activity
 */
class subject_static_instance extends model {

    use state_aware;

    protected $entity_attribute_whitelist = [
        'id',
        'subject_instance_id',
        'job_assignment_id',
        'manager_job_assignment_id',
        'position_id',
        'organisation_id',
        'created_at',
        'updated_at',
    ];

    protected $model_accessor_whitelist = [
        'activity',
        'subject_instance',
        'subject_user',
        'job_assignment',
        'manager_job_assignment',
    ];

    /** @var subject_static_instance_entity */
    protected $entity;

    public function __construct(subject_static_instance_entity $subject_instance) {
        parent::__construct($subject_instance);
    }

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return subject_static_instance_entity::class;
    }

    /**
     * @inheritDoc
     */
    public function get_current_state_code(string $state_type): int {
        return $this->{$state_type};
    }

    /**
     * @inheritDoc
     */
    protected function update_state_code(state $state): void {
        $this->entity->{$state::get_type()} = $state::get_code();
        $this->entity->update();
    }

    /**
     * @return subject_instance
     */
    public function get_subject_instance(): subject_instance {
        return subject_instance::load_by_entity($this->entity->subject_instance);
    }

    /**
     * @return job_assignment|null
     */
    public function get_job_assignment(): ?job_assignment {
        if ($this->entity->job_assignment_id === null) {
            return null;
        }

        // Get entity and overwrite with static content.
        $entity = new job_assignment_entity($this->entity->job_assignment_id);
        $entity->positionid = $this->entity->position_id;
        $entity->organisationid = $this->entity->organisation_id;
        $entity->managerjaid = $this->entity->manager_job_assignment_id;

        return job_assignment::from_entity($entity);
    }

    /**
     * @return job_assignment|null
     */
    public function get_manager_job_assignment(): ?job_assignment {
        if ($this->entity->manager_job_assignment_id === null) {
            return null;
        }

        $entity = new job_assignment_entity($this->entity->manager_job_assignment_id);
        return job_assignment::from_entity($entity);
    }

    /**
     * Returns a record representation of the underlying entity
     *
     * @return stdClass
     */
    public function to_record(): stdClass {
        return (object) $this->entity->get_attributes_raw();
    }

}
