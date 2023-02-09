<?php
/**
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka\local;

use context;
use context_system;
use moodle_url;
use repository;

/**
 * Local API for preparing the fileupload area.
 */
final class file_helper {
    /**
     * @param int|null $context_id
     * @return array
     */
    public static function get_upload_repository(?int $context_id = null): array {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/repository/lib.php");

        if (null === $context_id) {
            $context = context_system::instance();
        } else {
            $context = context::instance_by_id($context_id);
        }

        $repositories = repository::get_instances([
            'currentcontext' => $context,
            'type' => 'upload',
            'userid' => $USER->id
        ]);

        $repository_id = 0;
        if (!empty($repositories)) {
            $repository = current($repositories);
            $repository_id = (int) $repository->id;
        }

        return [
            'repository_id' => $repository_id,
            'url' => (new moodle_url('/repository/repository_ajax.php', ['action' => 'upload']))->out(),
            'max_bytes' => get_max_upload_file_size($CFG->maxbytes)
        ];
    }
}
