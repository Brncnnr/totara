<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\dates\resolvers\dynamic;

use core\collection;
use core\orm\query\builder;
use mod_perform\dates\constants;

class user_custom_field extends base_dynamic_date_resolver {

    protected $time_custom_fields = null;
    /**
     * @inheritDoc
     */
    protected function resolve(): void {
        $this->date_map = builder::create()
            ->select(['uid.userid', 'uid.data'])
            ->from('user_info_data', 'uid')
            ->join(['user_info_field', 'uif'], 'fieldid', 'id')
            ->where('uif.shortname', $this->option_key)
            ->where('uid.userid', $this->bulk_fetch_keys)
            ->get()
            ->map(function ($row) {
                // Using map (rather than pluck) to preserve keys.
                return $row->data;
            })->all(true);
    }

    /**
     * @return collection
     * @throws \coding_exception
     */
    public function get_options(): collection {
        return $this->get_time_profile_fields()->map(function ($item) {
            return new dynamic_source(
                $this,
                $item->shortname,
                $item->name
            );
        });
    }

    /**
     * @return collection
     */
    private function get_time_profile_fields(): collection {
        if (!$this->time_custom_fields) {
            $this->time_custom_fields = builder::create()
                ->select(['shortname', 'name'])
                ->from('user_info_field')
                ->where('datatype', 'datetime')
                ->get();
        }
        return $this->time_custom_fields;
    }

    /**
     * @param string $option_key
     *
     * @return bool
     */
    public function option_is_available(string $option_key): bool {
        return in_array(
            $option_key,
            $this->get_time_profile_fields()->pluck('shortname'),
            true
        );
    }

    /**
     * @inheritDoc
     */
    public function get_resolver_base(): string {
        return constants::DATE_RESOLVER_USER_BASED;
    }

}
