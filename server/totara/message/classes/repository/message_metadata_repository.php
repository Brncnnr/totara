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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_message
 */
namespace totara_message\repository;

use core\orm\entity\repository;
use totara_message\entity\message_metadata;

/**
 * Repository class for table "ttr_message_metadata"
 */
class message_metadata_repository extends repository {
    /**
     * @param int $notification_id
     * @param int $processor_id
     * @param bool $strict
     *
     * @return message_metadata|null
     */
    public function find_message_metadata_from_notification_id(int $notification_id, int $processor_id,
                                                               bool $strict = false): ?message_metadata {
        $builder = $this->get_builder();

        $builder->where('processorid', $processor_id);
        $builder->where('notificationid', $notification_id);

        /** @var message_metadata|null $entity */
        $entity = $builder->one($strict);
        return $entity;
    }
}