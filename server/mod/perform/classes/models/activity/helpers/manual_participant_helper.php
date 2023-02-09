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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

use coding_exception;
use core\orm\entity\entity;
use core\orm\entity\repository;
use mod_perform\entity\activity\manual_relationship_selection;
use mod_perform\entity\activity\manual_relationship_selection_progress;
use mod_perform\entity\activity\manual_relationship_selection_progress_repository;
use mod_perform\entity\activity\manual_relationship_selector;
use mod_perform\entity\activity\subject_instance;
use mod_perform\state\subject_instance\closed;

/**
 * Common functionality for handling manual relationships and participant selections.
 *
 * @package mod_perform\models\activity\helpers
 */
class manual_participant_helper {

    /**
     * @var int User ID
     */
    protected $user_id;

    private function __construct(int $for_user_id) {
        $this->user_id = $for_user_id;
    }

    /**
     * Get participant selections that a specific user needs to make.
     *
     * @param int $user_id
     * @return static
     */
    public static function for_user(int $user_id): self {
        return new static($user_id);
    }

    /**
     * Get the user ID.
     *
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * Base query for pending subject instance manual participant selections.
     * Note: this will exclude selections for closed subject instances.
     *
     * @return manual_relationship_selection_progress_repository
     */
    public function build_pending_selections_query(): manual_relationship_selection_progress_repository {
        return manual_relationship_selection_progress::repository()
            ->join([manual_relationship_selector::TABLE, 'selector'], 'id', 'manual_relation_select_progress_id')
            ->join([subject_instance::TABLE, 'si'], 'subject_instance_id', 'id')
            ->where('si.availability', '<>', closed::get_code())
            ->where('status', manual_relationship_selection_progress::STATUS_PENDING)
            ->where('selector.user_id', $this->user_id)
            ->order_by('subject_instance_id')
            ->order_by('id');
    }

    /**
     * Does the current user have pending manual participant selections that need to be made?
     *
     * @param int|null $subject_instance_id If specified, checks if there are pending selections for just a specific subject instance.
     * @return bool
     */
    public function has_pending_selections(int $subject_instance_id = null): bool {
        return $this
            ->build_pending_selections_query()
            ->when($subject_instance_id !== null, static function (repository $repository) use ($subject_instance_id) {
                $repository->where('subject_instance_id', $subject_instance_id);
            })
            ->exists();
    }

    /**
     * For the current user how many pending manual participant selections need to be made?
     *
     * @return int
     */
    public function get_pending_subject_instances_count(): int {
        return $this->build_pending_selections_query()
            ->select_raw('DISTINCT subject_instance_id')
            ->count();
    }

    /**
     * Set the selection progress for a subject instance relationship to complete.
     *
     * @param int $subject_instance_id
     * @param int $manual_relationship_id
     */
    public function set_progress_complete(int $subject_instance_id, int $manual_relationship_id): void {
        /** @var manual_relationship_selection_progress|entity $progress */
        $progress = $this
            ->build_pending_selections_query()
            ->join([manual_relationship_selection::TABLE, 'selection'], 'manual_relation_selection_id', 'id')
            ->where('selection.manual_relationship_id', $manual_relationship_id)
            ->where('subject_instance_id', $subject_instance_id)
            ->one(true);

        // We have to update the status separately from the query because the database doesn't support using joins with update()
        $progress->status = manual_relationship_selection_progress::STATUS_COMPLETE;
        $progress->save();
    }

    /**
     * Make sure the specified relationship IDs match what is required to activate the subject instance.
     *
     * @param int $subject_instance_id
     * @param int[] $relationship_ids
     */
    public function validate_participant_relationship_ids(int $subject_instance_id, array $relationship_ids): void {
        $relationships_ids_required = $this
            ->build_pending_selections_query()
            ->where('subject_instance_id', $subject_instance_id)
            ->join([manual_relationship_selection::TABLE, 'selection'], 'manual_relation_selection_id', 'id')
            ->select('selection.manual_relationship_id')
            ->get()
            ->pluck('manual_relationship_id');

        if (!empty(array_diff($relationships_ids_required, $relationship_ids))) {
            $specified = implode(', ', $relationship_ids);
            $required = implode(', ', $relationships_ids_required);

            throw new coding_exception("The relationship IDs specified [{$specified}] do not match the required IDs [{$required}]");
        }
    }

}
