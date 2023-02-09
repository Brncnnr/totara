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

use file_archive;
use zip_archive;
use totara_msteams\manifest\output;

/**
 * Output to a zip archive.
 */
final class zip_output implements output {
    /** @var zip_archive|null */
    private $ziparchive = null;

    /**
     * Open or create a zip file.
     *
     * @param string $archivepathname The full path to the zip file.
     * @param int $mode OPEN, CREATE or OVERWRITE constant of file_archive.
     * @param string $encoding archive local paths encoding, empty means autodetect
     * @return bool
     */
    public function open(string $archivepathname, int $mode = file_archive::CREATE, string $encoding = null): bool {
        if ($this->ziparchive) {
            $this->ziparchive->close();
        }
        $this->ziparchive = new zip_archive();
        $result = $this->ziparchive->open($archivepathname, $mode, $encoding);
        if (!$result) {
            $this->ziparchive = null;
        }
        return $result;
    }

    /**
     * Close the current archive. Must be called at the end.
     *
     * @return bool
     */
    public function close(): bool {
        if (!$this->ziparchive) {
            return false;
        }
        $result = $this->ziparchive->close();
        $this->ziparchive = null;
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function write(string $filespec, string $contents): bool {
        if (!$this->ziparchive) {
            return false;
        }
        return $this->ziparchive->add_file_from_string($filespec, $contents);
    }
}
