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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\manifest\outputs;

defined('MOODLE_INTERNAL') || die;

use totara_msteams\manifest\output;

/**
 * Output to memory. Useful for debugging.
 */
final class memory_output implements output {
    /** @var string[] */
    private $files = [];

    /**
     * Get an array of the copied files.
     *
     * @return array
     */
    public function get_files(): array {
        return $this->files;
    }

    /**
     * @inheritDoc
     */
    public function write(string $filespec, string $contents): bool {
        $this->files[$filespec] = $contents;
        return true;
    }
}
