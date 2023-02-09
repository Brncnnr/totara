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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\data_providers\activity;

use core\collection;
use core\orm\entity\repository;
use mod_perform\data_providers\provider;
use mod_perform\entity\activity\activity_type as activity_type_entity;
use mod_perform\models\activity\activity_type as activity_type_model;

/**
 * Handles sets of performance activity types.
 *
 * @method collection|activity_type_model[] get
 */
class activity_type extends provider {

    /**
     * @inheritDoc
     */
    protected function build_query(bool $include_relations = true): repository {
        return activity_type_entity::repository()
            ->order_by('id');
    }

    /**
     * Map the activity type entities to their respective model class.
     *
     * @return collection|activity_type_model[]
     */
    protected function process_fetched_items(): collection {
        return $this->items->map_to(activity_type_model::class);
    }

}
