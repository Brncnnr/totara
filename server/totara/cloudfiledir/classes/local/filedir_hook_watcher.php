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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_cloudfiledir
 */

namespace totara_cloudfiledir\local;

/**
 * Hook callbacks for filedir changes necessary for cloud file content storage.
 */
final class filedir_hook_watcher {
    public static function content_file_added(\totara_core\hook\filedir_content_file_added $hook): void {
        global $DB;

        if (!get_config('totara_cloudfiledir', 'version')) {
            // Not fully installed yet.
            return;
        }

        $allstores = store::get_stores();
        if (!$allstores) {
            return;
        }

        // Clear the missing flag in all stores.
        $contenthash = $hook->get_contenthash();
        $DB->set_field('totara_cloudfiledir_sync', 'localproblem', 0, ['contenthash' => $contenthash]);

        $stores = [];
        foreach ($allstores as $store) {
            if (!$store->is_active()) {
                continue;
            }
            if (!$store->add_enabled()) {
                continue;
            }
            if (!$store->is_instant_upload($hook->get_filesize())) {
                continue;
            }
            $stores[] = $store;
        }
        if (!$stores) {
            return;
        }

        $sql = 'SELECT idnumber, 1
                  FROM "ttr_totara_cloudfiledir_sync"
                 WHERE contenthash = ? AND timeuploaded > 0';
        $existing = $DB->get_records_sql_menu($sql, [$contenthash]);

        foreach ($stores as $store) {
            if (isset($existing[$store->get_idnumber()])) {
                // This was already uploaded once before.
                continue;
            }
            $streaminfo = function () use ($hook) {
                return $hook->get_stream_and_size();
            };

            $store->write_content($hook->get_contenthash(), $streaminfo);
        }
    }

    public static function content_file_deleted(\totara_core\hook\filedir_content_file_deleted $hook): void {
        if (!get_config('totara_cloudfiledir', 'version')) {
            // Not fully installed yet.
            return;
        }

        $stores = store::get_stores();
        if (!$stores) {
            return;
        }

        $contenthash = $hook->get_contenthash();

        foreach ($stores as $store) {
            if (!$store->is_active()) {
                continue;
            }

            if (!$store->delete_enabled()) {
                if ($store->restore_enabled()) {
                    if ($store->is_content_available($contenthash, true)) {
                        // This cloud store can be used instead of trashdir.
                        $hook->mark_as_restorable();
                    }
                }
                continue;
            }
            $store->delete_content($contenthash);
        }
    }

    public static function content_file_restore(\totara_core\hook\filedir_content_file_restore $hook): void {
        if (!get_config('totara_cloudfiledir', 'version')) {
            // Not fully installed yet.
            return;
        }

        if ($hook->was_restored()) {
            return;
        }

        $stores = store::get_stores();
        if (!$stores) {
            return;
        }

        $contenthash = $hook->get_contenthash();

        foreach ($stores as $store) {
            if (!$store->is_active()) {
                continue;
            }
            if (!$store->restore_enabled()) {
                continue;
            }

            $filereader = function (string $extfilepath) use ($hook) {
                return $hook->restore_file($extfilepath);
            };
            $store->read_content($contenthash, $filereader);
            if ($hook->was_restored()) {
                break;
            }
        }
    }

    public static function xsendfile(\totara_core\hook\filedir_xsendfile $hook): void {
        if (!get_config('totara_cloudfiledir', 'version')) {
            // Not fully installed yet.
            return;
        }

        if ($hook->was_file_sent()) {
            return;
        }

        $stores = store::get_stores();
        if (!$stores) {
            return;
        }

        $contenthash = $hook->get_contenthash();

        foreach ($stores as $store) {
            if (!$store->is_active()) {
                continue;
            }

            $headers = $store->xsendfile($contenthash);
            if (!$headers) {
                continue;
            }

            foreach ($headers as $header) {
                header($header);
            }

            $hook->mark_as_sent();
            return;
        }
    }
}
