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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\entity\form;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many;
use core\orm\entity\relations\has_one;
use mod_approval\entity\has_active_trait;
use mod_approval\entity\workflow\workflow;
use mod_approval\model\status;

/**
 * Approval workflow form entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property string $plugin_name Form plugin name
 * @property string $title Human-readable form name
 * @property bool $active Is this form active or not?
 * @property-read int $created Created timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read collection|form_version[] $versions Collection of form_versions for this form
 * @property-read collection|workflow[] $workflows Collection of workflows which use this form
 */
final class form extends entity {

    use has_active_trait;

    public const TABLE = 'approval_form';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;


    /**
     * Relationship with form_version entities.
     *
     * @return has_many
     */
    public function versions(): has_many {
        return $this->has_many(form_version::class, 'form_id');
    }

    /**
     * Relationship with workflow entities.
     *
     * @return has_many
     */
    public function workflows(): has_many {
        return $this->has_many(workflow::class, 'form_id');
    }
}
