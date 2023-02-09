<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\data_providers;

use core\collection;
use totara_competency\models\assignment;
use totara_competency\models\profile\competency_progress as competency_progress_model;

/**
 * Class competency_progress
 *
 * @package totara_competency\data_providers
 * @property-read collection|competency_progress_model[] items
 */
class competency_progress extends user_data_provider {

    /**
     * @var assignments
     */
    protected $assignments;

    /**
     * Order of items to load
     *
     * @var null
     */
    protected $order = null;

    /**
     * Set column to order by
     *
     * @param string|null $order
     * @return $this
     */
    public function set_order(string $order = null) {
        $this->order = $order;

        return $this;
    }

    /**
     * Fetch competency progress data from the database
     *
     * @return competency_progress
     */
    public function fetch() {
        if (!$this->assignments) {
            $this->assignments = assignments::for($this->get_user());
        }

        $this->set_filters($this->filters);

        $this->assignments->fetch();
        $this->fetched = true;
        $this->items = competency_progress_model::build_from_assignments($this->assignments->get());

        return $this->filter()->order();
    }

    /**
     * Sort competencies in a specified order
     *
     * @return $this
     */
    protected function order() {
        switch ($this->order) {
            case 'recently-assigned':
                $this->items->sort(function ($a, $b) {
                    return $this->get_latest_assignment_by_field($b->assignments, 'assigned_at') <=>
                        $this->get_latest_assignment_by_field($a->assignments, 'assigned_at');
                });
                break;

            case 'recently-archived':
                $this->items->sort(function ($a, $b) {
                    return $this->get_latest_assignment_by_field($b->assignments, 'archived_at') <=>
                        $this->get_latest_assignment_by_field($a->assignments, 'archived_at');
                });
                break;

            case 'alphabetical':
                $this->items->sort(function ($a, $b) {
                    return $a->competency->fullname <=> $b->competency->fullname;
                });
                break;

            case null:
                break;

            default:
                throw new \moodle_exception("Can not order by " . $this->order);
        }

        return $this;
    }

    /**
     * Select latest given field from a collection of assignment models
     *
     * This is required to get for example last assigned or last archived value
     * which is later used to sort assignments
     *
     * @param collection $assignments Collection of assignment models
     * @param string $field Field to select
     * @return mixed
     */
    protected function get_latest_assignment_by_field(collection $assignments, string $field) {
        return $assignments->reduce(function (int $max_date, assignment $assignment) use ($field) {
            if ($assignment->get_field($field) > $max_date) {
                $max_date = $assignment->get_field($field);
            }

            return $max_date;
        }, 0);
    }

    /**
     * Apply filters set with set_filters method
     *
     * @return $this
     */
    protected function filter() {
        // We filter only filters applicable to the progress, as the rest of the filters
        // are proxied to the assignments data provider
        $filters = array_filter($this->filters, function ($key) {
            return in_array($key, ['proficient']);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($filters as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if (method_exists($this, $method = 'filter_by_' . $key)) {
                $this->$method($value);
            }
        }

        return $this;
    }

    /**
     * Filter items by competencies with proficient \ not proficient values
     *
     * @param bool $value Proficient flag
     */
    protected function filter_by_proficient(bool $value): void {
        $this->items = $this->items->filter(function (competency_progress_model $item) use ($value): bool {
            return $item->is_proficient() === $value;
        });
    }

    /**
     * Set filters
     *
     * @param array $filters
     * @return $this
     */
    public function set_filters(array $filters) {
        parent::set_filters($filters);

        // This filters are proxied to the assignments data provider
        $ass_progress = array_filter($filters, function ($key) {
            return in_array($key, ['status', 'type', 'user_group_type', 'user_group_id', 'search', 'competency_id']);
        }, ARRAY_FILTER_USE_KEY);

        if ($this->assignments) {
            $this->assignments->set_filters($ass_progress);
        }

        return $this;
    }

}
