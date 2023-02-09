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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Section element response entity
 *
 * Properties:
 * @property-read int $id ID
 * @property int section_element_id $context_id the section element this is a answer to
 * @property int participant_instance_id $plugin_name the participant instance for the person making this answer
 * @property string|null $response_data JSON encoded question response data
 * @property-read section_element $section_element
 * @property-read participant_instance $participant_instance
 *
 * @method static element_response_repository repository()
 *
 * @package mod_perform\entity
 */
class element_response extends entity {
    public const TABLE = 'perform_element_response';
    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Get the element_identifier
     *
     * @return belongs_to
     */
    public function section_element(): belongs_to {
        return $this->belongs_to(section_element::class, 'section_element_id');
    }

    /**
     * Get the participant instance.
     *
     * @return belongs_to
     */
    public function participant_instance(): belongs_to {
        return $this->belongs_to(participant_instance::class, 'participant_instance_id');
    }

}
