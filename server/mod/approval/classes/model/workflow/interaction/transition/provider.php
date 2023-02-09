<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\model\workflow\interaction\transition;

use core_component;
use mod_approval\exception\model_exception;
use mod_approval\model\workflow\workflow_stage;

/**
 * Defined workflow stage moving options.
 *
 */
class provider {

    /**
     * Transition resolvers.
     *
     * @var transition_base[]
     */
    private static $transition_resolvers;

    /**
     * Get all transition classes
     *
     * @return array
     */
    private static function get_classnames(): array {
        return core_component::get_namespace_classes(
            'model\workflow\interaction\transition',
            transition_base::class,
            'mod_approval'
        );
    }

    /**
     * Function return existing transition resolvers
     *
     * @param workflow_stage $stage
     * @return array|transition_base[]
     */
    private static function get_transition_resolvers(workflow_stage $stage): array {
        if (is_null(self::$transition_resolvers)) {
            $resolvers = self::get_classnames();
            $maps = [];
            foreach ($resolvers as $class) {
                $maps[] = new $class($stage->id);
            }

            $transition_resolvers = [];

            foreach ($maps as $resolver) {
                $transition_resolvers[$resolver::get_sort_order()] = $resolver;
            }
            ksort($transition_resolvers);
            self::$transition_resolvers = $transition_resolvers;
        }
        return self::$transition_resolvers;
    }

    /**
     * Get the transition class which matches a particular enum.
     *
     * @param string $enum
     * @return string
     */
    public static function get_class_by_enum(string $enum): string {
        // Note that for now the enum is the class name.
        $class_name = 'mod_approval\model\workflow\interaction\transition\\' . strtolower($enum);
        $all_classnames = self::get_classnames();
        foreach ($all_classnames as $transition_class) {
            if ($transition_class === $class_name) {
                return $transition_class;
            }
        }
        throw new model_exception('Unknown transition code');
    }

    /**
     * Gets a transition resolver from a transition_field value.
     *
     * @param string $transition_field
     * @param workflow_stage $stage
     * @return transition_base
     */
    public static function get_transition_by_field(string $transition_field, workflow_stage $stage): transition_base {
        if (is_numeric($transition_field)) {
            $transition_stage = workflow_stage::load_by_id((int)$transition_field);
            if ($transition_stage->workflow_version_id != $stage->workflow_version_id) {
                throw new model_exception('Tried to instantiate stage transition to a stage that is not in the same workflow_version');
            }
            return new stage($transition_stage->id);
        } else {
            // Convert from enum, and instantiate.
            $class_name = self::get_class_by_enum($transition_field);
            return new $class_name($stage);
        }
    }

    /**
     * Returns transitions which could be used with a given stage.
     *
     * @param workflow_stage $stage
     * @return array
     */
    public static function get_resolver_options_for_stage(workflow_stage $stage): array {
        $resolvers = self::get_transition_resolvers($stage);
        $resolver_options = [];

        foreach ($resolvers as $resolver) {
            $options = $resolver->get_options($stage);

            array_push($resolver_options, ...$options);
        }
        return $resolver_options;
    }
}