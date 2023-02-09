<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\hook;

/**
 * Hook called right before standard file serving from local filedir in send_stored_file(),
 * this hook is intended local plugins that want to serve file contents directly from the cloud.
 */
final class filedir_xsendfile extends base {
    /** @var string sh1 content hash */
    public $contenthash;
    /** @var bool file sent flag */
    private $filesent = false;

    /**
     * Hook constructor.
     * @param string $filename
     */
    public function __construct(string $contenthash) {
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
     * Must be called by hook observer after sending file.
     */
    public function mark_as_sent(): void {
        $this->filesent = true;
    }

    /**
     * To be called by each hook observer to make sure file was not sent yet.
     * @return bool
     */
    public function was_file_sent(): bool {
        return $this->filesent;
    }
}
