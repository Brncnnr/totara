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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

namespace core\files;

use context;
use moodle_url;
use stored_file;

final class file_helper {

    /** @var string */
    private $component;

    /** @var string */
    private $area;

    /** @var int */
    private $item_id = 0;

    /** @var context */
    private $context;

    /** @var string */
    private $sort;

    /**
     * file_helper constructor.
     *
     * @param string $component
     * @param string $area
     * @param context $context
     */
    public function __construct(string $component, string $area, context $context) {
        $this->component = $component;
        $this->area = $area;
        $this->context = $context;
        $this->sort = 'timecreated';
    }

    /**
     * @param int $id
     */
    public function set_item_id(int $id): void {
        $this->item_id = $id;
    }

    /**
     * @param string $sort
     */
    public function set_sort(string $sort): void {
        $this->sort = $sort;
    }

    /**
     * Prepare draft area for uploading files.
     *
     * @param int|null $draft_id
     *
     * @return int
     */
    public function prepare_draft_area(?int $draft_id = null): int {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        file_prepare_draft_area(
            $draft_id,
            $this->context->id,
            $this->component,
            $this->area,
            $this->item_id
        );

        return $draft_id;
    }

    /**
     * @param int $user_id
     * @param int|null $draft_id
     *
     * @return file_area
     */
    public function create_file_area(int $user_id, ?int $draft_id = null): file_area {
        return new file_area(
            $this->prepare_draft_area($draft_id),
            $this->get_upload_repository_id($user_id),
            $this->get_upload_repository_url()->out()
        );
    }

    /**
     * @param int $user_id
     *
     * @return int
     */
    public function get_upload_repository_id(int $user_id): int {
        global $CFG;

        require_once("{$CFG->dirroot}/repository/lib.php");

        $repositories = \repository::get_instances([
            'currentcontext' => $this->context,
            'type' => 'upload',
            'userid' => $user_id
        ]);

        if (empty($repositories)) {
            throw new \coding_exception("Cannot find repository for upload");
        }

        $repository = reset($repositories);
        return $repository->id;
    }

    /**
     * @return moodle_url
     */
    public function get_upload_repository_url(): moodle_url {
        return new moodle_url("/repository/repository_ajax.php", ['action' => 'upload']);
    }

    /**
     * @return array
     */
    public function get_image_accept_types(): array {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");
        return file_get_typegroup('extension', ['web_image']);
    }

    /**
     * @param int $draft_id
     * @param int $user_id
     * @param array|null $options
     */
    public function save_files(int $draft_id, int $user_id, ?array $options = []): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();

        $user_context = \context_user::instance($user_id);
        $draft_files = $fs->get_area_files(
            $user_context->id,
            'user',
            'draft',
            $draft_id,
            'timemodified desc'
        );

        $draft_files = array_filter(
            $draft_files,
            function (stored_file $file): bool {
                return !$file->is_directory();
            }
        );

        if (empty($draft_files)) {
            return;
        }

        $max = $options['maxfiles'] ?? 1;
        if (count($draft_files) > $max) {
            // There are more than one draft file. We can assume that the last file uploaded
            // will be the exact file that the user wants to use.
            debugging("There are more than just one draft files", DEBUG_DEVELOPER);

            // Pop the last item, which is the file that we want (it will remain in the draft area).
            array_splice($draft_files, 0, $max);
            foreach ($draft_files as $draft_file) {
                $draft_file->delete();
            }
        }

        // Save file that is left in draft area.
        file_save_draft_area_files(
            $draft_id,
            $this->context->id,
            $this->component,
            $this->area,
            $this->item_id,
            $options
        );
    }

    /**
     * @return stored_file[]
     */
    public function get_stored_files(): array {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();

        $files = $fs->get_area_files(
            $this->context->id,
            $this->component,
            $this->area,
            $this->item_id,
            $this->sort
        );

        // Filter out directories.
        $files = array_filter(
            $files,
            function(stored_file $file): bool {
                return !$file->is_directory();
            }
        );

        return $files;
    }

    /**
     * Get the URL of a file.
     *
     * @return moodle_url|null
     */
    public function get_file_url(): ?moodle_url {
        // Get files for current component and context.
        $files = $this->get_stored_files();

        // No files saved.
        if (empty($files)) {
            return null;
        }

        // Return first file found.
        /** @var stored_file $file */
        $file = reset($files);
        return moodle_url::make_pluginfile_url(
            $this->context->id,
            $this->component,
            $this->area,
            0,
            '/',
            $file->get_filename()
        );
    }
}