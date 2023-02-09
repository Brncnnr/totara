<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\models;

use core\orm\query\builder;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\competency_assignment_user_log;

/**
 * Class assignment_user_log
 *
 * @package totara_competency\models
 */
class assignment_user_log {
    /**
     * @var string
     */
    protected $assignment_type;

    /**
     * @var int
     */
    private $assignment_id;

    /**
     * @var int|array
     */
    private $user_id;

    /**
     * @var int
     */
    private $competency_id;

    /**
     * assignment_user_log constructor.
     *
     * @param int $assignment_id
     * @param int|null $competency_id
     * @param string|null $assignment_type
     */
    public function __construct(int $assignment_id, ?int $competency_id = null, ?string $assignment_type = null) {
        $this->assignment_id = $assignment_id;
        $this->competency_id = $competency_id;
        $this->assignment_type = $assignment_type;
    }

    /**
     * Log action on an assignment
     *
     * @param int $action
     * @param int|array $user_id one or multiple user_ids
     * @return void
     */
    public function log($user_id, int $action) {
        if (is_array($user_id)) {
            if (empty($user_id)) {
                return;
            }

            // Bulk insert
            $logs = array_map(function (int $user_id) use ($action) {
                return (object)[
                    'assignment_id' => $this->assignment_id,
                    'user_id' => $user_id,
                    'action' => $action,
                    'created_at' => time()
                ];
            }, $user_id);
            builder::get_db()->insert_records_via_batch(competency_assignment_user_log::TABLE, $logs);
        } else {
            $user_log = new competency_assignment_user_log();
            $user_log->assignment_id = $this->assignment_id;
            $user_log->user_id = $user_id;
            $user_log->action = $action;
            $user_log->save();
        }
    }

    /**
     * Log the given action for given user
     *
     * @param int|array $user_id one or multiple user_ids
     */
    public function log_assign($user_id) {
        $this->log($user_id, competency_assignment_user_log::ACTION_ASSIGNED);
        $this->log_tracking_start($user_id);
    }

    /**
     * Log the given action for given user
     *
     * @param int|array $user_id one or multiple user_ids
     */
    public function log_unassign_user_group($user_id) {
        $this->log($user_id, competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP);
        $this->log_tracking_end($user_id);
    }

    /**
     * Log the given action for given user
     *
     * @param int|array $user_id one or multiple user_ids
     * @param bool $tracking_continues
     */
    public function log_archive($user_id, bool $tracking_continues = false) {
        $this->log($user_id, competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED);
        if (!$tracking_continues) {
            $this->log_tracking_end($user_id);
        }
    }

    /**
     * Log tracking start if the given assignment is the first active one
     *
     * @param int|array $user_id one or multiple user_ids
     */
    public function log_tracking_start($user_id) {
        // We ignore the continuous tracking assignment
        if (!is_null($this->competency_id) && $this->assignment_type !== assignment::TYPE_SYSTEM) {
            if (is_array($user_id)) {
                // Get all users who do not have any other active assignment than the current one
                $assignments = builder::table(assignment::TABLE)
                    ->select('au.user_id')
                    ->join([competency_assignment_user::TABLE, 'au'], 'id', 'assignment_id')
                    ->where('au.user_id', $user_id)
                    ->where('id', '<>', $this->assignment_id)
                    ->where('competency_id', $this->competency_id)
                    ->where('status', assignment::STATUS_ACTIVE)
                    ->group_by('au.user_id')
                    ->get();
                $assignment_user_ids = $assignments->pluck('user_id');
                $user_id = array_diff($user_id, $assignment_user_ids);
                $tracking_start = !empty($user_id);
            } else {
                $assignment_user = new assignment_user($user_id);
                $assignments = $assignment_user->get_active_assignments_for_competency($this->competency_id);
                // If the current assignment is the only one, tracking just got started
                $tracking_start = $assignments->count() === 1 && $assignments->first()->id == $this->assignment_id;
            }
            if ($tracking_start) {
                $this->log($user_id, competency_assignment_user_log::ACTION_TRACKING_START);
            }
        }
    }

    /**
     * Log tracking end if there are no active assignments left
     *
     * @param int|array $user_id one or multiple user_ids
     */
    public function log_tracking_end($user_id) {
        if (!is_null($this->competency_id)) {
            if (is_array($user_id)) {
                // Get all users who do not have any active assignments
                $assignments = builder::table(assignment::TABLE)
                    ->select('au.user_id')
                    ->join([competency_assignment_user::TABLE, 'au'], 'id', 'assignment_id')
                    ->where('au.user_id', $user_id)
                    ->where('competency_id', $this->competency_id)
                    ->where('status', assignment::STATUS_ACTIVE)
                    ->group_by('au.user_id')
                    ->get();
                $assignment_user_ids = $assignments->pluck('user_id');
                $user_id = array_diff($user_id, $assignment_user_ids);
                $tracking_end = !empty($user_id);
            } else {
                $assignment_user = new assignment_user($user_id);
                $tracking_end = !$assignment_user->has_active_assignments($this->competency_id);
            }
            if ($tracking_end) {
                $this->log($user_id, competency_assignment_user_log::ACTION_TRACKING_END);
            }
        }
    }

}
