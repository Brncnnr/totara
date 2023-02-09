<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\customfield_area;

use context;
use moodle_exception;
use moodle_url;
use totara_core\advanced_feature;
use totara_evidence\hook\pluginfile_access;
use totara_customfield\area;
use totara_evidence\entity;
use totara_evidence\models\evidence_item;
use totara_evidence\models\helpers\evidence_item_capability_helper;

class evidence implements area {

    /**
     * Returns the component for this area.
     *
     * @return string
     */
    public static function get_component(): string {
        return 'totara_evidence';
    }

    /**
     * Returns the area name for this area.
     *
     * @return string
     */
    public static function get_area_name(): string {
        return 'evidence';
    }

    /**
     * Returns an array of fileareas owned by this customfield area.
     *
     * @return string[]
     */
    public static function get_fileareas(): array {
        return [
            'evidence',
            'evidence_filemgr',
        ];
    }

    /**
     * The component in where the files are stored
     *
     * @return string
     */
    public static function get_filearea_component(): string {
        return 'totara_customfield';
    }

    /**
     * Returns the table prefix used by this custom field area.
     *
     * @return string
     */
    public static function get_prefix(): string {
        return 'evidence';
    }

    /**
     * Returns the table base used by this custom field area.
     *
     * @return string
     */
    public static function get_base_table(): string {
        return entity\evidence_type::TABLE;
    }

    /**
     * Returns the custom fields page url
     *
     * @param int $type_id
     * @return string
     * @throws moodle_exception
     */
    public static function get_url(int $type_id): string {
        return new moodle_url('/totara/evidence/type/fields.php', ['typeid' => $type_id]);
    }

    /**
     * Returns true if the user can view the custom field area for the given instance.
     *
     * @param evidence_item|int $item_or_id An evidence item OR the id of the item.
     * @return bool
     */
    public static function can_view($item_or_id): bool {
        if (!$item_or_id instanceof evidence_item) {
            $item_or_id = evidence_item::load_by_id($item_or_id);
        }
        return evidence_item_capability_helper::for_item($item_or_id)->can_view_item();
    }

    /**
     * Serves a file belonging to this customfield area.
     *
     * @param object $course
     * @param object $cm
     * @param context $context
     * @param string $filearea
     * @param array $args
     * @param bool $forcedownload
     * @param array $options
     */
    public static function pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()): void {
        if (!advanced_feature::is_disabled('evidence') && in_array($filearea, self::get_fileareas())) {
            $file_storage = get_file_storage();
            $component = self::get_filearea_component();
            $file_path = "/{$context->id}/{$component}/{$filearea}/{$args[0]}/{$args[1]}";

            $file = $file_storage->get_file_by_hash(sha1($file_path));
            if ($file && !$file->is_directory()) {
                $evidence = (new entity\evidence_field_data($file->get_itemid()))->item;
                if (self::can_view($evidence->id) || self::hook_pluginfile_can_view($evidence->id, $filearea, $context, $args)) {
                    send_stored_file($file, 86400, 0, true, $options);
                    return;
                }
            }
        }

        send_file_not_found();
    }

    /**
     * Check if user is allowed to view the file via another area.
     *
     * @param int $instance_id
     * @param context $context
     * @param string $file_area
     * @param array $args
     *
     * @return bool
     */
    private static function hook_pluginfile_can_view(
        int $instance_id,
        string $file_area,
        context $context,
        array $args
    ): bool {
        $hook = new pluginfile_access('totara_evidence', $instance_id, $file_area, $context, $args);
        return $hook->execute()->can_view();
    }

}
