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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\customfield_area;

use context_system;

/**
 * Facilitator custom field management class.
 *
 * @package mod_facetoface
 * @category totara_customfield
 */
class facetofacefacilitator  implements \totara_customfield\area {

    /**
     * Returns the component for the Seminar facilitator custom field area.
     * @return string
     */
    public static function get_component(): string {
        return 'mod_facetoface';
    }

    /**
     * Returns the name for the Seminar facilitator custom field area.
     * @return string facetofacefacilitator
     */
    public static function get_area_name(): string {
        return 'facetofacefacilitator';
    }

    /**
     * Returns an array of fileareas owned by the Seminar facilitator custom field area.
     * @return string[]
     */
    public static function get_fileareas(): array {
        return [
            'facetofacefacilitator',
            'facetofacefacilitator_filemgr',
        ];
    }

    /**
     * Returns the table prefix used by the Seminar facilitator custom field area.
     * @return string facetoface_facilitator
     */
    public static function get_prefix(): string {
        return 'facetoface_facilitator';
    }

    /**
     * Returns the context_system::instance()
     * @return context_system
     */
    public static function get_context(): context_system {
        global $TEXTAREA_OPTIONS;
        return $TEXTAREA_OPTIONS['context'];
    }

    /**
     * Returns true if the user can view the Seminar facilitator custom field area.
     * @param \stdClass|int $instanceorid An instance record OR the id of the instance. If a record is given it must be complete.
     * @return bool
     */
    public static function can_view($instanceorid): bool {
        global $DB;

        if (is_object($instanceorid)) {
            // If its a full blown object with an id then we will assume you can see it.
            return isset($instanceorid->id);
        }
        // Check that the given ID is valid.
        return $DB->record_exists('facetoface_facilitator', ['id' => $instanceorid]);
    }

    /**
     * Serves a file belonging to the Seminar facilitator custom field area.
     * @param \stdClass $course
     * @param \stdClass $cm
     * @param \context $context
     * @param string $filearea
     * @param array $args
     * @param bool $forcedownload
     * @param array $options
     * @return void
     */
    public static function pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = []): void {
        global $DB;

        if (!in_array($filearea, self::get_fileareas())) {
            // The given file area does not belong to this customfield area, or is not real.
            send_file_not_found();
        }

        require_login($course, false, $cm, false, true);

        // OK first up we need to verify if the user can access this.
        $id = reset($args);
        $sql = 'SELECT ff.*
                  FROM {facetoface_facilitator_info_data} ffid
                  JOIN {facetoface_facilitator} ff ON ff.id = ffid.facetofacefacilitatorid
                 WHERE ffid.id = :id';
        $asset = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);
        $allowaccess = self::can_view($asset);

        if ($allowaccess) {
            $fs = get_file_storage();
            $fullpath = "/{$context->id}/totara_customfield/$filearea/$args[0]/$args[1]";
            if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
                send_file_not_found();
            }
            // Finally send the file.
            send_stored_file($file, 86400, 0, true, $options); // download MUST be forced - security!
        }
        send_file_not_found();
    }
}