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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */
namespace mod_perform\dates\resolvers\dynamic;

use core\collection;
use core\orm\query\builder;
use mod_perform\dates\constants;

class user_creation_date extends base_dynamic_date_resolver {

    public const DEFAULT_KEY = 'default';

    /**
     * @inheritDoc
     */
    protected function resolve(): void {
        $this->date_map = builder::create()
            ->select(['id', 'timecreated'])
            ->from('user')
            ->where('id', $this->bulk_fetch_keys)
            ->get()
            ->map(function ($row) {
                // Admins have a 0 created date, and will be excluded.
                if ((int)$row->timecreated === 0) {
                    return null;
                }

                // Using map (rather than pluck) to preserve keys.
                return $row->timecreated;
            })->all(true);
    }

    /**
     * @inheritDoc
     */
    public function get_options(): collection {
        return new collection(
            [$this->make_option(static::DEFAULT_KEY)]
        );
    }

    /**
     * @inheritDoc
     */
    public function option_is_available(string $option_key): bool {
        return in_array($option_key, $this->get_supported_option_keys(), true);
    }

    protected function get_supported_option_keys(): array {
        return [static::DEFAULT_KEY];
    }

    protected function get_option_display_name(?string $option_key) {
        return get_string('user_creation_date', 'mod_perform');
    }

    protected function make_option(string $option_key): dynamic_source {
        return new dynamic_source(
            $this,
            $option_key,
            $this->get_option_display_name($option_key)
        );
    }

    /**
     * @inheritDoc
     */
    public function get_resolver_base(): string {
        return constants::DATE_RESOLVER_USER_BASED;
    }

}
