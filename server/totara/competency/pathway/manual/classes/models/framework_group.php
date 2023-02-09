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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_manual\models;

use core\orm\collection;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\scale_value;

/**
 * Class framework_group
 *
 * This is necessary for grouping competencies that can be manually rated
 * into sections based upon their respective framework.
 *
 * @package pathway_manual\models
 *
 * @property-read scale_value[] $values
 * @property-read rateable_competency[] $competencies
 */
class framework_group {

    /**
     * @var competency_framework
     */
    protected $framework;

    /**
     * @var rateable_competency[]
     */
    protected $rateable_competencies;

    protected function __construct(competency_framework $framework) {
        $this->framework = $framework;
        $this->rateable_competencies = [];
    }

    /**
     * Get the actual framework entity.
     *
     * @return competency_framework
     */
    public function get_framework(): competency_framework {
        return $this->framework;
    }

    /**
     * Get the rateable competencies.
     *
     * @return rateable_competency[]
     */
    public function get_competencies(): array {
        return $this->rateable_competencies;
    }

    /**
     * Get the scale values for the scale associated with this framework.
     *
     * @return scale_value[]|collection
     */
    public function get_values(): collection {
        return $this->framework->scale->sorted_values_high_to_low;
    }

    /**
     * Build framework groups by grouping a list of competencies by the framework they have.
     *
     * @param rateable_competency[] $competencies
     * @return framework_group[]
     */
    public static function build_from_competencies(array $competencies): array {
        $frameworks = [];

        foreach ($competencies as $competency) {
            $framework = $competency->get_entity()->framework;

            if (!isset($frameworks[$framework->id])) {
                $frameworks[$framework->id] = new static($framework);
            }

            $frameworks[$framework->id]->rateable_competencies[] = $competency;
        }

        $frameworks = array_values($frameworks);

        usort($frameworks, function (self $a, self $b) {
            return $a->framework->sortorder <=> $b->framework->sortorder;
        });

        return $frameworks;
    }

    /**
     * @param $attribute
     * @return mixed
     */
    public function __get($attribute) {
        return $this->{'get_' . $attribute}();
    }

    /**
     * @param $attribute
     * @return bool
     */
    public function __isset($attribute) {
        return method_exists($this, 'get_' . $attribute);
    }

}
