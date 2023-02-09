<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @package totara_api
 */

namespace totara_api\model;

use core\orm\entity\model;
use totara_api\entity\global_rate_limit as entity;
use totara_api\traits\rate_limit_trait;

/**
 *
 * @property-read int $id
 * @property-read int $prev_window_value
 * @property-read int|null $current_window_reset_time
 * @property-read int $current_window_value
 * @property-read int|null $current_limit
 * @property-read int $time_created
 */
class global_rate_limit extends model {
    use rate_limit_trait;

    /**
     * @var entity
     */
    protected $entity;

    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'prev_window_value',
        'current_window_reset_time',
        'current_window_value',
        'current_limit',
        'time_created',
    ];

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return entity::class;
    }

    /**
     * @param int $prev_window_value
     * @param int $current_window_value
     * @param int|null $current_window_reset_time
     * @param int|null $current_limit
     * @return static
     */
    public static function instance(
        int $prev_window_value,
        int $current_window_value,
        int $current_window_reset_time = null,
        int $current_limit = null
    ): self {
        $model = self::create($prev_window_value, $current_window_value, $current_window_reset_time, $current_limit);

        if (is_null($current_limit)) {
            $model = $model->update(null, null, null, $model->calculate_current_limit());
        }
        return $model;
    }

    /**
     * @param int $prev_window_value
     * @param int $current_window_value
     * @param int|null $current_window_reset_time
     * @param int|null $current_limit
     *
     * @return static
     */
    public static function create(
        int $prev_window_value,
        int $current_window_value,
        int $current_window_reset_time = null,
        int $current_limit = null
    ): self {
        $entity = new entity();
        $entity->prev_window_value = $prev_window_value;
        $entity->current_window_value = $current_window_value;
        $entity->current_window_reset_time = $current_window_reset_time;
        $entity->current_limit = $current_limit;
        $entity->save();
        return new static($entity);
    }

    /**
     * @param int|null $prev_window_value
     * @param int|null $current_window_value
     * @param int|null $current_window_reset_time
     * @param int|null $current_limit
     *
     * @return $this
     */
    public function update(
        int $prev_window_value = null,
        int $current_window_value = null,
        int $current_window_reset_time = null,
        int $current_limit = null
    ): self {
        if (isset($prev_window_value)) {
            $this->entity->prev_window_value = $prev_window_value;
        }

        if (isset($current_window_value)) {
            $this->entity->current_window_value = $current_window_value;
        }

        // This parameter can be set to null explicitly.
        if (func_num_args() > 2) {
            $this->entity->current_window_reset_time = $current_window_reset_time;
        }

        // This parameter can be set to null explicitly.
        if (func_num_args() > 3) {
            $this->entity->current_limit = $current_limit;
        }

        $this->entity->save();
        $this->entity->refresh();

        return $this;
    }

    /**
     * @return void
     */
    public function delete(): void {
        $this->entity->delete();
    }

}