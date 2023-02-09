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

use core\orm\entity\relations\belongs_to;
use mod_approval\entity\has_active_trait;

/**
 * Approval Workflow Stage Formview (form field) entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property string $field_key Form field key (from JSON schema) this formview references
 * @property int $workflow_stage_id Parent workflow_stage ID
 * @property bool $required Is the field required at this stage?
 * @property bool $disabled Is the field disabled at this stage?
 * @property string|null $default_value Override the default value at this stage
 * @property bool $active Is this formview active or not?
 * @property-read int $created Created timestamp
 * @property-read int $updated Last modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read workflow_stage $workflow_stage Parent workflow_stage
 *
 * Functions:
 * @method static workflow_stage_formview_repository repository()
 */
class workflow_stage_formview extends \core\orm\entity\entity {

    use has_active_trait;

    public const TABLE = 'approval_workflow_stage_formview';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Workflow_stage this formview belongs to.
     *
     * @return belongs_to the relationship.
     */
    public function workflow_stage(): belongs_to {
        return $this->belongs_to(workflow_stage::class, 'workflow_stage_id');
    }

    /**
     * Bool casting for required field get.
     *
     * @return bool
     */
    public function get_required_attribute(): bool {
        return $this->get_attributes_raw()['required'] ?? false;
    }

    /**
     * Bool casting for required field set.
     *
     * @param bool $value
     * @return bool
     */
    public function set_required_attribute(bool $value): bool {
        return (bool) $this->set_attribute_raw('required', $value);
    }

    /**
     * Bool casting for disabled field get.
     *
     * @return bool
     */
    public function get_disabled_attribute(): bool {
        return $this->get_attributes_raw()['disabled'] ?? false;
    }

    /**
     * Bool casting for disabled field set.
     *
     * @param bool $value
     * @return bool
     */
    public function set_disabled_attribute(bool $value): bool {
        return (bool) $this->set_attribute_raw('disabled', $value);
    }
}
