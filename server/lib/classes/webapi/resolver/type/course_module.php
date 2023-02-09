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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\type;

use core\webapi\execution_context;
use core_course\formatter\course_module_formatter;
use core_availability\info as info;
use core\format;
use core\webapi\type_resolver;
use context_course;
use coursecat;

class course_module extends type_resolver {

    public static function resolve(string $field, $cminfo, array $args, execution_context $ec) {
        global $DB, $USER, $CFG;

        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/grade_item.php');

        if (!$cminfo instanceof \cm_info) {
            throw new \coding_exception('Only cm_info objects are accepted: ' . gettype($cminfo));
        }

        // Take a lot of the basic information out of the wrapper, and use it as the base object.
        $info = $cminfo->get_course_module_record(true);

        if (!$mod_context = \context_module::instance($info->id, IGNORE_MISSING)) {
            // If there is no matching context we have a bad object, ignore missing so we can do our own error.
            throw new \coding_exception('Only valid module objects are accepted');
        }

        $format = $args['format'] ?? null;
        $available = $cminfo->available;
        if ($field == 'available') {
            return $available;
        }

        if ($field == 'instanceid') {
            $info->instanceid = $info->instance;
        }

        if ($field == 'modtype') {
            $info->modtype = $info->modname;
        }

        $course = $cminfo->get_course();
        if ($field == 'availablereason') {
            $info->availablereason = [];

            if (!$available) {
                $availableinfo = $cminfo->availableinfo;

                if (!empty($availableinfo)) {
                    // Pre-load the module and context information.
                    $modinfo = get_fast_modinfo($course->id, $USER->id);
                    $coursecontext = \context_course::instance($course->id);
                    $reason = \core_availability\info::webapi_parse_available_info($availableinfo, $coursecontext, $modinfo);

                    $info->availablereason = $reason;
                }
            }
        }

        /**
         * Note: This is a constant defined in lib/completionlib.php
         *       translated into string constants for mobile
         */
        if ($field == 'completion') {
            switch ($info->completion) {
                case COMPLETION_TRACKING_NONE :
                    return 'tracking_none';
                    break;
                case COMPLETION_TRACKING_MANUAL :
                    return 'tracking_manual';
                    break;
                case COMPLETION_TRACKING_AUTOMATIC :
                    return 'tracking_automatic';
                    break;
                default :
                    return 'unknown';
                    break;
            }
        }

        if ($field === 'completionenabled') {
            $completioninfo = new \completion_info($course);
            return $completioninfo->is_enabled($cminfo) > COMPLETION_TRACKING_NONE;
        }

        /**
         * Note: This is a constant defined in lib/completionlib.php
         *       translated into string constants for mobile
         */
        if ($field == 'completionstatus') {
            if ($available) {
                $completioninfo = new \completion_info($course);
                $completiondata = $completioninfo->get_data($cminfo);
                switch ($completiondata->completionstate) {
                    case COMPLETION_INCOMPLETE :
                        return 'incomplete';
                        break;
                    case COMPLETION_COMPLETE :
                        return 'complete';
                        break;
                    case COMPLETION_COMPLETE_PASS :
                        return 'complete_pass';
                        break;
                    case COMPLETION_COMPLETE_FAIL :
                        return 'complete_fail';
                        break;
                    default :
                        return 'unknown';
                        break;
                }
            } else {
                return 'unknown';
            }
        }

        if ($field === 'rpl') {
            if ($available) {
                $completioninfo = new \completion_info($course);
                $rpl = $completioninfo->is_completed_via_rpl($cminfo);

                return $rpl;
            }

            return false;
        }

        if ($field === 'progress') {
            $completioninfo = new \completion_info($course);
            $completiondata = $completioninfo->get_data($cminfo);
            return $completiondata->progress;
        }

        $gradefields = ['gradefinal', 'grademax', 'gradepercentage'];
        if (in_array($field, $gradefields)) {
            $item = \grade_item::fetch([
                'itemtype' => 'mod',
                'itemmodule' => $info->modname,
                'iteminstance' => $info->instance,
            ]);

            // No grade item found?
            if (empty($item)) {
                return 0;
            }

            $grade = new \grade_grade(array('itemid' => $item->id, 'userid' => $USER->id));

            if ($field == 'gradefinal') {
                return $grade->finalgrade;
            }

            if ($field == 'grademax') {
                return $grade->rawgrademax;
            }

            if ($field == 'gradepercentage') {
               return ((float)$grade->finalgrade / (float)$grade->rawgrademax) * 100;
            }
        }

        if ($field == 'showdescription') {
            if ($available) {
                return $info->showdescription;
            } else {
                return false;
            }
        }

        $modvaluefields = ['description', 'descriptionformat'];
        if (in_array($field, $modvaluefields)) {
            // Note: The get_coursemodule_info functions do too much pre-formatting, this is the easiest way to handle it.
            //       However, first we would need to make sure that if course module supports the intro or not in order
            //       to include the appropriate fields.
            $fetch_fields = ["name"];
            $support_intro = plugin_supports("mod", $cminfo->modname, FEATURE_MOD_INTRO, false);

            if ($support_intro) {
                $fetch_fields[] = "intro";
                $fetch_fields[] = "introformat";
            }

            $modvalues = $DB->get_record($cminfo->modname, ['id' => $cminfo->instance], implode(", ", $fetch_fields));
            if (!$support_intro) {
                // Default intro to null and FORMAT HTML if intro is not supported by course module.
                $modvalues->intro = null;
                $modvalues->introformat = FORMAT_HTML;
            }

            if ($field == 'description') {
                $info->description = $modvalues->intro;
            }

            // Transform the format field from the constants to a core_format string.
            if ($field == 'descriptionformat') {
                return format::from_moodle($modvalues->introformat);
            }
        }

        if ($field == 'viewurl') {
            if ($available) {
                return $cminfo->url;
            } else {
                return '';
            }
        }

        $formatter = new course_module_formatter($info, $mod_context);
        $formatted = $formatter->format($field, $format);

        // For mobile execution context, rewrite pluginfile urls in description and image_src fields.
        // This is clearly a hack, please suggest something more elegant.
        if (is_a($ec, 'totara_mobile\webapi\execution_context') && in_array($field, ['description'])) {
            // Prevent passing null as input parameter in PHP 8.1
            $formatted = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $formatted ?? '');
        }

        return $formatted;
    }
}
