<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\api\v2\service\learning_asset\query;

use moodle_url;

abstract class query {
    /**
     * A hash map of all other parameters.
     * Note that this is explicitly using the parameter names from linkedin API.
     *
     * @var array
     */
    protected $scalar_parameters;

    /**
     * query constructor.
     */
    public function __construct() {
        $this->scalar_parameters = [];
    }

    /**
     * Apply all the filters to the new url that got built from base_url.
     *
     * @param moodle_url $base_url
     * @return moodle_url
     */
    public function build_url(moodle_url $base_url): moodle_url {
        $clone_url = new moodle_url($base_url);
        $clone_url->remove_params();

        $this->apply_to_url($clone_url);
        return $clone_url;
    }

    /**
     * Mutate the given url with different sort of parameters.
     *
     * @param moodle_url $url
     * @return void
     */
    abstract protected function apply_to_url(moodle_url $url): void;

    /**
     * @param bool|null $value
     * @return void
     */
    abstract public function set_include_retired(?bool $value): void;

    /**
     * @param int|null $value
     * @return void
     */
    abstract public function set_expand_depth(?int $value): void;

    /**
     * @param string $key
     * @return void
     */
    protected function remove_scalar_parameter(string $key): void {
        if (array_key_exists($key, $this->scalar_parameters)) {
            unset($this->scalar_parameters[$key]);
        }
    }

    /**
     * @param string     $key
     * @param mixed|null $value
     * @param bool       $remove_on_null
     */
    protected function set_scalar_parameter(string $key, $value, bool $remove_on_null = true): void {
        if (is_null($value) && $remove_on_null) {
            $this->remove_scalar_parameter($key);
            return;
        }

        $this->scalar_parameters[$key] = $value;
    }

    /**
     * @return void
     */
    public function clear(): void {
        $this->scalar_parameters = [];
    }

    /**
     * @param string $paging_href
     * @return void
     */
    abstract public function set_parameters_from_paging_url(string $paging_href): void;
}