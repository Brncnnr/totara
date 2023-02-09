<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\entity\assignment;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use mod_approval\entity\has_status_trait;
use mod_approval\entity\workflow\workflow;
use mod_approval\entity\application\application;
use core\entity\course;


/**
 * Approval workflow assignment entity (approval table)
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $course Course ID
 * @property string $name Human-readable name
 * @property string $id_number Organization|position|cohort database shortname record => external reference number
 * @property bool $is_default Flag indicating this is the default assignment for the workflow
 * @property int $assignment_type Assignment type code (organization|position|cohort)
 * @property int $assignment_identifier ID of assignment database record
 * @property int $status Assignment status code (draft|active|archived)
 * @property-read int $created Created timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 * @property bool $to_be_deleted Flag that marks this assignment as waiting to be deleted
 *
 * Relationships:
 * @property-read course $container Parent course container
 * @property-read workflow $workflow Parent workflow
 * @property-read collection|assignment_approver[] $approvers Collection of approvers in this assignment
 * @property-read collection|assignment_approver[] $active_approvers Collection of active approvers in this assignment
 * @property-read collection|application[] $applications Collection of applications associated with this assignment
 *
 * @package mod_approval\entity
 */
class assignment extends entity {

    use has_status_trait;

    public const TABLE = 'approval';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Assignments are mod_approval activities that belong to a course container.
     *
     * @return belongs_to
     */
    public function container(): belongs_to {
        return $this->belongs_to(course::class, 'course', 'id');
    }

    /**
     * Assignments also belong to a parent workflow, which is related via course_id.
     *
     * @return belongs_to
     */
    public function workflow(): belongs_to {
        return $this->belongs_to(workflow::class, 'course', 'course_id');
    }

    /**
     * All approvers associated with this assignment.
     *
     * @return has_many
     */
    public function approvers(): has_many {
        return $this->has_many(assignment_approver::class, 'approval_id')->order_by('id');
    }

    /**
     * Active approvers associated with this assignment.
     *
     * @return has_many
     */
    public function active_approvers(): has_many {
        return $this->has_many(assignment_approver::class, 'approval_id')->where('active', '=', 1)->order_by('id');
    }

    /**
     * Applications associated with this assignment.
     *
     * @return has_many
     */
    public function applications(): has_many {
        return $this->has_many(application::class, 'approval_id')->order_by('id');
    }

    /**
     * Bool casting for is_default get.
     *
     * @return bool
     */
    public function get_is_default_attribute(): bool {
        return $this->get_attributes_raw()['is_default'] ?? false;
    }

    /**
     * Bool casting for is_default set.
     *
     * @param bool $value
     * @return bool
     */
    public function set_is_default_attribute(bool $value): bool {
        return (bool) $this->set_attribute_raw('is_default', $value);
    }

    /**
     * Cast assignment_type as int.
     *
     * @return int
     */
    public function get_assignment_type_attribute(): int {
        return (int)$this->get_attributes_raw()['assignment_type'];
    }

    /**
     * Bool casting for to_be_deleted get.
     *
     * @return bool
     */
    public function get_to_be_deleted_attribute(): bool {
        return $this->get_attributes_raw()['to_be_deleted'] ?? false;
    }

    /**
     * Bool casting for to_be_deleted set.
     *
     * @param bool $value
     * @return bool
     */
    public function set_to_be_deleted_attribute(bool $value): bool {
        return (bool) $this->set_attribute_raw('to_be_deleted', $value);
    }
}
