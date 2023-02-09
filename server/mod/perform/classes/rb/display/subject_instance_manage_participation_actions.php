<?php
/*
 * This file is part of Totara Perform
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\display;

use mod_perform\state\subject_instance\pending;
use rb_column;
use rb_column_option;
use reportbuilder;
use stdClass;
use totara_reportbuilder\rb\display\base;
use totara_tui\output\component;
use mod_perform\state\subject_instance\closed;

/**
 * Class describing column display formatting.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */
class subject_instance_manage_participation_actions extends base {

    public const SUBJECT_INSTANCE_REPORT_TYPE = 'SUBJECT_INSTANCE';

    /**
     * @inheritDoc
     */
    public static function display($value, $format, stdClass $row, rb_column $column, reportbuilder $report) {

        // Column uses noexport, but just to be sure...
        if ($format !== 'html') {
            return '';
        }

        $extrafields = self::get_extrafields_row($row, $column);

        $is_open = $extrafields->subject_instance_availability != closed::get_code();

        $tui = new component(
            'mod_perform/components/report/manage_participation/Actions',
            [
                'reportType'         => self::SUBJECT_INSTANCE_REPORT_TYPE,
                'activityId'         => $extrafields->activity_id,
                'id'                 => $extrafields->subject_instance_id,
                'isOpen'             => $is_open,
                'canAddParticipants' => $extrafields->deleted !== null && $extrafields->deleted == 0,
                'showActions'        => true,
                'isParticipantPending' => $extrafields->status == pending::get_code(),
            ]
        );
        return $tui->out_html();
    }

    public static function is_graphable(rb_column $column, rb_column_option $option, reportbuilder $report) {
        return false;
    }
}
