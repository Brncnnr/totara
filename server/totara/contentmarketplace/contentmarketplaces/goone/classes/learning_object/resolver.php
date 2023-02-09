<?php
/**
 * This file is part of Totara Learn
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone\learning_object;

use contentmarketplace_goone\entity\learning_object as learning_object_entity;
use contentmarketplace_goone\model\learning_object;
use core\orm\entity\entity as core_entity;
use totara_contentmarketplace\learning_object\abstraction\metadata\model;
use totara_contentmarketplace\learning_object\abstraction\resolver as base;

class resolver extends base {

    /**
     * @return string|learning_object_entity
     */
    public static function get_entity_class(): string {
        return learning_object_entity::class;
    }

    /**
     * @return string
     */
    public static function get_external_id_field(): string {
        return 'external_id';
    }

    /**
     * @param core_entity $entity
     * @return model
     */
    protected static function load_model_from_entity(core_entity $entity): model {
        return learning_object::load_by_entity($entity);
    }

}
