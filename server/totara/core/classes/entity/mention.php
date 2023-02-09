<?php
/**
 * This file is part of Totara LMS
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
 * @package totara_core
 */
namespace totara_core\entity;

use core\orm\entity\entity;
use totara_core\repository\mention_repository;

/**
 * Class mention
 * @package totara_core\entity
 *
 * @property int    $userid
 * @property int    $instanceid
 * @property string $component
 * @property string $area
 */
final class mention extends entity {
    /**
     * @var string
     */
    public const TABLE = 'totara_core_mention';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return mention_repository::class;
    }
}