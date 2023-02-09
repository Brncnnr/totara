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
namespace totara_comment\loader;

use core\orm\paginator;
use core\orm\query\builder;
use core\orm\query\order;
use totara_comment\comment;
use totara_comment\entity\comment as comment_entity;
use totara_comment\pagination\cursor;
use totara_comment\pagination\cursor_paginator;

/**
 * Communication layer between the application and the database.
 */
final class comment_loader {
    /**
     * comment_loader constructor.
     * Preventing this class from being constructed
     */
    private function __construct() {
    }

    /**
     * @return builder
     */
    private static function builder(): builder {
        $builder = builder::table(comment_entity::TABLE, 'tm');
        $builder->join(['user', 'u'], 'tm.userid', 'u.id');

        $builder->select(
            [
                // Trying to bring the commentid into first index of the record
                "tm.id AS comment_id",
                "u.id AS user_id",
                "u.email AS user_email",
                "u.picture AS user_picture",
                "u.imagealt AS user_imagealt",
                "tm.component AS comment_component",
                "tm.area AS comment_area",
                "tm.content AS comment_content",
                "tm.format AS comment_format",
                "tm.instanceid AS comment_instanceid",
                "tm.timecreated AS comment_timecreated",
                "tm.timemodified AS comment_timemodified",
                "tm.parentid AS comment_parentid",
                "tm.timedeleted AS comment_timedeleted",
                "tm.reasondeleted AS comment_reasondeleted"
            ]
        );

        $user_fields_sql = get_all_user_name_fields(true, 'u', false, 'user_');
        $builder->add_select_raw($user_fields_sql);

        $builder->results_as_arrays();
        return $builder;
    }

    /**
     * @param array $record
     * @return comment
     *
     * @internal
     */
    public static function build_comment(array $record): comment {
        $entity = new comment_entity();
        $entity->userid = $record['user_id'];

        $map = [
            'id' => 'comment_id',
            'component' => 'comment_component',
            'area' => 'comment_area',
            'content' => 'comment_content',
            'format' => 'comment_format',
            'instanceid' => 'comment_instanceid',
            'timecreated' => 'comment_timecreated',
            'timemodified' => 'comment_timemodified',
            'parentid' => 'comment_parentid',
            'timedeleted' => 'comment_timedeleted',
            'reasondeleted' => 'comment_reasondeleted'
        ];

        foreach ($map as $attribute => $property) {
            if (!array_key_exists($property, $record)) {
                debugging("No property '{$property}' was found for the record", DEBUG_DEVELOPER);
                continue;
            }

            $entity->set_attribute($attribute, $record[$property]);
        }

        // Build user record object.
        $user_fields = get_all_user_name_fields(false, 'u', 'user_');
        $user_fields['id'] = 'user_id';
        $user_fields['email'] = 'user_email';
        $user_fields['picture'] = 'user_picture';
        $user_fields['imagealt'] = 'user_imagealt';

        $user = [];
        foreach ($user_fields as $field => $sql_field) {
            if (!array_key_exists($sql_field, $record)) {
                debugging("The record does not have field '{$sql_field}'", DEBUG_DEVELOPER);
                continue;
            }

            $user[$field] = $record[$sql_field];
        }

        $user = (object) $user;
        $comment = comment::from_entity($entity, $user);

        if (isset($record['comment_total_replies'])) {
            $comment->set_total_replies($record['comment_total_replies']);
        }

        return $comment;
    }

    /**
     * @param int    $instanceid
     * @param string $component
     * @param string $area
     *
     * @return builder
     */
    private static function get_base_builder(int $instanceid, string $component, string $area): builder {
        $builder = static::builder();
        $builder->map_to([static::class, 'build_comment']);

        $builder->where('u.deleted', 0);
        $builder->where('tm.instanceid', $instanceid);
        $builder->where('tm.component', $component);
        $builder->where('tm.area', $area);

        // We have to loaded from the bottom to top. In order to include any latest comments in the result set.
        // Then at the webapi level, it has to be reverse to the asc.
        $builder->order_by('tm.timecreated', order::DIRECTION_DESC);

        // We need to sort by id as well, this might be a rare case, but sometimes there are like millions
        // of records had the same time_created field. Which means that sort by time_created field only
        // can cause the pagination going on forever.
        $builder->order_by('tm.id', order::DIRECTION_DESC);
        return $builder;
    }

    /**
     * This function is for fetching the comments.
     *
     * @param int    $instanceid
     * @param string $component
     * @param string $area
     * @param cursor $cursor
     *
     * @return cursor_paginator
     */
    public static function get_paginator(int $instanceid, string $component, string $area,
                                         cursor $cursor): cursor_paginator {
        $builder = static::get_base_builder($instanceid, $component, $area);
        $builder->where_null('tm.parentid');

        return new cursor_paginator($builder, $cursor);
    }

    /**
     * @param comment   $comment
     * @param int       $page
     * @param int|null  $perpage
     *
     * @return paginator
     */
    public static function get_replies(comment $comment, int $page = 1, ?int $perpage = null): paginator {
        $component = $comment->get_component();
        $instanceid = $comment->get_instanceid();

        if (null == $perpage) {
            $perpage = comment::ITEMS_PER_PAGE;
        }

        $builder = static::get_base_builder($instanceid, $component, $comment->get_area());
        $builder->where('tm.parentid', $comment->get_id());
        return $builder->paginate($page, $perpage);
    }

    /**
     * Fetching the first comment from bottom up.
     *
     * @param int $instance_id
     * @param string $component
     * @param string $area
     *
     * @return comment|null
     */
    public static function get_latest_comment(int $instance_id, string $component, string $area): ?comment {
        // By default, builder is being set to order by the field tm.timecreated
        $builder = static::get_base_builder($instance_id, $component, $area);
        $builder->where_null('tm.parentid');

        /** @var comment|null $comment */
        $comment = $builder->first();
        return $comment;
    }

    /**
     * @param int $instance_id
     * @param string $component
     * @param string $area
     *
     * @return int
     */
    public static function count_comments(int $instance_id, string $component, string $area): int {
        $repo = comment_entity::repository();
        return $repo->count_comment_for_instances($instance_id, $component, $area);
    }
}