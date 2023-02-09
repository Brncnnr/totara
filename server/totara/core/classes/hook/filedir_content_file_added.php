<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\hook;

/**
 * Hook called after a new file is added to the filedir pool.
 *
 * NOTE: this hook is intended local plugins that implement cloud storage
 *       or other backup solutions.
 */
final class filedir_content_file_added extends base {
    /** @var string full path to hash file */
    private $contentfile;
    /** @var string sh1 content hash */
    private $contenthash;

    /**
     * Hook constructor.
     * @param string $contentfile
     * @param string $contenthash
     */
    public function __construct(string $contentfile, string $contenthash) {
        $this->contentfile = $contentfile;
        $this->contenthash = $contenthash;
    }

    /**
     * Return sha1 hash of file content.
     * @return string
     */
    public function get_contenthash(): string {
        return $this->contenthash;
    }

    /**
     * Returns file size, false if does no exist.
     * @return false|int
     */
    public function get_filesize() {
        return filesize($this->contentfile);
    }

    /**
     * Copy file to target.
     *
     * @param string $filename
     * @return bool success
     */
    public function copy_contentfile_to(string $filename): bool {
        return copy($this->contentfile, $filename);
    }

    /**
     * Get read only stream and file size for copy operation.
     * @return array [stream resource, file size]
     */
    public function get_stream_and_size(): array {
        return [fopen($this->contentfile, 'r'), filesize($this->contentfile)];
    }

}
