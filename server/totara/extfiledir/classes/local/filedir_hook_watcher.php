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
 * @package totara_extfiledir
 */

namespace totara_extfiledir\local;

/**
 * Hook callbacks for filedir changes necessary for cloning to external directories.
 */
final class filedir_hook_watcher {
    /**
     * Called after file is added to local filedir folder.
     *
     * @param \totara_core\hook\filedir_content_file_added $hook
     */
    public static function content_file_added(\totara_core\hook\filedir_content_file_added $hook): void {
        $stores = store::get_stores();
        if (!$stores) {
            return;
        }

        $contenthash = $hook->get_contenthash();

        foreach ($stores as $store) {
            if (!$store->is_active()) {
                continue;
            }
            if (!$store->add_enabled()) {
                continue;
            }

            $filewriter = function (string $targetfile) use ($hook) {
                return $hook->copy_contentfile_to($targetfile);
            };

            $store->write_content($contenthash, $filewriter);
        }
    }

    /**
     * Called after file is deleted from local filedir folder.
     *
     * @param \totara_core\hook\filedir_content_file_deleted $hook
     */
    public static function content_file_deleted(\totara_core\hook\filedir_content_file_deleted $hook): void {
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
                        // This filedir can be used instead of trashdir.
                        $hook->mark_as_restorable();
                    }
                }
                continue;
            }
            $store->delete_content($contenthash);
        }
    }

    /**
     * Called when content file is missing in local filedir folder.
     *
     * @param \totara_core\hook\filedir_content_file_restore $hook
     */
    public static function content_file_restore(\totara_core\hook\filedir_content_file_restore $hook): void {
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
}
