<?php
/**
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\local\export;

use ml_recommender\local\csv\writer;
use stdClass;

/**
 * Using for exporting data.
 */
abstract class export {

    /**
     * @var stdClass Limit export to specific tenant
     */
    protected $tenant = null;

    /**
     * Get export name
     * Typically used for csv file naming
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Limit export to data relevant to specific tenant
     * @param stdClass|null $tenant
     * @return void
     */
    public function set_tenant(stdClass $tenant = null) {
        $this->tenant = $tenant;
    }

    /**
     * @param writer $writer
     * @return bool
     */
    abstract public function export(writer $writer): bool;

}
