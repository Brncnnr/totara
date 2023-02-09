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
namespace mod_perform\dates\resolvers;

use coding_exception;
use mod_perform\dates\anniversary_date_calculator;

/**
 * A decorator for any date resolver which will apply anniversary logic to the start and end dates.
 * Note: due to needing a anniversary cut off date this class must be instantiated one per user track assignment.
 *
 * @package mod_perform\dates\resolvers
 */
class anniversary_of implements date_resolver {

    /**
     * @var date_resolver
     */
    protected $original;

    /**
     * @var int
     */
    protected $anniversary_cutoff_date;

    /**
     * @var anniversary_date_calculator
     */
    private $anniversary_date_calculator;

    /**
     * anniversary_of constructor.
     *
     * @param date_resolver $original The original date resolver which we want to run anniversary calculations on
     * @param int $anniversary_cutoff_date The cut off date to start applying anniversary logic,
     *                                     if the start date is after this date the original start and end are used.
     */
    public function __construct(
        date_resolver $original,
        int $anniversary_cutoff_date
    ) {
        if ($original instanceof self) {
            throw new coding_exception('anniversary_of can not accept an original date resolver of the type anniversary_of');
        }

        $this->original = $original;
        $this->anniversary_cutoff_date = $anniversary_cutoff_date;
        $this->anniversary_date_calculator = new anniversary_date_calculator();
    }

    /**
     * @inheritDoc
     */
    public function get_start(...$args): ?int {
        $original_start = $this->original->get_start(...$args);

        if ($original_start === null) {
            return null;
        }

        return $this->adjust_to_anniversary($original_start);
    }

    /**
     * @inheritDoc
     */
    public function get_end(...$args): ?int {
        $original_end = $this->original->get_end(...$args);

        if ($original_end === null) {
            return null;
        }

        $adjusted_start = $this->get_start(...$args);
        $original_start = $this->original->get_start(...$args);

        // We only push the end to the anniversary if we did so for the start.
        // Other wise we could end up with the start being later than end if
        // the reference date is close to assignment date and the adjusted
        // start and end fell just outside.
        //
        // For example:                        window start     cut off/now    window end
        // original dates (after adjustment)   [2018-01-01]<----[2020-01-01]----->[2020-01-02]
        //
        // would result in the following if we just called adjust_to_anniversary on both boundaries
        //                                      [2021-01-01]<----[2020-01-01]----->[2020-01-02]
        if ($adjusted_start === $original_start) {
            return $original_end;
        }

        return $this->adjust_to_anniversary($original_end);
    }

    protected function adjust_to_anniversary(int $date): int {
        return $this->anniversary_date_calculator->calculate($date, $this->anniversary_cutoff_date);
    }

    /**
     * @inheritDoc
     */
    public function get_resolver_base(): string {
        return $this->original->get_resolver_base();
    }

}
