<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\entity;


use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * entity audience
 *
 * @property-read int $id ID
 * @property int $assignment_id
 * @property int $user_id
 * @property int $competency_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read string unique_identifier
 * @property-read assignment $assignment
 *
 * @method static competency_assignment_user_repository repository()
 *
 * @package totara_competency\entity
 */
class competency_assignment_user extends entity {

    public const TABLE = 'totara_competency_assignment_users';

    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
    public const SET_UPDATED_WHEN_CREATED = true;

    protected $extra_attributes = [
        'unique_identifier'
    ];

    /**
     * Creates a identifier which is unique over all cols except timestamps and id
     * @return string
     */
    protected function get_unique_identifier_attribute(): string {
        return md5($this->user_id.$this->assignment_id.$this->competency_id);
    }

    public function assignment(): belongs_to {
        return $this->belongs_to(assignment::class, 'assignment_id');
    }

}
