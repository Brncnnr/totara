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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria;

use stdClass;

/**
 * Base class related with display of criteria information
 */
abstract class criterion_display {

    /** @var criterion $criterion */
    protected $criterion;

    /**
     * Constructor.
     *
     * @param criterion $criterion
     */
    final public function __construct(criterion $criterion) {
        $this->criterion = $criterion;
    }

    /**
     * Get a summary of the criterion configuration for display purposes
     *
     * @return stdClass
     */
    public function get_configuration() {
        $result = new stdClass();
        $result->item_type = $this->get_display_items_type();
        $result->item_aggregation = $this->get_display_aggregation();
        if (!$this->criterion->is_valid()) {
            $result->error = get_string('error_invalid_configuration', 'totara_criteria');
        }

        $result->items = $this->get_display_configuration_items();

        return $result;
    }

    /**
     * Return the display type of items associated with the criterion
     * TODO: make protected when all UI is on vueJs
     *
     * @return string
     */
    abstract public function get_display_items_type(): string;

    /**
     * Return a summary of the aggregation of criterion items for display
     *
     * @return string
     */
    protected function get_display_aggregation(): string {
        if ($this->criterion->get_aggregation_method() === criterion::AGGREGATE_ALL) {
            return get_string('complete_all', 'totara_criteria');
        } else {
            $agg_params = $this->criterion->get_aggregation_params();
            return get_string('aggregate_any',
                'totara_criteria',
                (object)['x' => $agg_params['req_items'] ?? 1]
            );
        }
    }

    /**
     * Return a summarized view of the criterion items for display
     *
     * @return string[]
     */
    abstract protected function get_display_configuration_items(): array;

}
