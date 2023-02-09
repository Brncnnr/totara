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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace totara_core\relationship;

/**
 * Class relationship_resolver_dto
 *
 * This class is responsible for the data transfer of relationship resolvers
 *
 * @package mod_perform\task\service
 */
class relationship_resolver_dto {

    /** @var string */
    private $source;
    /** @var int */
    private $user_id;
    /** @var array */
    private $meta = [];

    /**
     * relationship_resolver_dto constructor.
     *
     * @param int|null $user_id
     * @param string $source
     * @param mixed $meta
     */
    public function __construct($user_id, $source = relationship_resolver::SOURCE, $meta = null) {
        $this->source = $source;

        if (isset($user_id)) {
            $this->user_id = $user_id;
        }

        if (isset($meta)) {
            $this->meta = $meta;
        }
    }

    /**
     * Get user id
     *
     * @return int|null
     */
    public function get_user_id(): ?int {
        return $this->user_id;
    }

    /**
     * Get meta data
     *
     * @return array
     */
    public function get_meta(): ?array {
        return $this->meta;
    }

    /**
     * Get source of dto
     *
     * @return string
     */
    public function get_source(): string {
        return $this->source;
    }

    /**
     * Get user ids from an array of dtos
     *
     * @param relationship_resolver_dto[] $relationship_resolver_dtos
     * @return int[]
     */
    public static function get_user_ids($relationship_resolver_dtos): array {
        $user_ids = [];

        foreach ($relationship_resolver_dtos as $dto) {
            $user_ids[] = $dto->get_user_id();
        }

        return $user_ids;
    }
}
