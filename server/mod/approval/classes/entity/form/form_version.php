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

use core\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use mod_approval\entity\application\application;
use mod_approval\entity\has_status_trait;

/**
 * Approval workflow form version entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property int $form_id Parent form ID
 * @property string $version Version identifying string
 * @property string $json_schema JSON form schema
 * @property int $status Form_version status code (draft|active|archived)
 * @property-read int $created Created timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read form $form Parent form
 * @property-read collection|application[] $applications using this form_version
 */
final class form_version extends entity {

    use has_status_trait;

    public const TABLE = 'approval_form_version';

    public const CREATED_TIMESTAMP = 'created';

    public const UPDATED_TIMESTAMP = 'updated';

    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Each form version belongs to a particular form.
     *
     * @return belongs_to
     */
    public function form(): belongs_to {
        return $this->belongs_to(form::class, 'form_id');
    }

    /**
     * Form_version may have many applications associated with it.
     *
     * @return has_many
     */
    public function applications(): has_many {
        return $this->has_many(application::class, 'form_version_id');
    }
}
