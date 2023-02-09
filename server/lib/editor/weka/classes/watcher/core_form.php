<?php
/**
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package editor_weka
 */

namespace editor_weka\watcher;

use core_form\hook\editor_formats_available;

/**
 * Class for catching core form hooks in weka editor.
 *
 * This class manages watchers for one hook:
 *
 *    1. \core_form\hook\editor_formats_available
 *        Allows us to manipulate the content formats available for editor fields to include
 *          mobile-friendly JSON as an option.
 *
 * @package editor_weka\watcher
 */
class core_form {

    /**
     * Hook watcher that manipulates the content formats available for the editor, in order
     * to enable mobile-friendly content conversion.
     *
     * @param edit_form_definition_complete $hook
     */
    public static function extend_editor_formats(editor_formats_available $hook): void {
        // Only do this if weka editor is enabled.
        if (!in_array('weka', editors_get_enabled_names())) {
            return;
        }

        $options = $hook->get_options();
        $values = $hook->get_values();
        $formats = $hook->get_formats();

        // Only do this if the allowjsonconversion option is set.
        if (empty($options['allowjsonconversion'])) {
            return;
        }

        // Only do this if JSON editor is not already there
        if (!empty($formats[FORMAT_JSON_EDITOR])) {
            return;
        }

        $format = $values['format'];
        $text = $values['text'];

        // Allow conversion to JSON from specific formats only.
        $allow_conversion = [FORMAT_HTML, FORMAT_MOODLE];
        if (in_array($format, $allow_conversion)) {
            $hook->set_format(FORMAT_JSON_EDITOR, get_string('mobile_friendly_format', 'core_editor'));
        } else if ($format == FORMAT_JSON_EDITOR && !\core\json_editor\helper\document_helper::looks_like_json($text)) {
            // Allow conversion back to HTML if text is still HTML (despite claiming to be JSON_EDITOR) because it can still be reverted.
            $hook->set_format(FORMAT_JSON_EDITOR, get_string('mobile_friendly_format', 'core_editor'));
            $hook->set_format(FORMAT_HTML, get_string('formathtml'));
        }
    }
}