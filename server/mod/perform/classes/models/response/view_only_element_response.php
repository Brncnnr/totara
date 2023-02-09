<?php
/*
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

namespace mod_perform\models\response;

use core\collection;
use core\orm\entity\model;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\section_element;

/**
 * Represents the responses (or lack of) to an element from the
 * perspective of a non participant (reporter).
 *
 * @property-read int section_element_id Foreign key
 * @property-read section_element $section_element The parent section element
 * @property-read collection|element_validation_error[] $validation_errors A collection of element_validation_errors
 * @property-read string $response_data Raw JSON encoded response data
 * @property-read element $element The element this is a response to
 * @property-read collection|participant_instance[] $visible_to
 * @property-read collection|responder_group[] $other_responder_groups
 *                Other responses grouped by relationship types (Manager/Appraiser)
 * @property-read int $sort_order The order this element should appear in the section
 * @package mod_perform\models\activity
 */
class view_only_element_response extends model implements section_element_responses_interface {

    protected $entity_attribute_whitelist = [];

    protected $model_accessor_whitelist = [
        'section_element',
        'section_element_id',
        'element',
        'validation_errors',
        'participant_instance',
        'other_responder_groups',
        'visible_to',
        'sort_order',
    ];

    /**
     * @var section_element_entity
     */
    protected $entity;

    /**
     * @var collection|responder_group[]
     */
    private $other_responder_groups;


    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return section_element::class;
    }

    /**
     * element_response constructor.
     *
     * @param section_element_entity $section_element_entity
     * @param collection|responder_group[] $other_responder_groups
     */
    public function __construct(
        section_element_entity $section_element_entity,
        collection $other_responder_groups
    ) {
        $this->entity = $section_element_entity;
        $this->other_responder_groups = $other_responder_groups;
    }

    public function get_section_element(): section_element {
        return new section_element($this->entity);
    }

    /**
     * @return collection|participant_instance[]
     */
    public function get_visible_to(): collection {
        // This is just stubbed for now.
        return new collection();
    }

    public function get_section_element_id(): int {
        // Done with accessor rather than attribute whitelist so we can satisfy the section_element_responses interface.
        return $this->entity->id;
    }

    /**
     * Get the order this response should appear in the section.
     * @return int
     */
    public function get_sort_order(): int {
        // Done with accessor rather than attribute whitelist so we can satisfy the section_element_responses interface.
        return $this->entity->sort_order;
    }

    public function get_element(): ?element {
        $element = null;
        if ($this->entity->element) {
            $element = new element($this->entity->element);
        }

        return $element;
    }

    /**
     * Get the other participants responses grouped by relationship types (Manager/Appraiser).
     * @return collection
     */
    public function get_other_responder_groups(): ?collection {
        return $this->other_responder_groups;
    }
}
