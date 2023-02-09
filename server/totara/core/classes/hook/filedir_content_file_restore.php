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
 * Hook called when file content is missing.
 *
 * NOTE: this hook is intended local plugins that implement cloud storage
 *       or other backup solutions.
 */
final class filedir_content_file_restore extends base {
    /** @var \file_storage */
    private $fs;
    /** @var string sh1 content hash */
    private $contenthash;
    /** @var bool */
    private $restored = false;

    /**
     * Hook constructor.
     * @param \file_storage $fs
     * @param string $contenthash
     */
    public function __construct(\file_storage $fs, string $contenthash) {
        $this->fs = $fs;
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
     * restore missing file content by putting it into local filedir.
     *
     * @param string $extfilepath
     * @return bool success
     */
    public function restore_file(string $extfilepath): bool {
        if (sha1_file($extfilepath) !== $this->contenthash) {
            return false;
        }
        $this->fs->add_file_to_pool($extfilepath, $this->contenthash);
        $this->restored = true;
        return true;
    }

    /**
     * Was the file already restored?
     *
     * @return bool
     */
    public function was_restored(): bool {
        return $this->restored;
    }
}
