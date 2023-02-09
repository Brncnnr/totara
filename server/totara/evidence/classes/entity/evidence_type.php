<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\entity;

use core\entity\user;
use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_many;
use totara_evidence\models;

/**
 * Evidence type entity
 *
 * @property-read int $id ID
 * @property string $name Evidence type name
 * @property string $idnumber Evidence type ID number
 * @property string $description Evidence type name
 * @property int $descriptionformat Description format
 * @property int $created_by User creator
 * @property int $created_at Created timestamp
 * @property int $modified_by User last modified
 * @property int $modified_at Last modified timestamp
 * @property int $location Location of type
 * @property int $status Status
 * @property-read user $created_by_user
 * @property-read user $modified_by_user
 * @property-read evidence_item[]|collection $items Evidence of this type
 * @property-read evidence_type_field[]|collection $fields Custom fields
 * @property-read models\evidence_type $model This type's model instance
 *
 * @method static evidence_type_repository repository()
 *
 * @package totara_evidence\entity
 */
class evidence_type extends entity {

    /**
     * @var string
     */
    public const TABLE = 'totara_evidence_type';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'created_at';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'modified_at';

    /**
     * @var bool
     */
    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * The user who created this.
     *
     * @return belongs_to
     */
    public function created_by_user(): belongs_to {
        return $this->belongs_to(user::class, 'created_by');
    }

    /**
     * The user who last modified this.
     *
     * @return belongs_to
     */
    public function modified_by_user(): belongs_to {
        return $this->belongs_to(user::class, 'modified_by');
    }

    /**
     * The fields that are children of this type
     *
     * @return has_many
     */
    public function fields(): has_many {
        return $this->has_many(evidence_type_field::class, 'typeid')
            ->order_by('sortorder');
    }

    /**
     * The evidence items that are of this type
     *
     * @return has_many
     */
    public function items(): has_many {
        return $this->has_many(evidence_item::class, 'typeid')
            ->order_by('id');
    }

    /**
     * Get a model instance of this type
     *
     * @return models\evidence_type
     */
    protected function get_model_attribute(): models\evidence_type {
        return models\evidence_type::load_by_entity($this);
    }

}
