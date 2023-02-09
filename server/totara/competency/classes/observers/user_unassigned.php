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

namespace totara_competency\observers;

use totara_competency\event\assignment_user_unassigned;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\assignment_user;
use totara_competency\models\assignment_user_log;
use totara_competency\settings;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer
 */
class user_unassigned {

    /**
     * Event
     *
     * @var assignment_user_unassigned
     */
    protected $event;

    /**
     * Assignment user model
     *
     * @var assignment_user
     */
    protected $assignment_user;

    /**
     * user_unassigned constructor.
     *
     * @param assignment_user_unassigned $event
     */
    public function __construct(assignment_user_unassigned $event) {
        $this->event = $event;
        $this->assignment_user = (new assignment_user($event->get_user_id()))
            ->set_assignment(assignment_model::load_by_id($this->event->get_assignment_id()));
    }

    /**
     * React to the user unassigned
     *
     * @return bool
     */
    public function handle() {
        $this->create_system_assignment()
            ->log_activity()
            ->remove_related_records();

        return true;
    }

    /**
     * Create system assignments if continuous tracking is enabled
     *
     * @return $this
     */
    protected function create_system_assignment() {
        // Create a new assignment for continuous tracking if setting is enabled
        if (settings::is_continuous_tracking_enabled()) {
            (new assignment_user($this->event->get_user_id()))
                ->create_system_assignment($this->event->get_competency_id());
        }

        return $this;
    }

    /**
     * Remove related records if the site-wide setting is set to remove records
     *
     * @return $this
     */
    protected function remove_related_records() {
        // Delete related competency records if unassign behaviour is set up that way
        if (!settings::should_unassign_keep_records()) {
            if (settings::should_unassign_keep_achieved_records() && $this->assignment_user->has_achievement()) {
                return $this;
            }

            $this->assignment_user->delete_related_data();
        }

        return $this;
    }

    /**
     * Log activity
     *
     * @return $this
     */
    protected function log_activity() {
        $log = new assignment_user_log(
            $this->event->get_assignment_id(),
            $this->event->get_competency_id(),
            $this->event->get_assignment_type()
        );

        $log->log_unassign_user_group($this->event->get_user_id());

        return $this;
    }

    /**
     * Triggered via assignment_user_unassigned event.
     *
     * @param assignment_user_unassigned $event
     * @return bool
     */
    public static function observe(assignment_user_unassigned $event) {
        $observer = new static($event);

        return $observer->handle();
    }
}
