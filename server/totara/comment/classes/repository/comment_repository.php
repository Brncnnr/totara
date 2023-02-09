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
 * @package totara_comment
 */
namespace totara_comment\repository;

use totara_comment\comment as model;
use totara_comment\entity\comment;
use core\orm\entity\entity;
use core\orm\entity\repository;
use core\orm\query\builder;

/**
 * Repository for entity comment
 */
final class comment_repository extends repository {
    /**
     * @param comment|entity $entity
     * @return comment
     */
    public function save_entity(entity $entity): entity {
        global $USER;

        if (!$entity->exists()) {
            // Creating a new record, better to check for the userid property
            $userid = $entity->userid;

            if (empty($userid)) {
                debugging("Please set the property userid before saving the entity", DEBUG_DEVELOPER);
                $userid = $USER->id;
            }

            $entity->userid = $userid;
        }

        return parent::save_entity($entity);
    }

    /**
     * @param int $userid
     * @param bool $includereplies
     *
     * @return void
     */
    public function soft_delete_for_user(int $userid, bool $includereplies = true): void {
        $builder = builder::table(static::get_table());
        $builder->where('userid', $userid);

        if (!$includereplies) {
            $builder->where_null('parentid');
        }

        $attrs = new \stdClass();
        $attrs->content = null;
        $attrs->format = null;
        $attrs->timedeleted = time();
        $attrs->reasondeleted = model::REASON_DELETED_USER;

        $builder->update($attrs);
    }

    /**
     * Returning an array of all the comments from a user.
     *
     * @param int       $userid
     * @param int|null  $page
     * @return comment[]
     */
    public function get_comments_of_user(int $userid, int $page = null): array {
        $builder = builder::table(static::get_table());

        $builder->where_null('parentid');
        $builder->where("userid", $userid);
        $builder->map_to(comment::class);

        if (null != $page) {
            // Since there is no such component defined for getting all the comments of a user.
            // Therefore, we should use the default one.
            $paginator = $builder->paginate($page, model::ITEMS_PER_PAGE);
            return $paginator->get_items()->all();
        }

        return $builder->fetch();
    }

    /**
     * @param int       $user_id
     * @param int|null  $page
     *
     * @return comment[]
     */
    public function get_replies_of_user(int $user_id, int $page = null): array {
        $builder = builder::table(static::get_table());

        $builder->where_not_null('parentid');
        $builder->where('userid', $user_id);
        $builder->map_to(comment::class);

        if (null !== $page && 0 !== $page) {
            $paginator = $builder->paginate($page, model::ITEMS_PER_PAGE);
            return $paginator->get_items()->all();
        }

        return $builder->fetch();
    }

    /**
     * @param int $userid
     * @return int
     */
    public function count_comments_for_user(int $userid): int {
        $builder = builder::table(static::get_table());

        $builder->where('userid', $userid);
        $builder->where_null('parentid');

        return $builder->count();
    }

    /**
     * @param int $instance_id
     * @param string $component
     * @param string $area
     *
     * @return int
     */
    public function count_comment_for_instances(int $instance_id, string $component, string $area): int {
        $builder = builder::table(static::get_table());
        $builder->where('component', $component);
        $builder->where('instanceid', $instance_id);
        $builder->where('area', $area);
        $builder->where_null('parentid');

        return $builder->count();
    }

    /**
     * @param int $user_id
     * @return int
     */
    public function count_replies_for_user(int $user_id): int {
        $builder = builder::table(static::get_table());

        $builder->where('userid', $user_id);
        $builder->where_not_null('parentid');

        return $builder->count();
    }

    /**
     * @return int
     */
    public function count_all_non_deleted_comments(): int {
        return $this->where_null('timedeleted')->count();
    }
}