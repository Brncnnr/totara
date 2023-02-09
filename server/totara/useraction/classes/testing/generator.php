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

namespace totara_useraction\testing;

use core\entity\cohort;
use core\testing\component_generator;
use totara_useraction\action\delete_user;
use totara_useraction\action\factory;
use totara_useraction\entity\scheduled_rule as scheduled_rule_entity;
use totara_useraction\entity\scheduled_rule_history as scheduled_rule_history_entity;
use totara_useraction\filter\applies_to;
use totara_useraction\filter\duration;
use totara_useraction\filter\status;
use totara_useraction\fixtures\mock_action;
use totara_useraction\model\scheduled_rule;
use totara_useraction\model\scheduled_rule_history;

class generator extends component_generator {
    /**
     * @param array $params
     * @return scheduled_rule_history
     */
    public function create_history_entry(array $params = []): scheduled_rule_history {
        global $USER;

        $rule_id = $params['rule_id'] ?? null;
        $user_id = $params['user_id'] ?? $USER->id;
        $success = $params['success'] ?? true;
        $action = $params['action'] ?? delete_user::class;
        $message = $params['message'] ?? null;
        $created = $params['created'] ?? null;

        if (!$rule_id) {
            $rule = $this->create_scheduled_rule();
            $rule_id = $rule->id;
        }

        $entity = new scheduled_rule_history_entity();
        $entity->scheduled_rule_id = $rule_id;
        $entity->user_id = $user_id;
        $entity->success = $success;
        $entity->action = $action;
        $entity->message = $message;

        if ($created) {
            $entity->created = $created;
        }

        $entity->save();

        return scheduled_rule_history::load_by_entity($entity);
    }

    /**
     * @param array $parameters
     * @return scheduled_rule_history
     */
    public function create_history_entry_from_params(array $parameters = []): scheduled_rule_history {
        global $USER;
        $params = [
            'user_id' => $parameters['userid'] ?? $USER->id,
            'message' => $parameters['message'] ?? null,
        ];

        $rule_name = $parameters['rule'] ?? null;
        if ($rule_name) {
            $params['rule_id'] = scheduled_rule_entity::repository()->where('name', $rule_name)->one()->id;
        }

        $action_name = $parameters['action_name'] ?? null;
        if ($action_name) {
            $actions = array_flip(factory::get_actions());
            $params['action'] = $actions[$action_name] ?? null;
        }

        $success = $parameters['success'] ?? true;
        $params['success'] = $this->boolval($success);

        $created = $parameters['created'] ?? null;
        if ($created) {
            $created = new \DateTime($created);
            $params['created'] = $created->format('U');
        }

        return $this->create_history_entry($params);
    }

    /**
     * Create a scheduled rule.
     *
     * @param array $parameters
     * @return scheduled_rule
     */
    public function create_scheduled_rule(array $parameters = []): scheduled_rule {
        $tenant_id = $parameters['tenant_id'] ?? null;
        $name = $parameters['name'] ?? 'Rule #' . random_string(5);
        $description = $parameters['description'] ?? '';
        $status = $parameters['status'] ?? false;
        $action = $parameters['action'] ?? delete_user::class;

        // Filters
        $filter_status = $parameters['filter_status'] ?? null;
        $filter_duration = $parameters['filter_duration'] ?? null;
        $filter_applies_to = $parameters['filter_applies_to'] ?? null;

        if (!$filter_status instanceof status) {
            if (is_bool($filter_status) || is_int($filter_status)) {
                $filter_status = status::create_from_stored($filter_status);
            } else {
                $filter_status = status::create_from_stored(status::STATUS_SUSPENDED);
            }
        }

        if (!$filter_duration instanceof duration) {
            if (!is_array($filter_duration)) {
                $filter_duration = [];
            }
            $source = $filter_duration['source'] ?? duration::SOURCE_SUSPENDED;
            $unit = $filter_duration['unit'] ?? duration::UNIT_DAYS;
            $days = $filter_duration['value'] ?? 5;

            // Note - we transform days to seconds
            $value = duration::unit_to_seconds($days, $unit);
            $filter_duration = duration::create_from_stored(compact('source', 'unit', 'value'));
        }

        if (!$filter_applies_to instanceof applies_to) {
            $filter_applies_to = applies_to::create_from_input(['audiences' => $filter_applies_to]);
        }

        return scheduled_rule::create(
            $name,
            $action,
            $filter_status,
            $filter_duration,
            $filter_applies_to,
            $description,
            $tenant_id,
            $status
        );
    }

    /**
     * Create a scheduled rule from behat, using the ENUM props available to GraphQL.
     * For "applies_to" it takes a comma-delimited list of audience id_numbers, or the key ALL_USERS.
     *
     * @param array $parameters
     * @return scheduled_rule
     */
    public function create_scheduled_rule_from_params(array $parameters = []): scheduled_rule {
        if (!empty($parameters['user_status'])) {
            $parameters['filter_status'] = status::create_from_input($parameters['user_status']);
            unset($parameters['user_status']);
        }

        // Unit, Value, Source
        $value = $parameters['duration_value'] ?? null;
        $unit = $parameters['duration_unit'] ?? null;
        $source = $parameters['data_source'] ?? null;

        $status = $parameters['status'] ?? false;
        $parameters['status'] = $this->boolval($status);

        if ($unit !== null) {
            $unit = duration::enum_to_stored($unit);
        }
        if ($source !== null) {
            $source = duration::enum_to_stored($source);
        }

        $parameters['filter_duration'] = compact('source', 'unit', 'value');

        // Use either ALL_USERS, or the audience id_number field
        $applies_to = $parameters['applies_to'] ?? null;
        $parameters['filter_applies_to'] = null;
        if ($applies_to && $applies_to !== 'ALL_USERS') {
            $audience_id_numbers = array_map('trim', explode(',', $applies_to));
            $parameters['filter_applies_to'] = cohort::repository()
                ->where_in('idnumber', $audience_id_numbers)
                ->get()
                ->pluck('id');
        }

        return $this->create_scheduled_rule($parameters);
    }

    /**
     * Helper method for unit tests to provide the minimal config needed to create a scheduled rule.
     *
     * @param array $parameters
     * @return array
     */
    public function get_minimal_scheduled_rule_parameters(array $parameters = []): array {
        $rule = [
            'name' => 'min',
            'action' => mock_action::class,
            'filter_user_status' => status::ENUM_SUSPENDED,
            'filter_duration' => [
                'source' => duration::ENUM_SUSPENDED,
                'unit' => duration::ENUM_UNIT_DAYS,
                'value' => 10,
            ],
            'filter_applies_to' => [
                'audiences' => null,
            ],
        ];

        return array_merge($rule, $parameters);
    }

    /**
     * @param $value
     * @return bool
     */
    private function boolval($value): bool {
        if ($value === 'true' || $value === 'yes') {
            return true;
        }
        if ($value === 'false' || $value === 'no') {
            return false;
        }

        return boolval($value);
    }
}
