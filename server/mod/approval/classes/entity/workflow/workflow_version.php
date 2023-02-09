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
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_one;
use mod_approval\entity\form\form_version;
use mod_approval\entity\application\application;
use mod_approval\entity\has_status_trait;

/**
 * Approval Workflow Version entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $workflow_id Parent workflow ID
 * @property int $form_version_id ID of the form_version this workflow uses
 * @property int $status Workflow status code (draft|active|archived)
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow $workflow Parent workflow
 * @property-read form_version $form_version Form_version this workflow uses
 * @property-read collection|workflow_stage[] $stages Sorted collection of all stages of this workflow_version
 * @property-read collection|workflow_stage[] $active_stages Sorted collection of active stages of this workflow_version
 * @property-read collection|application[] $applications using this workflow_version
 */
class workflow_version extends entity {

    use has_status_trait;

    public const TABLE = 'approval_workflow_version';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Workflow this is a version of.
     *
     * @return belongs_to the relationship.
     */
    public function workflow(): belongs_to {
        return $this->belongs_to(workflow::class, 'workflow_id');
    }

    /**
     * Form_version associated with this workflow_version.
     *
     * @return has_one the relationship.
     */
    public function form_version(): has_one {
        return $this->has_one(form_version::class, 'id', 'form_version_id');
    }

    /**
     * All stages in this workflow_version, sorted by sortorder.
     *
     * @return has_many
     */
    public function stages(): has_many {
        return $this->has_many(workflow_stage::class, 'workflow_version_id')
            ->order_by('sortorder')
            ->order_by('id');
    }

    /**
     * Active stages in this workflow_version, sorted by sortorder.
     *
     * @return has_many
     */
    public function active_stages(): has_many {
        return $this->has_many(workflow_stage::class, 'workflow_version_id')
            ->where('active', '=', 1)
            ->order_by('sortorder')
            ->order_by('id');
    }

    /**
     * Applications using this workflow_version.
     * @return has_many
     */
    public function applications(): has_many {
        return $this->has_many(application::class, 'workflow_version_id')->order_by('id');
    }
}
