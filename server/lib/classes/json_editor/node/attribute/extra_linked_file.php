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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\json_editor\node\attribute;

use coding_exception;
use moodle_url;

class extra_linked_file {
    /**
     * @var string
     */
    private $file_url;

    /**
     * @var string
     */
    private $filename;

    /**
     * extra_linked_file constructor.
     * @param string $file_url
     * @param string $filename
     */
    public function __construct(string $file_url, string $filename) {
        $this->file_url = $file_url;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function get_file_url_raw(): string {
        return $this->file_url;
    }

    /**
     * @param bool $force_download
     * @return moodle_url
     */
    public function get_file_url(bool $force_download = false): moodle_url {
        $this->ensure_url_rewritten();
        $url = new moodle_url($this->file_url);

        if ($force_download) {
            $url->param('forcedownload', 1);
        }

        return $url;
    }

    /**
     * @return bool
     */
    public function is_url_rewritten(): bool {
        return (false === strpos($this->file_url, '@@PLUGINFILE@@', 0));
    }

    /**
     * @return void
     */
    public function ensure_url_rewritten(): void {
        if (!$this->is_url_rewritten()) {
            throw new coding_exception("The file url had not been rewritten yet");
        }
    }

    /**
     * @return string
     */
    public function get_filename(): string {
        return $this->filename;
    }
}