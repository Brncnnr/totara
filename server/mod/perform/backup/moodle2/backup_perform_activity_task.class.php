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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');

/**
 * Perform backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_perform_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // $is_cloning = new backup_activity_generic_setting('is_cloning', base_setting::IS_BOOLEAN, false);
        // $this->add_setting($is_cloning);
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // perform only has one structure step
        $this->add_step(new \mod_perform\backup\backup_activity_structure_step('perform_structure', 'perform.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @param $content
     * @param backup_task|null $task
     * @return string
     */
    public static function encode_content_links($content, backup_task $task = null) {
        return $content;
    }
}
