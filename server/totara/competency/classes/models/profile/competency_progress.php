<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com
 * @package totara_competency
 */

namespace totara_competency\models\profile;

use coding_exception;
use core\entity\user;
use core\orm\collection;
use stdClass;
use totara_competency\data_providers\assignments;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\user_group_factory;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * This is a profile progress item model scaffolding, it has the following properties available:
 *
 *  - Assignments -> [Assignment] - a collection of related assignment models
 *  - Overall progress -> int - Overall progress value per this group
 *
 * @property-read collection $assignments Collection of assignments for this user group
 * @property-read competency $competency The competency this progress is related to
 * @property-read collection $items Collection of assignments for this user group
 * @property-read collection $filters Collection of filters
 * @property-read string $latest_achievement Latest achieved competency name (if any)
 * @package totara_competency\models
 */
class competency_progress {

    /**
     * @var collection
     */
    protected $assignments;

    /**
     * @var competency
     */
    protected $competency;

    /**
     * @var competency_achievement
     */
    protected $achievement;

    /**
     * @var scale_value|null
     */
    protected $my_value;

    /**
     * Attributes available publicly on the model
     *
     * @var array
     */
    protected $public_attributes = [
        'assignments',
        'competency',
        'achievement',
        'my_value',
    ];

    /**
     * competency_progress constructor.
     *
     * @param assignment_model $assignment Assignment
     */
    public function __construct(assignment_model $assignment) {
        $this->competency = $assignment->competency;
        $this->achievement = $assignment->current_achievement;
        $this->my_value = $assignment->current_achievement->value ?? null;
        $this->assignments = new collection([$assignment]);
    }

    /**
     * Return assignments for a given competency
     *
     * @return collection
     */
    public function get_assignments(): collection {
        return $this->assignments;
    }

    /**
     * Build a collection of competency progress models using assignments
     *
     * @param collection|assignment_model[] $assignments
     * @return collection
     */
    public static function build_from_assignments(collection $assignments): collection {
        $progress = new collection();
        $user_group_entities = user_group_factory::load_user_groups($assignments);

        // Explicitly sort by first assigned first.
        $assignments->sort(function (assignment $a, assignment $b) {
            if ($a->created_at === $b->created_at) {
                return $a->id - $b->id;
            }

            return $a->created_at - $b->created_at;
        });

        /** @var assignment $assignment */
        foreach ($assignments as $assignment) {
            $assignment_model = assignment_model::load_by_entity($assignment);

            $user_group = $user_group_entities[$assignment->user_group_type][$assignment->user_group_id] ?? null;

            if ($user_group) {
                $assignment_model->set_user_group_entity($user_group);
            }

            if (!$progress->item($assignment->competency_id)) {
                $progress->set(new static($assignment_model), $assignment->competency_id);
            } else {
                /** @var self $item */
                $item = $progress->item($assignment->competency_id);
                $item->append_assignment($assignment_model);
            }
        }

        return $progress;
    }

    /**
     * Build progress for one competency for a given user
     *
     * @param int|user|stdClass $user User id or object
     * @param int $competency_id Competency id
     * @param int|null $status optional status filter
     * @return competency_progress|null
     */
    public static function build_for_competency($user, int $competency_id, int $status = null): ?self {
        $filters = [
            'competency_id' => $competency_id,
        ];

        if ($status !== null) {
            if ($status !== assignment::STATUS_ACTIVE && $status !== assignment::STATUS_ARCHIVED) {
                throw new coding_exception('Invalid status filter value');
            }

            $filters['status'] = $status;
        }

        $assignments = assignments::for($user)->set_filters($filters)->fetch()->get();

        // An in progress competency was found, return it.
        if ($assignments->count() > 0) {
            return static::build_from_assignments($assignments)->first();
        }

        /** @var competency $competency */
        $competency = competency::repository()->find($competency_id);

        // The requested competency exits, but it has no progress/assignments linked to it.
        if ($competency !== null) {
            return new unassigned_competency_progress($competency);
        }

        // The competency was not found at all.
        return null;
    }

    /**
     * Get attribute
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        if ($name === 'items') {
            $name = 'assignments';
        }

        // Calling ?? will automatically trigger isset allowing only public attributes
        return $this->{$name} ?? null;
    }

    /**
     * Check whether an attribute is set
     *
     * @param $name
     * @return bool
     */
    public function __isset($name) {
        if ($name === 'items') {
            $name = 'assignments';
        }

        return in_array($name, $this->public_attributes);
    }

    /**
     * We consider having assignment with an achievement as being proficient.
     * @return bool
     */
    public function is_proficient(): bool {
        $found = $this->assignments->find(function (assignment_model $assignment): bool {
            return $assignment->current_achievement_is_proficient();
        });

        return $found !== null;
    }

    /**
     * Append assignment to the current progress item model.
     * Only adds the assignment to the collection if there's none with the same
     * user_group_type and user_group_id to not show duplicates.
     *
     * @param assignment_model $assignment
     * @return $this
     */
    protected function append_assignment(assignment_model $assignment): competency_progress {
        $this->assignments->append($assignment);

        return $this;
    }

}