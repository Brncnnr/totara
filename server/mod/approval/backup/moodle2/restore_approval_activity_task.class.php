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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/moodle2/restore_activity_task.class.php');

/**
 * Perform restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_approval_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
    }

    /**
     * Define steps for activity
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new \mod_approval\backup\restore_activity_structure_step('approval_structure', 'approval.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring activity logs.
     * It must return one array of {@link restore_log_rule} objects
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }
}