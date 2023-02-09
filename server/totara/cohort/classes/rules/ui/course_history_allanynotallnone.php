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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\rules\ui;

defined('MOODLE_INTERNAL') || die();

class course_history_allanynotallnone extends course_allanynotallnone {

    /**
     * @return $html string
     */
    public function getExtraSelectedItemsPaneWidgets() {

        $html = \html_writer::tag('p', get_string('completionarchivedrecords', 'totara_cohort'));
        $html .= parent::getExtraSelectedItemsPaneWidgets();
        return $html;
    }

    /**
     * Get description string depends from operator for course completion history.
     *
     * @return \stdClass
     */
    protected function get_description_string(): \stdClass {
        $strvar = new \stdClass();
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_ALL:
                $strvar->desc = get_string('cchdescall', 'totara_cohort');
                break;
            case COHORT_RULE_COMPLETION_OP_ANY:
                $strvar->desc = get_string('cchdescany', 'totara_cohort');
                break;
            case COHORT_RULE_COMPLETION_OP_NOTALL:
                $strvar->desc = get_string('cchdescnotall', 'totara_cohort');
                break;
            default:
                $strvar->desc = get_string('cchdescnotany', 'totara_cohort');
        }
        return $strvar;
    }
}