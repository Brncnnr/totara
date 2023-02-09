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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\entity;
use core\orm\entity\relations\has_one;
use mod_perform\models\activity\participant_source;

/**
 * External participant instance.
 *
 * @property-read int $id record id
 * @property string $name participant name
 * @property string $email participant email
 * @property string $token participant login token
 * @property int $created_at
 *
 * @property-read participant_instance $participant_instance
 *
 * @method static external_participant_repository repository
 *
 * @package mod_perform\entity
 */
class external_participant extends entity {
    public const TABLE = 'perform_participant_external';
    public const CREATED_TIMESTAMP = 'created_at';

    /**
    * Relationship with participant instance.
    *
    * @return has_one
    */
    public function participant_instance(): has_one {
        return $this->has_one(participant_instance::class, 'participant_id', 'id')
            ->where('participant_source', participant_source::EXTERNAL);
    }
}