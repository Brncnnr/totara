<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\userdata;

use stored_file;

use core\collection;
use core\orm\query\builder;

use mod_perform\userdata\helpers\userdata_file_helper;

use totara_userdata\userdata\export;

/**
 * Holds custom export data.
 */
class custom_userdata_exports {
    /**
     * @var array custom exported data.
     */
    private $exported_data = [];

    /**
     * @var string[] file item ids to use when retrieving file items.
     */
    private $file_item_ids = [];

    /**
     * Adds the given export data.
     *
     * @param array $exports keyed array of export values.
     *
     * @return self this object.
     */
    public function add_exports(...$exports): self {
        $this->exported_data = array_merge($this->exported_data, $exports);

        return $this;
    }

    /**
     * Adds file item ids that will identify the files for export.
     *
     * @param int[] $file_item_ids the ids to add.
     *
     * @return self this object.
     */
    public function add_file_item_ids(int ...$file_item_ids): self {
        $this->file_item_ids = array_unique(
            array_merge($this->file_item_ids, $file_item_ids)
        );

        return $this;
    }

    /**
     * Updates the given parent exports instance with the contents of this
     * object.
     *
     * @param export userdata exports object to update.
     *
     * @return the updated userdata exports data.
     */
    public function add_to_exports(export $exports): export {
        if (!empty($this->exported_data)) {
            $exports->data = array_merge($exports->data, $this->exported_data);
        }

        if (!empty($this->file_item_ids)) {
            $existing_files = $exports->files;
            $existing_ids = collection::new($existing_files)
                ->map(
                    function (stored_file $file): int {
                        return $file->get_id();
                    }
                )
                ->all();

            $additional_files = $this->get_response_files($this->file_item_ids, $existing_ids);
            if (!empty($additional_files)) {
                $exports->files = array_merge($existing_files, $additional_files);
            }
        }

        return $exports;
    }

    /**
     * Returns the set of files associated with the current set of response ids.
     *
     * @param int[] $file_item_ids items whose files are to be retrieved.
     * @param int[] $excluded_file_ids file ids that should not be retrieved even
     *        if the belong to the specified item ids.
     *
     * @return stored_file[] array of stored_files, keyed by the file ID.
     */
    private function get_response_files(
        array $file_item_ids,
        array $excluded_file_ids
    ): array {
        $fs = get_file_storage();

        $builder = builder::table('files')
            ->when(
                true,
                function (builder $builder): void {
                    userdata_file_helper::apply_respondable_element_file_restrictions($builder);
                }
            )
            ->where_in('itemid', $file_item_ids)
            ->where('filename', '!=', '.');

        if (!empty($excluded_file_ids)) {
            $builder->where_not_in('id', $excluded_file_ids);
        }

        return $builder
            ->get()
            ->map(
                function (object $file) use ($fs) {
                    return $fs->get_file_instance($file);
                }
            )
            ->all();
    }
}
