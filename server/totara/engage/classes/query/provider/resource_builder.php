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
 * @package totara_engage
 */

namespace totara_engage\query\provider;

use core\orm\query\builder;

final class resource_builder {

    /** @var builder */
    private $builder;

    /** @var string */
    private $key;

    /** @var string */
    private $correlation_id;

    /**
     * resource_search constructor.
     * @param builder $builder
     * @param string $key
     * @param string $correlation_id
     */
    public function __construct(builder $builder, string $key, string $correlation_id) {
        $this->builder = $builder;
        $this->key = $key;
        $this->correlation_id = $correlation_id;
    }

    /**
     * @return builder
     */
    public function get_builder(): builder {
        return $this->builder;
    }

    /**
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * @return string
     */
    public function get_correlation_id(): string {
        return $this->correlation_id;
    }
}