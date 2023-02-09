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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package tool_customlang
 */

namespace tool_customlang\testing;

use stdClass, coding_exception;

/**
 * Class tool_customlang_generator
 */
final class generator extends \core\testing\component_generator {

    /**
     * Handler of 'the following "language customisation" exist in "tool_customlang" plugin'
     *
     * @param array|stdClass $data
     */
    public function create_language_customisation_for_behat($data) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/adminlib.php');
        require_once($CFG->dirroot . '/admin/tool/customlang/locallib.php');
        $stringman = get_string_manager();
        $data = (array)(object)$data;
        if (empty($data['lang'])) {
            $lang = 'en';
        } else {
            $lang = $data['lang'];
        }
        if (empty($data['component']) || $data['component'] === 'moodle') {
            $component = 'core';
        } else {
            $component = $data['component'];
        }
        $componentid = $DB->get_field('tool_customlang_components', 'id', ['name' => $component]);
        if (!$componentid) {
            $version = get_component_version($component);
            if (empty($version)) {
                throw new coding_exception("$component does not exist");
            }
            $componentid = $DB->insert_record('tool_customlang_components', ['name' => $component, 'version' => $version]);
        }
        $langstring = stripcslashes($data['string']);
        $stringid = $data['id'];
        $record = $DB->get_record('tool_customlang', [
            'lang' => $lang,
            'componentid' => $componentid,
            'stringid' => $stringid
        ]);
        if (empty($record)) {
            $strings = $stringman->load_component_strings($component, $lang, false, true);
            if (!isset($strings[$stringid])) {
                throw new coding_exception("$stringid does not exist in $component");
            }
            $record = new stdClass();
            $record->lang = $lang;
            $record->componentid = $componentid;
            $record->stringid = $stringid;
            $record->original = $record->master = $strings[$stringid];
        }
        $record->local = $langstring;
        $record->modified = 1;
        $record->outdated = 0;
        $record->timecustomized = $record->timemodified = time();
        if (empty($record->id)) {
            $DB->insert_record('tool_customlang', $record);
        } else {
            $DB->update_record('tool_customlang', $record);
        }
        \tool_customlang_utils::checkin($lang);
        $stringman->reset_caches();
    }
}
