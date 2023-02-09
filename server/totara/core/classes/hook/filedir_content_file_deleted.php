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
 * Hook called after a content file was deleted form filedir pool.
 *
 * NOTE: this hook is intended local plugins that implement cloud storage
 *       or other backup solutions.
 */
final class filedir_content_file_deleted extends base {
    /** @var string relative path to hash file */
    private $contenthash;
    /** @var string|null local trash file location */
    private $trashfile;
    /** @var bool true if restorable */
    private $restorable = false;

    /**
     * Hook constructor.
     * @param string $contenthash
     * @param string|null $trashfile
     */
    public function __construct(string $contenthash, ?string $trashfile) {
        $this->contenthash = $contenthash;
        $this->trashfile = $trashfile;
    }

    /**
     * Return sha1 hash of file content.
     * @return string
     */
    public function get_contenthash(): string {
        return $this->contenthash;
    }

    /**
     * Returns trash file path
     * @return string|null
     */
    public function get_trashfile(): ?string {
        return $this->trashfile;
    }

    /**
     * Returns true if at least one store claims the file can be recovered later.
     * @return bool
     */
    public function is_restorable(): bool {
        return $this->restorable;
    }

    /**
     * Mark file as restorable in the future without the use of local trash dir.
     */
    public function mark_as_restorable(): void {
        $this->restorable = true;
    }
}
