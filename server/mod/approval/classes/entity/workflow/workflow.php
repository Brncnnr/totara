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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\entity\workflow;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_one;
use mod_approval\entity\assignment\assignment;
use mod_approval\entity\form\form;
use mod_approval\entity\has_active_trait;
use mod_approval\model\status;

/**
 * Approval Workflow entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $course_id Course ID
 * @property int $workflow_type_id Workflow_type ID
 * @property string $name Human-readable name
 * @property string|null $description JSONDoc description
 * @property string $id_number External reference number
 * @property int $form_id Approval form ID
 * @property int|null $template_id ID of workflow used as template, if there is one
 * @property bool $active Is this workflow active or not?
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 * @property bool $to_be_deleted Flag that marks this workflow as waiting to be deleted
 *
 * Relationships:
 * @property-read workflow_type $workflow_type Workflow_type for this workflow
 * @property-read form $form Form this workflow uses
 * @property-read workflow|null $template Workflow that is the template for this workflow, if any
 * @property-read collection|workflow_version[] $versions Collection of versions of this workflow
 * @property-read collection|assignment[] $assignments Collection of assignment entities for this workflow
 * @property-read collection|assignment[] $draft_override_assignments Collection of draft override assignment entities for this workflow
 * @property-read assignment $default_assignment The default assignment entity for this workflow
 * @property-read collection|workflow[] $template_instances Active workflows which use this workflow as a template
 */
class workflow extends entity {

    use has_active_trait;

    public const TABLE = 'approval_workflow';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Workflow type.
     *
     * @return has_one the relationship.
     */
    public function workflow_type(): has_one {
        return $this->has_one(workflow_type::class, 'id', 'workflow_type_id');
    }

    /**
     * Form associated with this workflow.
     *
     * @return has_one the relationship.
     */
    public function form(): has_one {
        return $this->has_one(form::class, 'id', 'form_id');
    }

    /**
     * Template associated with this workflow, if there is one.
     *
     * @return has_one the relationship.
     */
    public function template(): has_one {
        return $this->has_one(workflow::class, 'id', 'template_id');
    }

    /**
     * Versions of this workflow.
     *
     * @return has_many
     */
    public function versions(): has_many {
        return $this->has_many(workflow_version::class, 'workflow_id')->order_by('id');
    }

    /**
     * Assignments for this workflow.
     *
     * @return has_many
     */
    public function assignments(): has_many {
        return $this->has_many(assignment::class, 'course', 'course_id')->order_by('id');
    }

    /**
     * Draft override assignments for this workflow.
     *
     * @return has_many
     */
    public function draft_override_assignments(): has_many {
        return $this->has_many(assignment::class, 'course', 'course_id')
            ->where('status', status::DRAFT)
            ->where('is_default', false)
            ->order_by('id');
    }

    /**
     * Default assignment for this workflow.
     *
     * @return has_one
     */
    public function default_assignment(): has_one {
        return $this->has_one(assignment::class, 'course', 'course_id')
            ->where("is_default", 1);
    }

    /**
     * Workflows which use this workflow as a template.
     *
     * @return has_many
     */
    public function template_instances(): has_many {
        return $this->has_many(workflow::class, 'template_id', 'id')
            ->where('active', '=', 1)
            ->order_by('id');
    }

    /**
     * Bool casting for to_be_deleted field get.
     *
     * @return bool
     */
    public function get_to_be_deleted_attribute(): bool {
        return $this->get_attributes_raw()['to_be_deleted'] ?? false;
    }

    /**
     * Bool casting for to_be_deleted field set.
     *
     * @param bool $value
     * @return bool
     */
    public function set_to_be_deleted_attribute(bool $value): bool {
        return (bool) $this->set_attribute_raw('to_be_deleted', $value);
    }
}
