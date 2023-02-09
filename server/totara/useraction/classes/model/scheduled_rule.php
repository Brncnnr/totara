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

namespace totara_useraction\model;

use coding_exception;
use context;
use context_system;
use context_tenant;
use core\entity\tenant;
use core\orm\entity\model;
use core\orm\lazy_collection;
use core\orm\query\builder;
use totara_useraction\action\action_contract;
use totara_useraction\action\action_result;
use totara_useraction\action\factory as action_factory;
use totara_useraction\entity\scheduled_rule as entity;
use totara_useraction\entity\scheduled_rule_audience_map;
use totara_useraction\filter\factory;
use totara_useraction\filter\filter_contract;
use totara_useraction\filter\status as filter_status;
use totara_useraction\filter\duration as filter_duration;
use totara_useraction\filter\applies_to as filter_applies_to;
use totara_useraction\model\scheduled_rule\execution_data;
use totara_useraction\model\scheduled_rule_history as scheduled_rule_history;
use totara_useraction\entity\scheduled_rule_history as scheduled_rule_history_entity;

/**
 * Represents a scheduled_rule
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read int|null $tenant_id
 * @property-read int $status
 * @property-read int|null $created
 * @property-read int|null $updated
 * @property-read action_contract $action
 * @property-read filter_status $filter_user_status
 * @property-read filter_duration $filter_duration
 * @property-read filter_applies_to $filter_applies_to
 */
