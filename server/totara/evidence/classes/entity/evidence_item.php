<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
use totara_plan\entity\plan_evidence_relation;

/**
 * Evidence item entity
 *
 * @property-read int $id ID
 * @property int $typeid Evidence type ID
 * @property int $user_id User ID for who the evidence is for
 * @property string $name Evidence item name
 * @property int $status Status
 * @property int $created_by User ID for who created the evidence
 * @property int $created_at Created timestamp
 * @property int $modified_by User ID for who last modified the evidence
 * @property int $modified_at Last modified timestamp
 * @property int $imported
 * @property-read evidence_type $type Evidence type entity
 * @property-read user $user User entity who the evidence is for
 * @property-read user $created_by_user Created by
 * @property-read user $modified_by_user
 * @property-read evidence_field_data[]|collection $data Custom field data
 * @property-read plan_evidence_relation[]|collection $plan_relation Learning plan relations this is linked to
 * @property-read models\evidence_item $model This item's model instance
 *
 * @method static evidence_item_repository repository()
 *
 * @package totara_evidence\entity
 */
class evidence_item extends entity {

    /**
     * @var string
     */
    public const TABLE = 'totara_evidence_item';

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
     * The user for this evidence.
     *
     * @return belongs_to
     */
    public function user(): belongs_to {
        return $this->belongs_to(user::class, 'user_id');
    }

    /**
     * The type this evidence is.
     *
     * @return belongs_to
     */
    public function type(): belongs_to {
        return $this->belongs_to(evidence_type::class, 'typeid');
    }

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
     * The field data for this evidence item
     *
     * @return has_many
     */
    public function data(): has_many {
        return $this->has_many(evidence_field_data::class, 'evidenceid')
            ->join([evidence_type_field::TABLE, 'field'], 'fieldid', 'id')
            ->order_by('field.typeid')
            ->order_by('field.sortorder');
    }

    /**
     * Learning plan relations that link this evidence to a learning plan.
     *
     * @return has_many
     */
    public function plan_relations(): has_many {
        return $this->has_many(plan_evidence_relation::class, 'evidenceid');
    }

    /**
     * Get a model instance of this item
     *
     * @return models\evidence_item
     */
    protected function get_model_attribute(): models\evidence_item {
        return models\evidence_item::load_by_entity($this);
    }

}
