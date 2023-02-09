<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use core_text;
use totara_useraction\action\factory;
use totara_useraction\exception\invalid_action_exception;
use totara_useraction\exception\missing_filter_duration_fields;
use totara_useraction\exception\missing_name_field_exception;
use totara_useraction\filter\applies_to;
use totara_useraction\filter\duration;
use totara_useraction\filter\factory as filter_factory;
use totara_useraction\filter\status;
use totara_useraction\model\scheduled_rule;
use totara_useraction\webapi\middleware\require_capability;

/**
 * Mutation to update a single scheduled rule.
 */
class update_scheduled_rule extends mutation_resolver {
    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            require_capability::from_id('input.id'),
        ];
    }

    /**
     * @param array $args
     * @param execution_context $ec
     * @return scheduled_rule
     */
    public static function resolve(array $args, execution_context $ec): scheduled_rule {
        // The middleware should've loaded it
        $scheduled_rule = $args['scheduled_rule_model'] ?? null;
        if (!$scheduled_rule instanceof scheduled_rule) {
            // This should never happen, but we're sanity checking against never not being never.
            throw new \coding_exception('Resolution failure with the scheduled rule update mutation.');
        }

        $input = $args['input'] ?? [];

        // At this point we've validated this rule exists & the user has access.
        $name = $input['name'] ?? null;
        $description = array_key_exists('description', $input) ? (string) $input['description'] : null;
        $status = array_key_exists('status', $input) ? (bool) $input['status'] : null;
        $action = array_key_exists('action', $input) ? $input['action'] : null;
        $filter_status = array_key_exists('filter_user_status', $input) ? $input['filter_user_status'] : null;
        $filter_duration = array_key_exists('filter_duration', $input) ? $input['filter_duration'] : null;
        $filter_applies_to = array_key_exists('filter_applies_to', $input) ? $input['filter_applies_to'] : false;

        // If name was updated, but was not provided, throw an exception.
        if ($name !== null && core_text::strlen($name) === 0) {
            throw new missing_name_field_exception();
        }

        if ($action !== null && !factory::is_valid($action)) {
            throw new invalid_action_exception();
        }

        if ($filter_status) {
            $filter_status = (filter_factory::create(status::class, $filter_status, true));
        }

        if ($filter_duration) {
            // Make sure we have all three fields posted
            $source = $filter_duration['source'] ?? null;
            $unit = $filter_duration['unit'] ?? null;
            $value = $filter_duration['value'] ?? null;

            if ($source === null || $unit === null || $value === null) {
                throw new missing_filter_duration_fields();
            }

            $filter_duration = filter_factory::create(duration::class, compact('source', 'unit', 'value'), true);
        }

        if ($filter_applies_to !== false) {
            $filter_applies_to = filter_factory::create(applies_to::class, $filter_applies_to, true);
        }

        return $scheduled_rule->update(
            compact('name', 'description', 'status', 'action', 'filter_status', 'filter_duration', 'filter_applies_to')
        );
    }
}
