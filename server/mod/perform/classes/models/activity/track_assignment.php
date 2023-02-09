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

namespace mod_perform\models\activity;

use coding_exception;
use core\collection;
use core\orm\entity\model;
use mod_perform\entity\activity\track_assignment as track_assignment_entity;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\event\track_user_group_assigned;
use mod_perform\event\track_user_group_unassigned;
use mod_perform\user_groups\grouping;

/**
 * Represents a single performance activity track assignment.
 * @property-read grouping $group
 * @property-read track $track
 * @property-read \core\orm\collection|track_user_assignment[] $user_assignments
 */
class track_assignment extends model {
    /**
     * @var track_assignment_entity
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected $entity_attribute_whitelist = [
        'id',
        'track_id',
        'type',
        'user_group_type',
        'user_group_id',
        'created_by',
        'created_at',
        'updated_at',
        'expand',
    ];

    protected $model_accessor_whitelist = [
        'user_assignments',
        'track',
        'group'
    ];

    /**
     * {@inheritdoc}
     */
    protected static function get_entity_class(): string {
        return track_assignment_entity::class;
    }

    /**
     * Creates a new track assignment with the given field values.
     *
     * @param track $parent parent track.
     * @param int $type assignment type; one of the track_assignment_type enums.
     * @param grouping $group assignment group details.
     *
     * @return track_assignment the newly created track.
     */
    public static function create(
        track $parent,
        int $type,
        grouping $group
    ): track_assignment {
        global $USER;

        if (!in_array($type, track_assignment_type::get_allowed())) {
            throw new coding_exception("unknown assignment type: '$type'");
        }

        $parent->require_manage('create track assignment');

        $entity = new track_assignment_entity();
        $entity->track_id = $parent->get_id();
        $entity->type = $type;
        $entity->user_group_type = $group->get_type();
        $entity->user_group_id = $group->get_id();
        $entity->expand = true;
        $entity->created_by = $USER->id;
        $entity->save();

        $assignment = new track_assignment($entity);
        $parent->refresh();

        track_user_group_assigned::create_from_track_assignment($assignment)
            ->trigger();

        return $assignment;
    }

    /**
     * Retrieves assignments by their parent track.
     *
     * @param track $parent parent track.
     * @param int|null $type optional assignment type to filter by; one of the
     *        track_assignment_type enums.
     * @param grouping|null $group optional assignment group details to filter by.
     *
     * @return collection retrieved assignments.
     */
    public static function load_by_track(
        track $parent,
        ?int $type = null,
        ?grouping $group = null
    ): collection {
        if ($type && !in_array($type, track_assignment_type::get_allowed())) {
            throw new coding_exception("unknown assignment type: '$type'");
        }

        $parent->require_manage('load assignments by track');

        $builder = track_assignment_entity::repository()
            ->where('track_id', $parent->get_id());

        if ($type) {
            $builder->where('type', $type);
        }

        if ($group) {
            $builder
                ->where('user_group_id', $group->get_id())
                ->where('user_group_type', $group->get_type());
        }

        return $builder
            ->get()
            ->map_to(track_assignment::class);
    }

    /**
     * Mark this track assignment to be expanded on the next expand task run
     *
     * @return $this
     */
    public function mark_as_expand(): self {
        $this->entity->expand = true;
        $this->entity->save();

        return $this;
    }

    /**
     * Get the track this assignment belongs to
     *
     * @return track
     */
    public function get_track(): track {
        return track::load_by_entity($this->entity->track);
    }

    /**
     * Get the group for this assignment
     *
     * @return grouping
     */
    public function get_group(): grouping {
        return grouping::by_type(
            $this->entity->user_group_type,
            $this->entity->user_group_id,
            $this
        );
    }

    /**
     * Removes this assignment. Note: after this, the model is invalid.
     */
    public function remove(): void {
        track::load_by_entity($this->entity->track)
            ->require_manage('remove track assignment');

        $event = track_user_group_unassigned::create_from_track_assignment($this);
        $this->entity->delete();
        $event->trigger();
    }

    /**
     * Forces the model to reload its data from the repository.
     *
     * @return track_assignment this assignment.
     */
    public function refresh(): track_assignment {
        $this->entity->refresh();
        return $this;
    }

    /**
     * Get all assignments that has a specific grouping.
     *
     * @param grouping $grouping
     * @return collection
     */
    public static function get_all_for_grouping(grouping $grouping): collection {
        return track_assignment_entity::repository()
            ->where('user_group_type', $grouping->get_type())
            ->where('user_group_id', $grouping->get_id())
            ->get()
            ->map_to(track_assignment::class);
    }

}
