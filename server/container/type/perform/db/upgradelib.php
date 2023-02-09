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
 * @package container_perform
 */

/**
 * Create and enable 'container_perform' enrollment records for existing perform containers.
 */
function container_perform_create_enrollment_plugin_records() {
    global $DB;
    $transaction = $DB->start_delegated_transaction();

    $time = time();
    $container_ids = $DB->get_fieldset_select('course', 'id', "containertype = 'container_perform'");

    // We don't want to support any enrolment method other than container_perform,
    // so delete all previously existing methods for perform containers.
    // This makes inserting enrol records easier as we don't need to calculate sort orders again.
    $DB->delete_records_list('enrol', 'courseid', $container_ids);

    $enrollment_records_to_insert = [];
    foreach ($container_ids as $container_id) {
        $enrollment_records_to_insert[] = (object) [
            'enrol' => 'container_perform',
            'courseid' => $container_id,
            'timecreated' => $time,
            'timemodified' => $time,
        ];
    }
    $DB->insert_records_via_batch('enrol', $enrollment_records_to_insert);

    $transaction->allow_commit();
}
