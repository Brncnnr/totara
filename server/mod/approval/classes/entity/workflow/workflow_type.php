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
use core\orm\entity\relations\has_many;
use mod_approval\entity\has_active_trait;

/**
 * Approval Workflow Type entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property string $name Human readable type name
 * @property string|null $description Workflow type description
 * @property bool $active Is this workflow_type active or not?
 * @property-read int $created Created timestamp
 *
 * Relationships:
 * @property-read collection|workflow[] $workflows Collection of workflows of this type
 */
class workflow_type extends \core\orm\entity\entity {

    use has_active_trait;

    public const TABLE = 'approval_workflow_type';

    public const CREATED_TIMESTAMP = 'created';

    /**
     * Workflows of this type
     *
     * @return has_many the relationship.
     */
    public function workflows(): has_many {
        return $this->has_many(workflow::class, 'workflow_type_id')->order_by('id');
    }
}
