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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\response;

use core\collection;
use core\orm\entity\model;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\models\activity\participant_instance;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\models\activity\section;

/**
 * Class participant_section
 *
 * @property-read int $id ID
 * @property-read int $section_id
 * @property-read int $participant_instance_id
 * @property-read int $progress
 * @property-read int $created_at
 * @property-read int $updated_at
 * @property-read string $progress_status
 * @property-read participant_instance $participant_instance
 * @property-read section $section
 * @property-read section_element_response[] $section_element_responses
 *
 * @package mod_perform\models\activity
 */
class view_only_section extends model implements section_response_interface {

    /**
     * @var participant_section_entity
     */
    protected $entity;

    /**
     * @var collection|section_element_response[]
     */
    protected $element_responses;


    /**
     * @var collection|section_entity[]
     */
    private $siblings;

    protected $entity_attribute_whitelist = [
        'id',
        'section_id',
        'participant_instance_id',
        'progress',
        'availability',
        'created_at',
        'updated_at',
        'participant_instance',
    ];

    protected $model_accessor_whitelist = [
        'section',
        'section_element_responses',
        'siblings',
    ];

    /**
     * @param section_entity $section
     * @param collection|null $element_responses
     * @param collection|null $siblings
     */
    public function __construct(
        section_entity $section,
        collection $element_responses = null,
        collection $siblings = null
    ) {
        parent::__construct($section);
        $this->element_responses = $element_responses ?? new collection();
        $this->siblings = $siblings ?? new collection();
    }

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return section::class;
    }

    public function get_section_id(): int {
        return $this->entity->id;
    }

    public function get_section(): section {
        return new section($this->entity);
    }

    public function get_section_element_responses(): collection {
        return $this->element_responses;
    }

    /**
     * Get the sections that come form the same activity,
     * including the section this model is based on.
     */
    public function get_siblings(): collection {
        return $this->siblings->map_to(section::class);
    }

}
