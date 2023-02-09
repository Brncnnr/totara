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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_reaction
 */
namespace totara_reaction\userdata;

use totara_reaction\repository\reaction_repository;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_reaction\entity\reaction as reaction_entity;
use totara_reaction\reaction as model_reaction;

/**
 * User data implementation for reaction record.
 */
final class reaction extends item {
    /**
     * @param int $user_status
     * @return bool
     */
    public static function is_purgeable(int $user_status): bool {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_exportable(): bool {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function count(target_user $user, \context $context): int {
        /** @var reaction_repository $repository */
        $repository = reaction_entity::repository();
        return $repository->count_for_user($user->id);
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function purge(target_user $user, \context $context): int {
        /** @var reaction_repository $repository */
        $repository = reaction_entity::repository();
        $entities = $repository->get_for_user($user->id);

        foreach ($entities as $entity) {
            $reaction = model_reaction::from_entity($entity);
            $reaction->delete();
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    protected static function export(target_user $user, \context $context): export {
        /** @var reaction_repository $repository */
        $repository = reaction_entity::repository();

        $export = new export();
        $entities = $repository->get_for_user($user->id);

        foreach ($entities as $entity) {
            $export->data[] = [
                'id' => $entity->id,
                'component' => $entity->component,
                'area' => $entity->area,
                'time_created' => $entity->timecreated,
                'instance_id' => $entity->instanceid,
                'contextid' => $entity->contextid
            ];
        }

        return $export;
    }

    /**
     * @return array
     */
    public static function get_fullname_string(): array {
        return ['user_data_item_reaction', 'totara_reaction'];
    }
}