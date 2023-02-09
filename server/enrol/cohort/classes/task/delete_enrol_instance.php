<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package enrol_cohort
 */

namespace enrol_cohort\task;

use core\task\adhoc_task;

/**
 * Delete the specific enrolment instance.
 */
class delete_enrol_instance extends adhoc_task {
    /**
     * Delete the specific instance
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        $data = $this->get_custom_data();
        if (empty($data) || !is_object($data)) {
            return;
        }

        $instance_id = $data->instance_id ?? null;
        if (empty($instance_id)) {
            return;
        }

        require_once("$CFG->libdir/enrollib.php");
        $instance = $DB->get_record('enrol', ['id' => $instance_id, 'status' => ENROL_INSTANCE_DELETED]);
        if (empty($instance)) {
            return;
        }

        /** @var \enrol_plugin $plugin */
        $plugin = enrol_get_plugin($instance->enrol);
        $plugin->delete_instance($instance);
    }

    /**
     * @return \lang_string|string
     */
    public function get_name() {
        return get_string('taskdeleteenrolinstance', 'enrol_cohort');
    }
}
