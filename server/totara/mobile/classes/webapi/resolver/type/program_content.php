<?php
/**
 *
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_currentlearning
 */

namespace totara_mobile\webapi\resolver\type;

use core\format;
use core\orm\query\builder;
use core\webapi\execution_context;
use totara_core\user_learning\item_has_dueinfo;
use totara_core\webapi\resolver\type\learning_item as core_item;
use core_course\user_learning\item as course_item;
use totara_mobile\local\duedate_state as mobile_duedate_state;
use totara_mobile\formatter\mobile_program_content_formatter;

class program_content extends core_item {

    /**
     * Resolve program fields
     *
     * @param string $field
     * @param item|item_base $item
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $item, array $args, execution_context $ec) {
        global $CFG;

        if (!$item instanceof course_item) {
             throw new \coding_exception('Only program content learning_item objects are accepted: ' . gettype($item));
        }

        $format = $args['format'] ?? null;
        $context = parent::get_item_context($item);
        self::authorize($item, $field, $format, $context);

        $formatter = new mobile_program_content_formatter($item, $context);

        // Run through the fields, overriding for mobile where necessary.
        switch ($field) {
            case 'id':
                // Override field - concatenate type and id to form unique identifiers.
                if ($field == 'id') {
                    return $item->get_type() . '_' . $item->id;
                }
                break;
            case 'description_format':
                // Override field - make the duedate state consistent.
                if ($field == 'description_format') {
                    return format::from_moodle($item->description_format);
                }
                break;
            case 'duedate_state':
                // Mobile - override duedate state to make them consistent.
                if ($item instanceof item_has_dueinfo) {
                    $item->ensure_duedate_loaded();
                } else {
                    return null;
                }

                if ($field == 'duedate_state') {
                    if (!empty($item->duedate) && $item->duedate != -1) {
                        $item->duedate_state = mobile_duedate_state::calculate($item->duedate);
                    } else {
                        return null;
                    }
                }
                break;
            case 'mobile_coursecompat':
                // Mobile only field.
                if ($item->get_type() == 'course') {
                    if (!empty($item->id)) {
                        $item->mobile_coursecompat = (bool) builder::table('totara_mobile_compatible_courses')
                            ->where('courseid', $item->id)
                            ->count();
                    } else {
                        $item->mobile_coursecompat = false;
                    }
                } else {
                    $item->mobile_coursecompat = true;
                }
                break;
            case 'mobile_image':
                // Mobile only field - blank if the item has default image. We have to kind of reverse-engineer this.
                if (empty($item->image_src)) {
                    $item->image_src = "";
                }
                if ($item->image_src instanceof \moodle_url) {
                    $item->image_src = $item->image_src->out();
                }
                $item->mobile_image = $item->image_src;

                break;
            default:
                // Note: These will also be run through the learning item formatter rather than the
                // mobile_learning_item formatter, if that needs to change they need to be overridden above.
                $formatted = parent::resolve($field, $item, $args, $ec);
        }

        // If this hasn't already returned or been run through the parent formatter.
        if (empty($formatted)) {
            $formatted = $formatter->format($field, $format);
        }

        // Rewrite pluginfile urls in description and image_src fields.
        if (in_array($field, ['description', 'image_src', 'mobile_image'])) {
            $formatted = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $formatted);
        }
        return $formatted;
    }

    /**
     * Override parent authorization to enable an extra coding error for non course learning items in program content.
     *
     * @param item|item_base $item
     * @param string|null $format
     * @param string $field
     * @param context $context
     * @return bool
     * @throws coding_exception
     */
    protected static function authorize($item, $field, $format, $context) {

        $itemtype = $item->get_type();
        if ($itemtype == 'course') {
            $classpath = 'core\webapi\resolver\type\course';
        } else {
            // Largely superfluous due to the earlier check, but doesn't hurt to be sure.
            throw new \coding_exception("Unexpected learning type in program content '{$itemtype}'");
        }

        $authfield = ($field == 'description') ? 'summary' : $field;
        if (!$classpath::authorize($authfield, $format, $context)) {
            throw new \coding_exception("Not authorized to request this format for '{$authfield}'");
        }
    }
}
