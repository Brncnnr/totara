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
 * @package totara_notification
 */
namespace totara_notification\loader;

use context_system;
use core\orm\query\builder;
use totara_core\extended_context;
use totara_notification\entity\notification_preference as entity;
use totara_notification\model\notification_preference as model;

class notification_preference_loader {
    /**
     * notification_preference_loader constructor.
     * Preventing this class from construction.
     */
    private function __construct() {
    }

    /**
     * Here are logics of the loading notification preferences for the event within a context.
     * + We would want all the notification preferences within a current context, however it would not include
     *   those notification preferences that are not set to overridden at this very context.
     * + Therefore, we would have to load all the notification preferences within the contexts path range.
     * + But we want to exclude those in between this very context and the top context (system context). This means
     *   that we want the ancestor's id to be unique and it should be ordering the context from bottom up (descendant).
     *
     * @param extended_context $extended_context
     * @param string|null      $resolver_class_name If the parameter is not provided, then we are assuming to load all
     *                                              of notification preferences..
     * @param bool             $at_context_only     The parameter will narrow down the list of notification preferences
     *                                              overridden or created at specific given context only.
     *
     * @return model[]
     */
    public static function get_notification_preferences(extended_context $extended_context,
                                                        ?string $resolver_class_name = null,
                                                        bool $at_context_only = false): array {
        $resolver_class_name = ltrim($resolver_class_name ?? '', '\\');

        // Get all the notifications at a specific contexts for the event first.
        $current_context_builder = builder::table(entity::TABLE, 'np');
        $current_context_builder->select('*');
        $current_context_builder->where('context_id', $extended_context->get_context_id());
        $current_context_builder->where('component', $extended_context->get_component());
        $current_context_builder->where('area', $extended_context->get_area());
        $current_context_builder->where('item_id', $extended_context->get_item_id());
        $current_context_builder->results_as_arrays();
        $current_context_builder->map_to([static::class, 'create_preference']);

        $current_context_builder->when(
            !empty($resolver_class_name),
            function (builder $inner_builder) use ($resolver_class_name): void {
                $inner_builder->where('resolver_class_name', $resolver_class_name);
            }
        );

        $current_context_preferences = $current_context_builder->fetch();

        // Now start fetch the preferences in the context path that does not have overridden at this lower context.
        // Which means that any preferences that does not share the same ancestor's id or any ancestor preferences.
        $context_ids = $extended_context->get_parent_context_ids();

        if (empty($context_ids) || $at_context_only) {
            // Either we are fetching the notification preferences at the given context ONLY.
            // Or we are at the top context level.
            return $current_context_preferences;
        }

        $upper_context_builder = builder::table(entity::TABLE, 'np');
        $upper_context_builder->select('*');
        $upper_context_builder->where_in('context_id', $context_ids);

        $upper_context_builder->when(
            !empty($resolver_class_name),
            function (builder $inner_builder) use ($resolver_class_name) {
                $inner_builder->where('resolver_class_name', $resolver_class_name);
            }
        );

        $sub_upper_ancestor = builder::table(entity::TABLE, 'bnp');
        $sub_upper_ancestor->select('bnp.ancestor_id AS id');
        $sub_upper_ancestor->where_not_null('bnp.ancestor_id');
        $sub_upper_ancestor->when(
            !empty($resolver_class_name),
            function (builder $inner_builder) use ($resolver_class_name) {
                $inner_builder->where('bnp.resolver_class_name', $resolver_class_name);
            }
        );

        $sub_upper_ancestor->where_in('bnp.context_id', $context_ids);
        $upper_context_builder->left_join([$sub_upper_ancestor, 'ancestor'], 'np.id', 'ancestor.id');

        $upper_context_builder->where(
            function (builder $inner_upper_context_builder) use ($current_context_preferences) {
                $inner_upper_context_builder->where_null('ancestor.id');

                if (!empty($current_context_preferences)) {
                    // Righty, so we are at the child level. Hence we can figure out the ancestor's id
                    // of the upper level.
                    $ancestor_ids = array_filter(
                        array_unique(
                            array_map(
                                function (model $preference): ?int {
                                    return $preference->get_ancestor_id();
                                },
                                $current_context_preferences
                            )
                        ),
                        function (?int $id): bool {
                            return !empty($id);
                        }
                    );

                    $inner_upper_context_builder->where_not_in('id', $ancestor_ids);
                    $inner_upper_context_builder->where('component', '=', extended_context::NATURAL_CONTEXT_COMPONENT);
                    $inner_upper_context_builder->where(
                        function (builder $inner_builder) use ($ancestor_ids): void {
                            $inner_builder->where_not_in('ancestor_id', $ancestor_ids);
                            // Or null ancestor's id
                            $inner_builder->or_where_null('ancestor_id');
                        }
                    );
                }
            }
        );

        $upper_context_builder->results_as_arrays();
        $upper_context_builder->map_to([static::class, 'create_preference']);

        // ===== The SQL from builder above =====
        //
        // SELECT "np".*
        // FROM phpunit_00notification_preference "np"
        // LEFT JOIN (
        //  SELECT bnp.ancestor_id as id
        //  FROM phpunit_00notification_preference "bnp"
        //  WHERE bnp.ancestor_id IS NOT NULL
        //  AND bnp.resolver_class_name = $1
        //  AND bnp.context_id IN ($2, $3)
        // ) "ancestor" ON np.id = ancestor.id
        // WHERE "np".context_id IN ($4, $5)
        //  AND "np".resolver_class_name = $6
        //  AND (ancestor.id IS NULL AND (np.id NOT IN ($8) OR np.ancestor_id NOT IN ($9)))
        //
        // ===== End of SQL =====

        $upper_context_preferences = $upper_context_builder->fetch();
        return array_merge($upper_context_preferences, $current_context_preferences);
    }

    /**
     * Note: Please do not call this function outside of this class. It is only
     * public because we want the query builder to access it.
     *
     * @param array $row
     * @return model
     *
     * @internal
     */
    public static function create_preference(array $row): model {
        $entity = new entity($row);
        return model::from_entity($entity);
    }

    /**
     * Find built in notification preference at given context. Default to context system
     * if it is not provided.
     *
     * @param string                $notification_class_name
     * @param extended_context|null $extended_context
     *
     * @return model|null
     */
    public static function get_built_in(string $notification_class_name, ?extended_context $extended_context = null): ?model {
        $extended_context = $extended_context ?? extended_context::make_with_context(context_system::instance());

        $repository = entity::repository();
        $entity = $repository->find_built_in($notification_class_name, $extended_context);

        if (null !== $entity) {
            return model::from_entity($entity);
        }

        return null;
    }
}