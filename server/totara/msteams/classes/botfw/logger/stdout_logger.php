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

namespace totara_msteams\botfw\logger;

/**
 * A simple logger that sends to the standard output.
 */
final class stdout_logger implements logger {
    /**
     * @inheritDoc
     */
    public function error(string $message): void {
        echo "ERROR: {$message}\n";
    }

    /**
     * @inheritDoc
     */
    public function warn(string $message): void {
        echo "WARN: {$message}\n";
    }

    /**
     * @inheritDoc
     */
    public function info(string $message): void {
        echo "INFO: {$message}\n";
    }

    /**
     * @inheritDoc
     */
    public function log(string $message): void {
        echo "{$message}\n";
    }

    /**
     * @inheritDoc
     */
    public function debug(string $message): void {
        global $CFG;
        if ($CFG->debugdeveloper) {
            echo "{$message}\n";
        }
    }
}
