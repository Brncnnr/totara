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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_resource
 */
namespace totara_engage\resource;

use totara_engage\repository\resource_completion as repository;
use totara_engage\entity\engage_resource_completion;

final class resource_completion {
    /**
     * @var int
     */
    private $resource_id;

    /**
     * @var int
     */
    private $user_id;

    /**
     * resource_completion constructor.
     * @param int $resource_id
     * @param int $user_id
     */
    private function __construct(int $resource_id, int $user_id) {
        $this->resource_id = $resource_id;
        $this->user_id = $user_id;
    }

    /**
     * @param int $resource_id
     * @param int $user_id
     * @return resource_completion
     */
    public static function instance(int $resource_id, int $user_id): resource_completion {
        return new self($resource_id, $user_id);
    }

    /**
     * @param int|null $actor_id
     * @return bool
     */
    public function can_create(?int $actor_id = null): bool {
        global $USER;

        if (null === $actor_id) {
            $actor_id = (int)$USER->id;
        }

        if ($this->user_id === $actor_id) {
            return false;
        }

        /** @var repository $repository */
        $repository = engage_resource_completion::repository();
        return !$repository->is_exist($this->resource_id, $actor_id);
    }

    /**
     * @param int|null $user_id
     * @return engage_resource_completion
     */
    public function create(?int $user_id = null): engage_resource_completion {
        global $USER;

        if (null === $user_id) {
            $user_id = (int)$USER->id;
        }
        $record = new engage_resource_completion();
        $record->resourceid = $this->resource_id;
        $record->userid = $user_id;
        $record->save();

        return $record;
    }
}