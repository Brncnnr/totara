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

namespace degeneration\items\criteria;


use degeneration\App;
use degeneration\Cache;
use degeneration\items\item;
use Exception;
use ReflectionClass;

/**
 * Class criterion
 *
 * @method \totara_criteria\criterion get_data(?string $property = null)
 *
 * @package degeneration\items\criteria
 */
abstract class criterion extends item {

    /**
     *  Check that all the the prerequisites for creating this criterion have been met
     *
     * @return $this
     */
    public function check_prerequisites() {
        return $this;
    }

    /**
     * Save a user
     *
     * @return bool
     */
    public function save(): bool {
        $this->check_prerequisites();

        $criterion = $this->generator()->{$this->get_method_name()}($this->evaluate_properties());

        $this->data = $criterion;

        Cache::get()->add($this);

        return true;
    }

    /**
     * Get criterion type
     *
     * @return string
     */
    public function get_type(): string {
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * Get create criterion method name
     *
     * @return string
     */
    protected function get_method_name(): string {
        $method = "create_{$this->get_type()}";

        if (!method_exists($this->generator(), $method)) {
            throw new Exception('Can not create a given criterion it might very well be that it\'s not supported - ' . $method);
        }

        return $method;
    }

    /**
     * Get competency generator
     *
     * @return \totara_criteria\testing\generator
     */
    public function generator() {
        return App::generator()->get_plugin_generator('totara_criteria');
    }

}
