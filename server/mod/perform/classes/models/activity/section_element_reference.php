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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use Closure;
use coding_exception;
use core\orm\collection;
use core\orm\entity\model;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\section_element_reference as section_element_reference_entity;

/**
 * Represents an element referencing a section element.
 *
 * @property-read int $id ID
 * @property-read int $source_section_element_id
 * @property-read int $referencing_element_id
 *
 * @package performelement_redisplay\model
 */
class section_element_reference extends model {

    /**
     * @var section_element_reference_entity
     */
    protected $entity;

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return section_element_reference_entity::class;
    }

    protected $entity_attribute_whitelist = [
        'id',
        'source_section_element_id',
        'referencing_element_id',
    ];

    /**
     * @param int $source_section_element_id
     * @param int $referencing_element_id
     * @return section_element_reference
     * @throws coding_exception
     */
    public static function create(int $source_section_element_id, int $referencing_element_id): section_element_reference {
        $entity = new section_element_reference_entity();
        $entity->source_section_element_id = $source_section_element_id;
        $entity->referencing_element_id = $referencing_element_id;
        $entity->save();

        return static::load_by_entity($entity);
    }

    /**
     * Run a mix of create/update/deletes. Simply does a delete all by referencing element id, then inserts new records.
     *
     * @param array $source_section_element_ids
     * @param int $referencing_element_id
     * @return collection|section_element_reference[]
     * @throws coding_exception
     */
    public static function patch_multiple(array $source_section_element_ids, int $referencing_element_id): collection {
        section_element_reference_entity::repository()
            ->where('referencing_element_id', $referencing_element_id)
            ->delete();

        return collection::new($source_section_element_ids)->map(function (int $source_section_element_id) use ($referencing_element_id) {
            return static::create($source_section_element_id, $referencing_element_id);
        });
    }

    /**
     * Get element reference relationship by reference element id, for referencing elements that only reference one section element.
     *
     * @param int $referencing_element_id
     * @return section_element_reference
     * @throws coding_exception
     */
    protected static function load_single_by_referencing_element_id(int $referencing_element_id): section_element_reference {
        $entity = section_element_reference_entity::repository()
            ->where('referencing_element_id', $referencing_element_id)
            ->one();

        if ($entity === null) {
            throw new coding_exception('Can not update a section element reference that does not exist');
        }

        return self::load_by_entity($entity);
    }

    /**
     * Update a section element reference.
     *
     * @param int $source_section_element_id
     * @param int $referencing_element_id
     * @return self
     */
    public static function update(int $source_section_element_id, int $referencing_element_id): self {
        $section_element_reference = self::load_single_by_referencing_element_id($referencing_element_id);
        $section_element_reference->entity->source_section_element_id = $source_section_element_id;
        $section_element_reference->entity->update();

        return $section_element_reference;
    }

    /**
     * @param int[] $source_section_element_ids
     * @return section_element[]|collection
     */
    public static function get_source_section_elements(array $source_section_element_ids): collection {
        return section_element_entity::repository()
            ->where_in('id', $source_section_element_ids)
            ->get()
            ->map_to(section_element::class);
    }

    /**
     * Get section elements which are referencing elements in a specific activity
     *
     * @param int $source_activity_id
     * @return collection
     */
    public static function get_section_elements_that_reference_activity(int $source_activity_id): collection {
        return section_element_entity::repository()
            ->with('section.activity')
            ->with('element')
            ->as('referencing_section_element')
            ->join([section_element_reference_entity::TABLE, 'ser'], 'ser.referencing_element_id', 'referencing_section_element.element_id')
            ->join([section_element_entity::TABLE, 'source_section_element'], 'source_section_element.id', 'ser.source_section_element_id')
            ->join([section_entity::TABLE, 'source_section'], 'source_section.id', 'source_section_element.section_id')
            ->where('source_section.activity_id', $source_activity_id)
            ->get()
            ->sort(Closure::fromCallable([__CLASS__, 'activity_section_elements_sort_callback']))
            ->map_to(section_element::class);
    }

    /**
     * Get section elements which are referencing elements in a specific section
     *
     * @param int $source_section_id
     * @return collection
     */
    public static function get_section_elements_that_reference_section(int $source_section_id): collection {
        return section_element_entity::repository()
            ->with('section.activity')
            ->with('element')
            ->as('referencing_section_element')
            ->join([section_element_reference_entity::TABLE, 'ser'], 'ser.referencing_element_id', 'referencing_section_element.element_id')
            ->join([section_element_entity::TABLE, 'source_section_element'], 'source_section_element.id', 'ser.source_section_element_id')
            ->where('source_section_element.section_id', $source_section_id)
            ->get()
            ->sort(Closure::fromCallable([__CLASS__, 'activity_section_elements_sort_callback']))
            ->map_to(section_element::class);
    }

    /**
     * Get section elements which are referencing a specific section element
     *
     * @param int $section_element_id source section element id
     * @return collection
     */
    public static function get_referenced_section_elements_by_source_section_element(int $section_element_id): collection {
        return section_element_entity::repository()
            ->with('section.activity')
            ->with('element')
            ->as('referencing_section_element')
            ->join([section_element_reference_entity::TABLE, 'ser'], 'ser.referencing_element_id', 'referencing_section_element.element_id')
            ->where('ser.source_section_element_id', $section_element_id)
            ->get()
            ->sort(Closure::fromCallable([__CLASS__, 'activity_section_elements_sort_callback']))
            ->map_to(section_element::class);
    }

    /**
     * Sort activities and sections by name
     *
     * @param section_element_entity $first
     * @param section_element_entity $second
     * @return int
     */
    private static function activity_section_elements_sort_callback(section_element_entity $first, section_element_entity $second): int {
        $first_activity_name = strtolower(trim($first->section->activity->name));
        $first_section_name = strtolower(trim($first->section->title));
        $second_activity_name = strtolower(trim($second->section->activity->name));
        $second_section_name = strtolower(trim($second->section->title));

        if ($first_activity_name !== $second_activity_name) {
            return strcmp($first_activity_name, $second_activity_name);
        }

        if ($first_section_name !== $second_section_name) {
            return strcmp($first_section_name, $second_section_name);
        }

        return 0;
    }

    /**
     * Checks if a participant section can access section element through a reference element.
     *
     * @param int $participant_section_id
     * @param int $section_element_id
     * @return bool
     */
    public static function participant_section_can_access_section_element(int $participant_section_id, int $section_element_id): bool {
        return section_element_entity::repository()->as('se')
            ->join([participant_section_entity::TABLE, 'ps'], 'se.section_id', 'ps.section_id')
            ->join([section_element_reference_entity::TABLE, 'ser'], 'se.element_id', 'ser.referencing_element_id')
            ->where('ps.id', $participant_section_id)
            ->where('ser.source_section_element_id', $section_element_id)
            ->exists();
    }

    /**
     * Get the id of the source activity.
     * @return int
     */
    public function get_source_activity_id(): int {
        return $this->entity->source_section_element->section->activity_id;
    }

}
