<?php
/*
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
 * @package totara_mobile
 * @author David Curry
 */

namespace totara_mobile\webapi\resolver\type;

use core\json_editor\helper\document_helper;
use totara_mobile\formatter\mobile_program_formatter;
use totara_mobile\local\duedate_state as mobile_duedate_state;
use core\format;
use context_program;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use coding_exception;
use coursecat;

use \totara_program\user_learning\item as program_item;
use \totara_program\user_learning\courseset as item_courseset;

/**
 * Program type
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see a program
 */
class program extends type_resolver {
    /**
     * Resolve program fields
     *
     * @param string $field
     * @param mixed $program
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $program, array $args, execution_context $ec) {
        global $CFG, $USER, $OUTPUT;

        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        if (!$program instanceof \program) {
             throw new \coding_exception('Only program objects are accepted: ' . gettype($program));
        }

        $format = $args['format'] ?? null;
        $program_context = context_program::instance($program->id);

        if (!self::authorize($field, $format, $program_context)) {
            return null;
        }

        $datefields = ['availablefrom', 'availableuntil'];
        if (in_array($field, $datefields) && empty($program->{$field})) {
            // Highly unlikely this is set to 1/1/1970, return null for notset dates.
            return null;
        }

        $formatfields = ['summaryformat', 'endnoteformat'];
        if (in_array($field, $formatfields)) {
            $prop = substr($field, 0, -6);
            if (document_helper::is_valid_json_document($program->{$prop})) {
                return 'JSON_EDITOR';
            } else {
                return 'HTML';
            }
        }

        if ($field == 'category') {
            return coursecat::get($program->category);
        }

        $currentdata = [
            'current_coursesets',
            'count_unavailablesets',
            'count_optionalsets',
            'count_completedsets',
            'courseset_header'
        ];
        if ($field == 'coursesets' || in_array($field, $currentdata)) {
            /* @var $content \prog_content */
            $content = $program->get_content();
            $csets = $content->get_course_sets();

            // Return all the coursesets in the program, without extra formatting.
            if ($field == 'coursesets') {
                return $csets;
            }

            // Sort the coursesets into only the currently available ones and return.
            if (in_array($field, $currentdata)) {
                // We want to mimic the learning item process here, so lets just use it.
                if ($item = program_item::one($USER, $program)) {
                    $coursesets = [];
                    foreach ($csets as $cset) {
                        $coursesets[] = item_courseset::from_course_set($item, $cset, $USER);
                    }
                } else {
                    return null;
                }

                /**
                 * This gets us a lot of the information we need here.
                 * $data->sets = $finalsets;
                 * $data->completecount = $completed_set_count;
                 * $data->optionalcount = $optional_set_count;
                 * $data->unavailablecount = $unavailable_set_count;
                 */
                $data = $item->process_coursesets($coursesets);

                // Do a little extra processing on top to format the coursesets.
                if ($field == 'current_coursesets') {
                    $csetor = [];
                    $csetand = [];
                    foreach ($data->sets as $cset) {
                        switch ($cset->nextsetoperator) {
                            case NEXTSETOPERATOR_AND:
                                $csetand[] = $cset;
                                break;
                            case NEXTSETOPERATOR_OR:
                                $csetand[] = $cset;
                                $csetor[] = $csetand;
                                $csetand = [];
                                break;
                            default:
                                // Covers, THEN and null values in final coursesets.
                                $csetand[] = $cset;
                                $csetor[] = $csetand;
                                break(2);
                        }
                    }
                    if (empty($csetor) && !empty($csetand)) {
                        $csetor[] = $csetand;
                    }
                    return $csetor;
                }

                if ($field == 'count_unavailablesets') {
                    return $data->unavailablecount;
                }

                if ($field == 'count_optionalsets') {
                    return $data->optionalcount;
                }

                if ($field == 'count_completedsets') {
                    return $data->completecount;
                }

                if ($field == 'courseset_header') {
                    return $item->get_coursesets_header_text($data->completecount, $data->optionalcount);
                }
            }
        }

        $duefields = ['duedate', 'duedate_state'];
        if ($field == 'completion' || in_array($field, $duefields)) {
            // Note: This loads the duedate as well so I've combined them here,
            // however completion is it's own object and duedate is part of the program.
            if ($completion = prog_load_completion($program->id, $USER->id, false)) {

                if ($field == 'completion') {
                    return $completion;
                }

                if ($field == 'duedate') {
                    if (!empty($completion->timedue) && $completion->timedue != -1) {
                        $program->duedate = $completion->timedue;
                    } else {
                        return null;
                    }
                }

                // Mobile - override duedate states to make them consistent.
                if ($field == 'duedate_state') {
                    if (!empty($completion->timedue) && $completion->timedue != -1) {
                        $program->duedate_state = mobile_duedate_state::calculate($completion->timedue);
                    } else {
                        return null;
                    }
                }
            } else {
                return null;
            }
        }
        // Copied from the mobile_learning_item type resolver.
        if ($field == 'image_src' || $field == 'mobile_image') {
            $prog = new \program($program->id);
            $program->image_src = $prog->get_image();

            if ($field == 'mobile_image') {
                $component = 'totara_program';
                $filearea = 'totara_program_default_image';

                if ($program->image_src == $OUTPUT->image_url('defaultimage', $component)) {
                    $program->mobile_image = "";
                } else {
                    $url = false;
                    $fs = get_file_storage();
                    // check if same as custom default
                    $files = array_values($fs->get_area_files(
                        \context_system::instance()->id,
                        'totara_core',
                        $filearea,
                        0,
                        "timemodified DESC",
                        false
                    ));
                    if ($files) {
                        $file = \moodle_url::make_pluginfile_url(
                            $files[0]->get_contextid(),
                            $files[0]->get_component(),
                            $files[0]->get_filearea(),
                            $files[0]->get_itemid(),
                            $files[0]->get_filepath(),
                            $files[0]->get_filename()
                        );
                        $url = $file->out();
                    }
                    if ($url && $program->image_src == $url) {
                        $program->mobile_image = "";
                    } else {
                        $program->mobile_image = $program->image_src;
                    }
                }
            }
        }

        $formatter = new mobile_program_formatter($program, $program_context);
        $formatted = $formatter->format($field, $format);

        // For mobile execution context, rewrite pluginfile urls in description and image_src fields.
        // This is clearly a hack, please suggest something more elegant.
        if (is_a($ec, 'totara_mobile\webapi\execution_context') && in_array($field, ['description', 'image', 'mobile_image'])) {
            $formatted = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $formatted);
        }

        return $formatted;
    }

    public static function authorize(string $field, ?string $format, context_program $context) {
        // Permission to see RAW formatted string fields
        if (in_array($field, ['fullname', 'shortname']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/program:configuredetails', $context);
        }
        // Permission to see RAW formatted text fields
        if (in_array($field, ['summary', 'endnote']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/program:configuredetails', $context);
        }
        return true;
    }


}
