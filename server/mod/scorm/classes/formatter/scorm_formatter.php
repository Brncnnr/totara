<?php
/**
 *
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_scorm
 */

namespace mod_scorm\formatter;

use core\webapi\formatter\formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;

class scorm_formatter extends formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'courseid' => null,
            'showgrades' => null, // Default boolean.
            'name' => string_field_formatter::class,
            'scormtype' => null,
            'reference' => null,
            'intro' => function ($value, text_field_formatter $formatter) {
                $component = "mod_scorm";
                $filearea = 'intro';

                return $formatter
                    ->set_pluginfile_url_options($this->context, $component, $filearea)
                    ->format($value);
            },
            'version' => null,
            'maxgrade' => null,
            'grademethod' => null,
            'whatgrade' => null,
            'maxattempt' => null,
            'forcecompleted' => null,
            'forcenewattempt' => null,
            'lastattemptlock' => null,
            'masteryoverride' => null,
            'displaycoursestructure' => null,
            'skipview' => null,
            'nav' => null,
            'navpositionleft' => null,
            'navpositiontop' => null,
            'auto' => null,
            'width' => null,
            'height' => null,
            'timeopen' => date_field_formatter::class,
            'timeclose' => date_field_formatter::class,
            'displayactivityname' => null,
            'autocommit' => null,
            'allowmobileoffline' => null,
            'completion' => null,
            'completionview' => null,
            'completionstatusrequired' => null,
            'completionscorerequired' => null,
            'completionstatusallscos' => null,
            'package_url' => string_field_formatter::class,
            'launch_url' => string_field_formatter::class,
            'repeat_url' => string_field_formatter::class,
            'attempts_current' => null,
            'calculated_grade' => null,
            'offline_package_url' => null,
            'offline_package_contenthash' => null,
            'offline_package_sco_identifiers' => null,
            'attempt_defaults' => null, // Serialized JSON string.
            'attempts' => null,
        ];
    }
}
