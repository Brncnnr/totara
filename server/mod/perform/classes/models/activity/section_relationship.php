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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use core\orm\entity\model;
use core\orm\query\builder;
use mod_perform\constants;
use mod_perform\entity\activity\section_relationship as section_relationship_entity;
use mod_perform\hook\pre_section_relationship_deleted;
use mod_perform\section_relationship_deletion_exception;
use totara_core\relationship\relationship as core_relationship_model;

/**
 * Class section_relationship
 *
 * A relationship is used to define which participants should participate in a section.
 *
 * @property-read int $id ID
 * @property-read int $section_id
 * @property-read int $core_relationship_id
 * @property-read bool $can_view
 * @property-read bool $can_answer
 * @property-read section $section
 * @property-read core_relationship_model $core_relationship
 *
 * @package mod_perform\models\activity
 */
class section_relationship extends model {

    protected $entity_attribute_whitelist = [
        'id',
        'section_id',
        'core_relationship_id',
        'can_view',
        'can_answer',
    ];

    protected $model_accessor_whitelist = [
        'section',
        'core_relationship',
        'is_subject',
    ];

    /**
     * @var section_relationship_entity
     */
    protected $entity;

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return section_relationship_entity::class;
    }

    /**
     * Create a section relationship
     *
     * @param int $section_id
     * @param int $core_relationship_id
     * @param bool $can_view
     * @param bool $can_answer
     * @return static
     */
    public static function create(
        int $section_id,
        int $core_relationship_id,
        bool $can_view = false,
        bool $can_answer = true
    ): self {
        $section = section::load_by_id($section_id);
        $activity = activity::load_by_id($section->activity_id);
        require_capability('mod/perform:manage_activity', $activity->get_context());

        return builder::get_db()->transaction(function () use ($section_id, $core_relationship_id, $can_view, $can_answer) {
            $section_relationship_entity = section_relationship_entity::repository()
                ->where('section_id', $section_id)
                ->where('core_relationship_id', $core_relationship_id)
                ->get()
                ->first();

            // Create section_relationship record only if it doesn't exist already.
            if (!$section_relationship_entity) {
                $section_relationship_entity = new section_relationship_entity();
                $section_relationship_entity->section_id = $section_id;
                $section_relationship_entity->core_relationship_id = $core_relationship_id;
                $section_relationship_entity->can_view = $can_view;
                $section_relationship_entity->can_answer = $can_answer;
                $section_relationship_entity->save();
            }

            return self::load_by_entity($section_relationship_entity);
        });
    }

    /**
     * Update a section relationship's can_view
     *
     * @param bool $can_view
     * @param bool $can_answer
     * @return void
     */
    public function update_attribution_settings(bool $can_view, bool $can_answer = true): void {
        $entity = $this->entity;
        $entity->can_view = $can_view;
        $entity->can_answer = $can_answer;

        $entity->save();
    }

    /**
     * Delete a section relationship
     *
     * @param int $section_id
     * @param int $core_relationship_id
     * @param bool $ignore_conflicts_on_same_section
     * @return bool
     * @throws section_relationship_deletion_exception
     */
    public static function delete_with_properties(int $section_id, int $core_relationship_id, bool $ignore_conflicts_on_same_section = false): bool {
        $section = section::load_by_id($section_id);
        $activity = activity::load_by_id($section->activity_id);
        require_capability('mod/perform:manage_activity', $activity->get_context());

        /** @var section_relationship_entity $section_relationship_entity */
        $section_relationship_entities = section_relationship_entity::repository()
            ->where('section_id', $section_id)
            ->where('core_relationship_id', $core_relationship_id)
            ->with('core_relationship')
            ->get()
            ->all();

        foreach ($section_relationship_entities as $section_relationship_entity) {
            $hook = new pre_section_relationship_deleted(section_relationship::load_by_entity($section_relationship_entity), $ignore_conflicts_on_same_section);
            $hook->execute();
            $reasons = $hook->get_reasons();

            if (!empty($reasons)) {
                $reason = $hook->get_first_reason();
                throw new section_relationship_deletion_exception($reason->get_description(), $reason);
            }
            $section_relationship_entity->delete();
        }

        return true;
    }

    /**
     * Get the section that this section relationship belongs to
     *
     * @return section
     */
    public function get_section(): section {
        return section::load_by_entity($this->entity->section);
    }

    /**
     * Get the core relationship which is being linked to the section
     *
     * @return core_relationship_model
     */
    public function get_core_relationship(): core_relationship_model {
        return core_relationship_model::load_by_entity($this->entity->core_relationship);
    }

    /**
     * Is this relationship a "subject".
     *
     * @return bool
     */
    public function get_is_subject(): bool {
        return $this->entity->core_relationship->idnumber === constants::RELATIONSHIP_SUBJECT;
    }

    /**
     * Delete the section relationship
     */
    public function delete(): void {
        $this->entity->delete();
    }
}
