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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_static_content
 */

defined('MOODLE_INTERNAL') || die();

function performelement_static_content_fix_broken_elements(): void {
    global $CFG, $DB;

    $upgrade_lib_file = $CFG->dirroot . '/lib/editor/weka/db/upgradelib.php';
    // As we depend on the weka editor plugin being installed skip this if the lib file is not there
    if (file_exists($upgrade_lib_file)) {
        require_once $upgrade_lib_file;

        $records = $DB->get_records('perform_element', ['plugin_name' => 'static_content']);
        foreach ($records as $record) {
            $data = json_decode($record->data, true);
            if ($data === null || empty($data['wekaDoc'])) {
                // Unexpected data structure, let's skip this one.
                // The empty $data['wekaDoc'] should never be happened, but who knows.
                continue;
            }

            $updated_weka_doc = editor_weka_fix_attachments_with_empty_url($data['wekaDoc']);
            if ($updated_weka_doc) {
                $data['wekaDoc'] = $updated_weka_doc;
                $record->data = json_encode($data);

                $DB->update_record('perform_element', $record);
            }
        }
    }
}
