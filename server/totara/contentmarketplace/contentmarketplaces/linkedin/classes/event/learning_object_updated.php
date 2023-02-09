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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\event;

use core\orm\entity\entity;
use totara_contentmarketplace\event\base_learning_object_updated;
use totara_contentmarketplace\learning_object\abstraction\metadata\detailed_model;
use contentmarketplace_linkedin\entity\learning_object as learning_object_entity;

final class learning_object_updated extends base_learning_object_updated {
    /**
     * @inheritDoc
     */
    protected function init() {
        parent::init();
        $this->data['objecttable'] = 'marketplace_linkedin_learning_object';
    }

    /**
     * @param detailed_model $learning_object
     * @param entity $entity
     * @return base_learning_object_updated
     */
    public static function from_learning_object(detailed_model $learning_object, entity $entity): base_learning_object_updated {
        $event = parent::from_learning_object($learning_object, $entity);

        $event->data['other']['description'] = $learning_object->description_include_html;
        return $event;
    }

    /**
     * @inheritDoc
     */
    protected function get_extra_data_prefix_key(): string {
        return 'old_';
    }

    /**
     * @inheritDoc
     */
    protected static function get_extra_name_value(entity $entity): ?string {
        if ($entity instanceof learning_object_entity) {
            return $entity->title;
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    protected static function get_extra_image_value(entity $entity): ?string {
        if ($entity instanceof learning_object_entity) {
            return $entity->primary_image_url;
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    protected static function get_extra_description_value(entity $entity): ?string {
        if ($entity instanceof learning_object_entity) {
            return $entity->description_include_html;
        }
        return null;
    }
}