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
 * @package totara_core
 */

namespace totara_core\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_core\formatter\learning_item_formatter;
use totara_core\user_learning\item;
use totara_core\user_learning\item_base;
use totara_core\user_learning\item_has_dueinfo;
use totara_core\user_learning\item_has_image;
use totara_core\user_learning\item_has_progress;

class learning_item extends type_resolver {

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

        if (!$item instanceof item) {
            throw new \coding_exception('Only learning_item objects are accepted: ' . gettype($item));
        }

        $format = $args['format'] ?? null;
        $context = self::get_item_context($item);
        self::authorize($item, $field, $format, $context); // Verify user is authorized to access item.

        // Transform the format field from the constants to a core_format string.
        if ($field == 'description_format') {
            return format::from_moodle($item->description_format);
        }

        if ($field == 'duedate') {
            // Make sure we have the due date, this is for programs and certifications, also courses inside learning plans.
            if ($item instanceof item_has_dueinfo) {
                $item->ensure_duedate_loaded();
                if (empty($item->duedate) || $item->duedate == -1) {
                    return null;
                }
            } else {
                return null;
            }
        }

        // Progress actually maps to progress_percentage
        if ($field == 'progress') {
            // Make sure we have the percentage in the progress.
            if ($item instanceof item_has_progress && $item->can_be_completed()) {
                return $item->get_progress_percentage();
            } else {
                return null;
            }
        }

        if ($field === 'progress_summary') {
            // Make sure we have the percentage in the progress.
            if ($item instanceof item_has_progress) {
                return $item->get_progress_summary();
            } else {
                return null;
            }
        }

        if ($field == 'duedate_state') {
            if (empty($item->duedate) || $item->duedate == -1) {
                $item->duedate_state = null; // For consistency.
            }
        }

        if ($field == 'url_view') {
            return $item->url_view->out();
        }

        if ($field == 'image_src') {
            if ($item instanceof item_has_image) {
                return $item->get_image();
            }
            return null;
        }

        $formatter = new learning_item_formatter($item, $context);
        return $formatter->format($field, $format);
    }

    /**
     * Verify that the item is the expected type, verify the classpath and check current user authorization.
     *
     * @param item|item_base $item
     * @param string $field
     * @param string|null $format
     * @param context $context
     * @return bool
     * @throws coding_exception
     */
    protected static function authorize($item, $field, $format, $context) {

        if ($item->get_type() == 'course') {
            $classpath = 'core\webapi\resolver\type\course';
        } else {
            $classpath = $item->get_component() . '\webapi\resolver\type\\' . $item->get_type();
        }

        $authfield = ($field == 'description') ? 'summary' : $field;
        if (!$classpath::authorize($authfield, $format, $context)) {
            throw new \coding_exception("Not authorized to request this format for '{$authfield}'");
        }

        return true;
    }

    /**
     * @param item|item_base $item
     * @return \context_course|\context_program|false
     */
    protected static function get_item_context(item $item) {
        switch ($item->get_type()) {
            case 'course':
                return \context_course::instance($item->id);
                break;
            case 'program':
            case 'certification':
                return \context_program::instance($item->id);
                break;
        }
    }
}
