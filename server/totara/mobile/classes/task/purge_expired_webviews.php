<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\task;

/**
 * Purging of stale mobile web views.
 *
 * NOTE: those linked to sessions are deleted via foreign key ON DELETE cascading.
 */
final class purge_expired_webviews extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskpurgeexpiredwebviews', 'totara_mobile');
    }

    /**
     * Execute task.
     */
    public function execute() {
        global $DB;

        $select = $DB->sql('sessionid IS NULL AND timecreated < :stale', ['stale' => time() - HOURSECS]);
        $DB->delete_records_select('totara_mobile_webviews', $select);
    }
}

