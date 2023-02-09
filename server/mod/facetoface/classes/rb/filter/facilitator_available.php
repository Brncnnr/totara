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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

defined('MOODLE_INTERNAL') || die();

/**
 * Don't uncomment this namespace as report_builder will never find this filter.
 * Leaving this for reference.
 */
// namespace mod_facetoface\rb\filter;

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/classes/rb/filter/f2f_available.php');

/**
 * Keep this classname with rb_filter prefix as report_builder will never load this filter.
 */
class rb_filter_facilitator_available extends rb_filter_f2f_available {

    public function get_sql_snippet($sessionstarts, $sessionends): array {

        $paramstarts = rb_unique_param('timestart');
        $paramends = rb_unique_param('timefinish');

        $field = $this->get_field();
        $sql = "$field NOT IN (
            SELECT ff.id
              FROM {facetoface_facilitator} ff
              JOIN {facetoface_facilitator_dates} ffd ON ffd.facilitatorid = ff.id
              JOIN {facetoface_sessions_dates} fsd ON fsd.id = ffd.sessionsdateid
             WHERE ff.allowconflicts = 0
               AND :{$paramends} > fsd.timestart
               AND fsd.timefinish > :{$paramstarts})";

        $params = array();
        $params[$paramstarts] = $sessionstarts;
        $params[$paramends] = $sessionends;

        return array($sql, $params);
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    function get_label($data): string {

        $end = $data['end'];
        $start = $data['start'];
        $enable = $data['enable'];

        // Default vale for facilitator availability filter is any time. Enable equal to zero.
        $value = get_string('anytime', 'mod_facetoface');
        if ($enable) {
            $a = new stdClass();
            $a->start  = userdate($start);
            $a->end = userdate($end);
            $value = get_string('freebetweendates', 'mod_facetoface', $a);
        }

        $a = new stdClass();
        $a->label = $this->label;
        $a->value = $value;

        return get_string('selectlabelnoop', 'filters', $a);
    }
}