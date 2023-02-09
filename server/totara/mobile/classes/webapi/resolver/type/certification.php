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
use totara_mobile\local\duedate_state as mobile_duedate_state;
use totara_mobile\formatter\mobile_certification_formatter;
use core\format;
use context_program;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use coding_exception;
use coursecat;

use \totara_certification\user_learning\item as certification_item;
use \totara_certification\user_learning\courseset as item_courseset;

/**
 * certification type
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see a certification
 */
class certification extends type_resolver {
    /**
     * Resolve certification fields
     *
     * @param string $field
     * @param mixed $certification
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $certification, array $args, execution_context $ec) {
        global $CFG, $USER, $OUTPUT, $DB;

        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        if (!$certification instanceof \program || empty($certification->certifid)) {
            // Note: Currently this is accepting program objects, but only when certifid <> 0.
            throw new \coding_exception('Only certification program objects are accepted: ' . gettype($certification));
        }

        $format = $args['format'] ?? null;
        $program_context = context_program::instance($certification->id);

        if (!self::authorize($field, $format, $program_context)) {
            return null;
        }

        $datefields = ['availablefrom', 'availableuntil'];
        if (in_array($field, $datefields) && empty($certification->{$field})) {
            // Highly unlikely this is set to 1/1/1970, return null for notset dates.
            return null;
        }

        $formatfields = ['summaryformat', 'endnoteformat'];
        if (in_array($field, $formatfields)) {
            $prop = substr($field, 0, -6);
            if (document_helper::is_valid_json_document($certification->{$prop})) {
                return 'JSON_EDITOR';
            } else {
                return 'HTML';
            }
        }

        if ($field == 'category') {
            return \coursecat::get($certification->category);
        }

        // Include certification specific fields here.
        if ($field == 'activeperiod') {
            $certif = $DB->get_record('certif', ['id' => $certification->certifid]);

            // Note: this is saved as "1 Month" in the database and won't be translated...
            $certification->activeperiod = $certif->activeperiod;
        }

        $duefields = ['duedate', 'duedate_state'];
        if ($field == 'completion' || in_array($field, $duefields)) {
            // Note: This loads the duedate as well so I've combined them here,
            // however completion is it's own object and duedate is part of the certification.
            list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $USER->id, false);
            if (empty($certcompletion) || empty($progcompletion)) {
                return null; // No completion information for this user.
            } else {

                if ($field == 'duedate') {
                    if (!empty($progcompletion->timedue) && $progcompletion->timedue != -1) {
                        $certification->duedate = $progcompletion->timedue;
                    } else {
                        return null;
                    }
                }

                // Mobile - override duedate state to make them consistent.
                if ($field == 'duedate_state') {
                    if (!empty($progcompletion->timedue) && $progcompletion->timedue != -1) {
                        $certification->duedate_state = mobile_duedate_state::calculate($progcompletion->timedue);
                    } else {
                        return null;
                    }
                }

                if ($field == 'completion') {
                    // Hand through all the completion information.
                    return [$certcompletion, $progcompletion];
                }
            }
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
            $content = $certification->get_content();
            $csets = $content->get_course_sets();

            // Return all the coursesets in the certification, without extra formatting.
            if ($field == 'coursesets') {
                return $csets;
            }

            // Sort the coursesets into only the currently available ones and return.
            if (in_array($field, $currentdata)) {
                // We want to mimic the learning item process here, so lets just use it.
                if ($item = certification_item::one($USER, $certification)) {
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

        // Copied from the mobile_learning_item type resolver.
        if ($field == 'image_src' || $field == 'mobile_image') {
            $certification->image_src = $certification->get_image();

            if ($field == 'mobile_image') {
                $component = 'totara_certification';
                $filearea = 'totara_certification_default_image';

                if ($certification->image_src == $OUTPUT->image_url('defaultimage', $component)) {
                    $certification->mobile_image = "";
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
                    if ($url && $certification->image_src == $url) {
                        $certification->mobile_image = "";
                    } else {
                        $certification->mobile_image = $certification->image_src;
                    }
                }
            }
        }

        $formatter = new mobile_certification_formatter($certification, $program_context);
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
            return has_capability('totara/certification:configuredetails', $context);
        }
        // Permission to see RAW formatted text fields
        if (in_array($field, ['summary', 'endnote']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/certification:configuredetails', $context);
        }
        return true;
    }
}
