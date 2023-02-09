<?php

use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use performelement_aggregation\calculations\average;
use PHPUnit\Framework\Constraint\Constraint;

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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

class is_saved_aggregate_average_response extends Constraint {

    /**
     * @var participant_instance_entity
     */
    protected $participant_instance;

    /**
     * @var section_element_entity
     */
    protected $aggregation_section_element;

    /**
     * @var float
     */
    private $actual;

    public function __construct(participant_instance_entity $participant_instance, section_element_entity $aggregation_section_element) {
        $this->participant_instance = $participant_instance;
        $this->aggregation_section_element = $aggregation_section_element;
    }

    protected function matches($other): bool
    {
        /** @var element_response $subject_aggregated_response */
        $subject_aggregated_response = element_response::repository()
            ->where('participant_instance_id', $this->participant_instance->id)
            ->where('section_element_id', $this->aggregation_section_element->id)
            ->one(false);

        if ($other === null) {
            return $subject_aggregated_response === null;
        }

        // attempting to prevent platform-dependent failures.
        // https://www.php.net/manual/en/language.types.float.php#:~:text=The%20size%20of%20a%20float,the%2064%20bit%20IEEE%20format).
        $other = round($other, 4);

        $decoded_response = json_decode($subject_aggregated_response->response_data, true, 512, JSON_THROW_ON_ERROR);
        $this->actual = round($decoded_response[average::get_name()], 4);

        return $other === $this->actual;
    }

    public function toString(): string {
        return 'matches expected saved average ' . $this->actual;
    }
}