class scheduled_rule extends model {
    /**
     * @var entity
     */
    protected $entity;

    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'name',
        'description',
        'tenant_id',
        'status',
        'created',
        'updated',
    ];

    /**
     * @var string[]
     */
    protected $model_accessor_whitelist = [
        'action',
        'filter_user_status',
        'filter_duration',
        'filter_applies_to',
    ];

    /**
     * Create a new scheduled_rule instance
     *
     * @param string $name
     * @param string $action
     * @param filter_status|filter_contract $filter_status
     * @param filter_duration|filter_contract $filter_duration
     * @param filter_applies_to|filter_contract $filter_applies_to
     * @param string|null $description
     * @param int|null $tenant_id
     * @param bool $status
     *
     * @return static
     */
    public static function create(
        string $name,
        string $action,
        filter_status $filter_status,
        filter_duration $filter_duration,
        filter_applies_to $filter_applies_to,
        string $description = null,
        ?int $tenant_id = null,
        bool $status = false
    ): self {
        $entity = new entity();
        $entity->name = $name;
        $entity->description = $description ?? '';
        $entity->status = $status;
        $entity->tenant_id = $tenant_id;
        $entity->action = $action;
        $entity->filter_status = $filter_status->get_status();
        $entity->filter_duration_source = $filter_duration->get_source();
        $entity->filter_duration_unit = $filter_duration->get_unit();
        $entity->filter_duration_value = $filter_duration->get_value();
        $entity->filter_all_users = $filter_applies_to->is_all_users();

        builder::get_db()->transaction(function () use ($entity, $filter_applies_to) {
            $entity = $entity->save();

            foreach ($filter_applies_to->get_audiences() as $audience) {
                $mapping = new scheduled_rule_audience_map();
                $mapping->cohort_id = $audience->id;
                $mapping->scheduled_rule_id = $entity->id;
                $mapping->save();
            }
        });

        $entity = $entity->refresh();
        return new static($entity);
    }

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return entity::class;
    }

    /**
     * Delete the scheduled rule.
     *
     * @return void
     */
    public function delete(): void {
        $this->entity->delete();
    }

    /**
     * Fetch the relevant user action.
     *
     * @return action_contract
     */
    public function get_action(): action_contract {
        $identifier = $this->entity->action;
        return action_factory::create($identifier);
    }

    /**
     * Either return a tenant context or a system context.
     *
     * @return context
     */
    public function get_context(): context {
        $tenant = $this->get_tenant();
        if ($tenant) {
            return context_tenant::instance($tenant->id);
        }

        return context_system::instance();
    }

    /**
     * @return filter_status|filter_contract
     */
    public function get_filter_user_status(): filter_status {
        return factory::create(filter_status::class, $this->entity->filter_status);
    }

    /**
     * @return filter_duration|filter_contract
     */
    public function get_filter_duration(): filter_duration {
        return factory::create(filter_duration::class, [
            'source' => $this->entity->filter_duration_source,
            'unit' => $this->entity->filter_duration_unit,
            'value' => $this->entity->filter_duration_value,
        ]);
    }

    /**
     * @return filter_applies_to|filter_contract
     */
    public function get_filter_applies_to(): filter_applies_to {
        return factory::create(filter_applies_to::class, [
            'audiences' => $this->entity->filter_audiences,
            'all_users' => $this->entity->filter_all_users,
        ]);
    }

    /**
     * Get list of filters configured in this scheduled rule.
     *
     * @return filter_contract[]|array
     */
    private function get_filters(): array {
        return [
            $this->get_filter_applies_to(),
            $this->get_filter_duration(),
            $this->get_filter_user_status(),
        ];
    }

    /**
     * Execute the scheduled rule.
     *
     * @param execution_data $execution_data execution data.
     */
    public function execute(execution_data $execution_data): void {
        if (!$this->status) {
            throw new coding_exception("Scheduled rule is inactive");
        }

        // get users meeting the requirement.
        $users = $this->get_affected_users($execution_data);

        // apply action to each user.
        foreach ($users as $user) {
            builder::get_db()->transaction(function () use ($user, $execution_data) {
                $action_result = $this->action->execute($user);

                // record history of applying rule to user.
                $this->record_action($user->id, $action_result);
            });
        }
    }

    /**
     * The tenant entity associated with this scheduled rule.
     * If null then this rule is in the system context.
     *
     * @return tenant|null
     */
    public function get_tenant(): ?tenant {
        return $this->entity->tenant;
    }

    /**
     * Convert the model into a simple key=>value array.
     *
     * @return array
     */
    public function to_array(): array {
        return $this->entity->to_array();
    }

    /**
     * Update a specific scheduled rule. Properties that are provided are updated,
     * any that are skipped will be left alone.
     *
     * Nulls are treated as to-be-skipped.
     *
     * @param array $properties
     * @return $this
     */
    public function update(array $properties): self {
        builder::get_db()->transaction(function () use ($properties) {
            foreach (['name', 'description', 'action'] as $key) {
                $field = $properties[$key] ?? null;
                if (is_string($field)) {
                    $this->entity->{$key} = $field;
                }
            }

            $status = $properties['status'] ?? null;
            if (is_bool($status)) {
                $this->entity->status = $properties['status'];
            }

            $filter_status = $properties['filter_status'] ?? null;
            if ($filter_status instanceof filter_status) {
                $this->entity->filter_status = $properties['filter_status']->get_status();
            }

            $filter_duration = $properties['filter_duration'] ?? null;
            if ($filter_duration instanceof filter_duration) {
                $this->entity->filter_duration_source = $filter_duration->get_source();
                $this->entity->filter_duration_unit = $filter_duration->get_unit();
                $this->entity->filter_duration_value = $filter_duration->get_value();
            }

            $filter_applies_to = $properties['filter_applies_to'] ?? null;
            if ($filter_applies_to instanceof filter_applies_to) {
                if ($filter_applies_to->is_all_users()) {
                    $this->entity->filter_all_users = true;

                    scheduled_rule_audience_map::repository()
                        ->where('scheduled_rule_id', $this->entity->id)
                        ->delete();
                } else {
                    $this->entity->filter_all_users = false;

                    $existing_audiences = $this->entity->filter_audiences->pluck('id');
                    $posted_audiences = $filter_applies_to->get_audiences()->pluck('id');

                    $to_add = array_diff($posted_audiences, $existing_audiences);
                    $to_remove = array_diff($existing_audiences, $posted_audiences);

                    foreach ($to_add as $audience_id) {
                        $mapping = new scheduled_rule_audience_map();
                        $mapping->cohort_id = $audience_id;
                        $mapping->scheduled_rule_id = $this->entity->id;
                        $mapping->save();
                    }

                    if (!empty($to_remove)) {
                        scheduled_rule_audience_map::repository()
                            ->where('scheduled_rule_id', $this->entity->id)
                            ->where_in('cohort_id', $to_remove)
                            ->delete();
                    }
                }
            }
            $this->entity->save();
        });

        return self::load_by_id($this->entity->id);
    }

    /**
     * Creates an action execution history record
     *
     * @param int $user_id
     * @param action_result $action_result
     *
     * @return scheduled_rule_history
     */
    private function record_action(int $user_id, action_result $action_result): scheduled_rule_history {
        $entity = new scheduled_rule_history_entity();

        $entity->scheduled_rule_id = $this->id;
        $entity->user_id = $user_id;
        $entity->action = get_class($this->action);
        $entity->success = $action_result->is_success();
        $entity->message = $action_result->get_message();

        $entity = $entity->save();

        return new scheduled_rule_history($entity);
    }

    /**
     * Get the list of users that will be affected with a specific execution data.
     *
     * @param execution_data $execution_data
     * @return lazy_collection
     */
    private function get_affected_users(execution_data $execution_data): lazy_collection {
        $context = empty($this->tenant_id)
            ? context_system::instance()
            : context_tenant::instance($this->tenant_id);

        return (new user_search($context))
            ->apply_filters($this->get_filters(), $execution_data)
            ->get_all();
    }
}