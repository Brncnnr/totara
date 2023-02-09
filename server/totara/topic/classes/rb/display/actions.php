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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
namespace totara_topic\rb\display;

use totara_reportbuilder\rb\display\base;
use totara_topic\output\report_actions;
use stdClass;
use rb_column;
use reportbuilder;
use moodle_url;

final class actions extends base {
    /**
     * @param int|string    $id
     * @param string        $format
     * @param stdClass      $row
     * @param rb_column     $column
     * @param reportbuilder $report
     *
     * @return string
     */
    public static function display($id, $format, stdClass $row, rb_column $column, reportbuilder $report): string {
        global $OUTPUT, $PAGE;

        // Using global $CONTEXT for now.
        $context = $PAGE->context;
        $extra = self::get_extrafields_row($row, $column);

        $usage = 0;
        if (null != $extra->totalusage) {
            $usage = $extra->totalusage;
        }

        $widget = report_actions::create(null, null, $usage);

        if (has_capability('totara/topic:update', $context)) {
            // Crafting the update url, if user has the capability
            $updateurl = new \moodle_url("/totara/topic/edit.php", ['id' => $id]);
            if (!$report->embedded) {
                $updateurl->param('back', $report->report_url(true));
            }

            $widget->set_update_url($updateurl);

            if ($extra->topic_value) {
                $update_title = get_string('update_topic_name', 'totara_topic', $extra->topic_value);
                $widget->set_update_url_title($update_title);
            }
        }

        if (has_capability('totara/topic:delete', $context)) {
            // Crafting the delete icon if the user has any capability
            $delete_url = new moodle_url("/totara/topic/delete.php", ['id' => $id]);

            if (!$report->embedded) {
                $delete_url->param('back', $report->report_url());
            }

            $widget->set_delete_url($delete_url);

            if ($extra->topic_value) {
                $delete_title = get_string('delete_topic_name', 'totara_topic', $extra->topic_value);
                $widget->set_delete_url_title($delete_title);
            }
        }

        return $OUTPUT->render($widget);
    }
}