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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_appraisal
 */

namespace totara_appraisal\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended to display the response to the multichoicemulti question type
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_appraisal
 */
class appraisal_multichoice_multi extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        if (empty($value)) {
            return '';
        }

        // Cache option names.
        if (empty(\rb_source_appraisal_detail::$appraisalmultichoicenamecache[$report->src->appraisalid])) {
            $report->src->populate_multichoice_name_cache();
        }

        $ids = explode(',', $value);

        $result = array();
        foreach ($ids as $id) {
            if (!isset(\rb_source_appraisal_detail::$appraisalmultichoicenamecache[$report->src->appraisalid][$id])) {
                throw new \coding_exception('Missing cache value in rb_source_appraisal_detail::$appraisalmultichoicenamecache');
            }

            $result[] = \rb_source_appraisal_detail::$appraisalmultichoicenamecache[$report->src->appraisalid][$id];
        }

        $result = implode(', ', $result);
        $result = \totara_reportbuilder\rb\display\format_string::display($result, $format, $row, $column, $report);
        return $result;
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
