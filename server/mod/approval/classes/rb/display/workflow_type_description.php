<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\rb\display;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use core_text;
use rb_column;
use reportbuilder;
use totara_reportbuilder\rb\display\base;
use container_approval\approval as container;

class workflow_type_description extends base {

    /**
     * @inheritDoc
     */
    public static function display($description, $format, stdClass $row, rb_column $column, reportbuilder $report) {

        $isexport = ($format !== 'html');

        $extra = self::get_extrafields_row($row, $column);

        $descriptionhtml = file_rewrite_pluginfile_urls(
            $description,
            'pluginfile.php',
            container::get_default_category_context()->id,
            'mod_approval',
            'workflow_type',
            $extra->id
        );
        $descriptionhtml = format_text($descriptionhtml, FORMAT_HTML);

        if ($isexport) {
            $displaytext = html_to_text($descriptionhtml, 0, false);
            $displaytext = core_text::entities_to_utf8($displaytext);
            return $displaytext;
        }

        return $descriptionhtml;
    }
}
