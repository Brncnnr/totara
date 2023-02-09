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

use contentmarketplace_linkedin\repository\classification_repository;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\has_many_through;

/**
 * Entity class represent for table "ttr_marketplace_linkedin_classification"
 *
 * @property int    $id
 * @property string $urn
 * @property string $locale_language
 * @property string $locale_country
 * @property string $name
 * @property string $type
 *
 * @property-read classification[]|collection $parents
 * @property-read classification[]|collection $children
 *
 * @method static classification_repository repository()
 */
class classification extends entity {
    /**
     * @var string
     */
    public const TABLE = 'marketplace_linkedin_classification';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return classification_repository::class;
    }

    /**
     * @return has_many_through
     */
    public function parents(): has_many_through {
        return $this->has_many_through(
            classification_relationship::class,
            self::class,
            'id',
            'child_id',
            'parent_id',
            'id'
        )
            ->order_by('name')
            ->order_by('id');
    }

    /**
     * @return has_many_through
     */
    public function children(): has_many_through {
        return $this->has_many_through(
            classification_relationship::class,
            self::class,
            'id',
            'parent_id',
            'child_id',
            'id'
        )
            ->order_by('name')
            ->order_by('id');
    }
}