<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\rb\display;

use rb_column;
use reportbuilder;
use stdClass;
use totara_evidence\entity\evidence_item;
use totara_evidence\output\item_list_actions;
use totara_mvc\view;
use totara_reportbuilder\rb\display\base;

class evidence_item_actions extends base {

    /**
     * Display evidence actions column
     *
     * @param int $evidence_id
     * @param string $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($evidence_id, $format, stdClass $row, rb_column $column, reportbuilder $report): string {
        $extra_fields = self::get_extrafields_row($row, $column);
        $item = (new evidence_item($extra_fields))->model;

        return view::core_renderer()->render(item_list_actions::create($item));
    }

}
