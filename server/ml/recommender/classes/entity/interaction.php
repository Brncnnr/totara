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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\entity;

use core\orm\entity\entity;
use ml_recommender\repository\interaction_repository;

/**
 * @property int            $user_id
 * @property int            $item_id
 * @property int            $component_id
 * @property string|null    $area
 * @property int            $interaction_type_id
 * @property int            $rating
 * @property int            $time_created
 */
final class interaction extends entity {
    /**
     * @var string
     */
    public const TABLE = 'ml_recommender_interactions';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'time_created';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return interaction_repository::class;
    }
}