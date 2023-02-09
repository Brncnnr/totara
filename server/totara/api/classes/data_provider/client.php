<?php
/**
 * This file is part of Totara Core
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\data_provider;

use coding_exception;
use core\orm\entity\filter\filter_factory;
use totara_api\entity\client as entity;
use totara_core\data_provider\provider;

class client extends provider {

    public const DEFAULT_PAGE_SIZE = 1000;

    // Mapping of sort field display names to physical entity _columns_.
    public const SORT_FIELDS = [
        'name' => 'name',
        'time_created' => 'time_created'
    ];

    /**
     * @inheritDoc
     */
    protected function get_default_sort_by(): ?string {
        return 'time_created';
    }

    /**
     * @inheritDoc
     */
    public static function create(?filter_factory $filter_factory = null): provider {
        return new static(
            entity::repository(),
            self::SORT_FIELDS,
            $filter_factory
        );
    }

    /**
     * @inheritDoc
     */
    public static function get_type(): string {
        return 'client';
    }

    /**
     * @inheritDoc
     */
    public static function get_summary_format_select() {
        return 'summaryformat';
    }

    /**
     * Override set_filters to accept null values
     *
     * @param array $filters
     * @return provider
     */
    public function set_filters(array $filters): provider {
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
}