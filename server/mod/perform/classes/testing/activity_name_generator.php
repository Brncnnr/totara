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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\testing;


/**
 * A generator to create random activity names
 */
class activity_name_generator {

    public $period = [
        'Daily', 'Weekly', 'Bi-weekly', 'Monthly', 'Half-year', 'End-of-year', 'Yearly', 'Quarterly'
    ];

    public $middle = [
        'employee', 'satisfaction', 'employee satisfaction', 'performance', 'manager performance', 'happiness', '360-degree',
    ];

    public $type = [
        'check-in', 'appraisal', 'feedback'
    ];

    public function generate(): array {
        $period = $this->period[array_rand($this->period)];
        $middle = $this->middle[array_rand($this->middle)];
        $type = $this->type[array_rand($this->type)];

        $name = sprintf('%s %s %s', $period, $middle, $type);
        return [$name, $type];
    }

    public function generate_multiple(int $amount): array {
        $names = [];
        for ($i = 0; $i < $amount; $i++) {
            $names[] = $this->generate();
        }
        return $names;
    }
}