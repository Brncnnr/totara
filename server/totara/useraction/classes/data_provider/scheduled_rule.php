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

namespace totara_useraction\data_provider;

use coding_exception;
use core\orm\entity\filter\filter_factory;
use core\orm\lazy_collection;
use totara_core\data_provider\provider;
use totara_useraction\entity\scheduled_rule as scheduled_rule_entity;
use totara_useraction\model\scheduled_rule as scheduled_rule_model;

/**
 * Data provider for scheduled rules.
 */
class scheduled_rule extends provider {
    /**
     * Valid sort fields
     */
    public const SORT_FIELDS = [
        'id' => 'id',
        'name' => 'name',
        'created' => 'created',
        'updated' => 'updated',
    ];

    /**
     * Create a new instance of the scheduled rule data provider.
     *
     * @param filter_factory|null $filter_factory
     * @return provider
     */
    public static function create(?filter_factory $filter_factory = null): provider {
        return new static(
            scheduled_rule_entity::repository(),
            self::SORT_FIELDS,
            $filter_factory
        );
    }

    /**
     * What field is used when a summary select is created. Required, but isn't used.
     *
     * @return string
     */
    public static function get_summary_format_select(): string {
        return 'id';
    }

    /**
     * The type of data this provider works with.
     *
     * @return string
     */
    public static function get_type(): string {
        return 'scheduled_rule';
    }

    /**
     * We override the core set_filters method as tenant_id can accept null as a valid value,
     * but the base function will filter it out.
     *
     * @param array $filters
     * @return scheduled_rule
     */
    public function set_filters(array $filters): self {
        if (!$this->filter_factory) {
            throw new coding_exception("No filter factory registered");
        }

        $new_filters = [];
        foreach ($filters as $key => $value) {
            $filter = $this->filter_factory->create($key, $value);
            if (!$filter) {
                throw new coding_exception("unknown filter: '$key'");
            }

            $new_filters[$key] = $filter;
        }

        $this->filters = $new_filters;
        return $this;
    }

    /**
     * Get all active scheduled user rules
     *
     * @return lazy_collection
     * @throws coding_exception
     */
    public static function get_all_active_rules(): lazy_collection {
        return scheduled_rule_entity::repository()
            ->where('status', true)
            ->get_lazy()
            ->map_to(function ($record) {
                $entity = new scheduled_rule_entity($record, false, true);

                return scheduled_rule_model::load_by_entity($entity);
            });
    }
}
