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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\entity;

use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\participant_instance;

/**
 * Linked review content response entity.
 *
 * Properties:
 *
 * @property int $linked_review_content_id
 * @property int $child_element_id
 * @property int $participant_instance_id
 * @property string $response_data
 * @property int $created_at
 * @property int $updated_at
 * @property-read linked_review_content $linked_review_content
 * @property-read element $child_element
 * @property-read participant_instance $participant_instance
 *
 * @package performelement_linked_review\entity
 */
class linked_review_content_response extends entity {

    public const TABLE = 'perform_element_linked_review_content_response';
    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';
    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * Get the linked review content.
     *
     * @return belongs_to
     */
    public function linked_review_content(): belongs_to {
        return $this->belongs_to(linked_review_content::class, 'linked_review_content_id');
    }

    /**
     * Get the element.
     *
     * @return belongs_to
     */
    public function child_element(): belongs_to {
        return $this->belongs_to(element::class, 'child_element_id');
    }

    /**
     * Get the participant instance.
     *
     * @return belongs_to
     */
    public function participant_instance(): belongs_to {
        return $this->belongs_to(participant_instance::class, 'participant_instance_id');
    }

    /**
     * Updates an existing record or creates a new one.
     *
     * @param int $linked_content_id
     * @param int $child_element_id
     * @param int $participant_instance_id
     * @param string|null $response_data
     * @return static
     */
    public static function update_or_create_response(
        int $linked_content_id,
        int $child_element_id,
        int $participant_instance_id,
        ?string $response_data
    ): self {
        $content_response_entity = linked_review_content_response::repository()
            ->where('linked_review_content_id', $linked_content_id)
            ->where('child_element_id', $child_element_id)
            ->where('participant_instance_id', $participant_instance_id)
            ->get()
            ->first();

        if ($content_response_entity) {
            $content_response_entity->response_data = $response_data;
        } else {
            $content_response_entity = new self();
            $content_response_entity->linked_review_content_id = $linked_content_id;
            $content_response_entity->child_element_id = $child_element_id;
            $content_response_entity->participant_instance_id = $participant_instance_id;
            $content_response_entity->response_data = $response_data;
        }
        $content_response_entity->save();

        return $content_response_entity;
    }
}
