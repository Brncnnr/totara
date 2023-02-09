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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\entity;

use core\orm\entity\entity;
use ml_recommender\repository\interaction_type_repository;

/**
 * @property string $interaction
 */
final class interaction_type extends entity {
    /**
     * @var string
     */
    public const TABLE = 'ml_recommender_interaction_types';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return interaction_type_repository::class;
    }
}