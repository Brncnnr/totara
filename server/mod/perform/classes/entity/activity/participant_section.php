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

namespace mod_perform\entity\activity;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use mod_perform\models\activity\participant_instance as participant_instance_entity;

/**
 * Participant section entity
 *
 * @property int $section_id ID of activity section
 * @property int $participant_instance_id ID of participant instance
 * @property int $progress
 * @property int $availability
 * @property int $created_at
 * @property int $updated_at
 *
 * @method static participant_section_repository repository()
 *
 * @property-read section $section
 * @property-read participant_instance $participant_instance
 * @property-read collection|section_element[] $section_elements
 *
 * @package mod_perform\entity
 */
class participant_section extends entity {
    public const TABLE = 'perform_participant_section';
    public const CREATED_TIMESTAMP = 'created_at';
    public const UPDATED_TIMESTAMP = 'updated_at';

    /**
     * Relationship with section entities.
     *
     * @return belongs_to
     */
    public function section(): belongs_to {
        return $this->belongs_to(section::class, 'section_id');
    }

    /**
     * Relationship with section entities.
     *
     * @return belongs_to
     */
    public function participant_instance(): belongs_to {
        return $this->belongs_to(participant_instance::class, 'participant_instance_id');
    }

    /**
     * Relationship with the section elements entities.
     *
     * @return has_many
     */
    public function section_elements(): has_many {
        return $this->has_many(
            section_element::class,
            'section_id',
            'section_id'
        )->order_by('sort_order');
    }

    /**
     * Get all participant instances (same end user, different relationship),
     * that this section can be answered by the linked participant instance.
     *
     * @return collection|participant_instance_entity[]
     */
    public function get_answerable_participant_instances(): collection {
        $subject_instance_id = $this->participant_instance->subject_instance_id;
        $participant_id = $this->participant_instance->participant_id;
        $source = $this->participant_instance->participant_source;
        $section_id = $this->section_id;

        return $this->participant_instance::repository()
            ->as('pi')
            ->where('pi.subject_instance_id', $subject_instance_id) // For performance so we can hit the subject_instance_id index.
            ->where('pi.participant_id', $participant_id) // Same end user.
            ->where('pi.participant_source', $source) // Same end user.
            ->join([self::TABLE, 'ps'], 'ps.participant_instance_id', 'pi.id')
            ->where('ps.section_id', $section_id) // Same section.
            ->get(true);
    }

}