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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

namespace core\files;

/**
 * Class file_area
 *
 * Wrapper class to contain file area configuration.
 *
 * @package core\files
 */
final class file_area {

    /** @var int */
    private $draft_id;

    /** @var int */
    private $repository_id;

    /** @var string */
    private $url;

    /**
     * file_area constructor.
     *
     * @param int $draft_id
     * @param int $repository_id
     * @param string $url
     */
    public function __construct(int $draft_id, int $repository_id, string $url) {
        $this->draft_id = $draft_id;
        $this->repository_id = $repository_id;
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function to_array(): array {
        return [
            'draft_id' => $this->draft_id,
            'repository_id' => $this->repository_id,
            'url' => $this->url,
        ];
    }

    /**
     * @return int
     */
    public function get_draft_id(): int {
        return $this->draft_id;
    }

    /**
     * @return int
     */
    public function get_repository_id(): int {
        return $this->repository_id;
    }

    /**
     * @return string
     */
    public function get_url(): string {
        return $this->url;
    }

}