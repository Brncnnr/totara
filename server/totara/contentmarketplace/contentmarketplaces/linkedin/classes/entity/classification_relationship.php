<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\entity;

use contentmarketplace_linkedin\repository\classification_relationship_repository;
use core\orm\entity\entity;

/**
 * Entity class for a record of table "ttr_marketplace_linkedin_classification_relationship"
 *
 * @property int $id
 * @property int $parent_id
 * @property int $child_id
 *
 * @method static classification_relationship_repository repository()
 */
class classification_relationship extends entity {
    /**
     * @var string
     */
    public const TABLE = 'marketplace_linkedin_classification_relationship';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return classification_relationship_repository::class;
    }
}