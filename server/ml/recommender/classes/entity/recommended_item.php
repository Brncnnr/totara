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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\entity;

use core\orm\entity\entity;

/**
 * @property string $unique_id
 * @property int $target_item_id
 * @property string $target_component
 * @property string|null $target_area
 * @property int $item_id
 * @property string $component
 * @property string|null $area
 * @property int $time_created
 * @property float $score
 */
final class recommended_item extends entity {
    /**
     * @var string
     */
    public const TABLE = 'ml_recommender_items';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'time_created';
}