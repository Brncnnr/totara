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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\course;

use context_course;
use file_exception;
use file_storage;
use stored_file;

class course_image_downloader {
    /**
     * @var int
     */
    protected $context_id;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var file_storage
     */
    protected $fs;

    /**
     * course_image_downloader constructor.
     * @param int $course_id
     * @param string $url
     */
    public function __construct(int $course_id, string $url) {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->fs = get_file_storage();
        $context = context_course::instance($course_id);
        $this->context_id = $context->id;
        $this->url = $url;
    }

    /**
     * Create file record when return ture, the file exists, we do not want to create it again.
     *
     * @return bool|stored_file
     */
    public function download_image_for_course() {
        $filename = $this->make_filename();
        $info = $this->get_file_info($filename);

        if ($this->fs->file_exists($this->context_id, $info['component'], $info['filearea'], $info['itemid'], $info['filepath'], $info['filename'])) {
            return true;
        }

        try {
            $file = $this->fs->create_file_from_url($info, $this->url, null, true);
        } catch (file_exception $e) {
            debugging('Unable to download remote image ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        return $file;
    }

    /**
     * @return string
     */
    private function make_filename(): string {
        $pathinfo = pathinfo($this->url);
        $filename = $pathinfo['filename'];

        return "{$filename}.{$pathinfo['extension']}";
    }

    /**
     * @param string $filename
     * @return array
     */
    private function get_file_info(string $filename): array {
        // Compatible with course
        return [
            'contextid' => $this->context_id,
            'component' => 'course',
            'filearea' => 'images',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename
        ];
    }

    /**
     * @param course_image_downloader $new_course_image
     * @return void
     */
    public function compare_and_update(course_image_downloader $new_course_image): void {
        $old_filename = $this->make_filename();
        $info = $this->get_file_info($old_filename);

        $old_file = $this->fs->get_file($this->context_id, $info['component'], $info['filearea'], $info['itemid'], $info['filepath'], $old_filename);
        $files = $this->fs->get_area_files($this->context_id, $info['component'], $info['filearea'], $info['itemid'], "timemodified DESC", false);

        $image_not_changed = false;
        $temp_file = null;
        if ($files) {
            foreach ($files as $file) {
                if ($old_file !== false) {
                    // If old file content is not the same as current one, it means image is changed.
                    if ($file->get_contenthash() == $old_file->get_contenthash()) {
                        $temp_file = $file;
                        $image_not_changed = true;
                    }
                }

                // If current filename is not the same as old one, it means course admin has been updated image manually.
                if ($file->get_filename() == $old_filename) {
                    $temp_file = $file;
                    $image_not_changed = true;
                }
            }
        }

        if ($image_not_changed) {
            // We just download new one, delete old one.
            $temp_file->delete();
            $new_course_image->download_image_for_course();
        }
    }
}