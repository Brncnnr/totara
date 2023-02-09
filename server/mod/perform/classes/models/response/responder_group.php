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

namespace mod_perform\models\response;

use core\collection;

/**
 * Class responder_group
 *
 * Represents a group of responses to a particular element for a particular relationship type.
 * For example a responder group could hold all the responses for question one for all managers of a subject.
 *
 * @package mod_perform\models\response
 */
class responder_group {

    /**
     * @var string
     */
    protected $relationship_name;

    /**
     * @var collection
     */
    protected $responses;

    /**
     * @var int|null
     */
    protected $sort_order;

    /**
     * other_responder_group constructor.
     *
     * @param string $relationship_name
     * @param collection|section_element_response[] $responses
     * @param int|null $sort_order
     */
    public function __construct(string $relationship_name, collection $responses, ?int $sort_order = null) {
        $this->relationship_name = $relationship_name;
        $this->responses = $responses;
        $this->sort_order = $sort_order;
    }

    /**
     * Create a group for anonymous responses
     *
     * @return static
     */
    public static function create_anonymous_group(): self {
        // Technically this name will be overwritten by the front end, this is just for safety.
        $anonymous_name = get_string('anonymous_group_relationship_name', 'mod_perform');
        return new responder_group($anonymous_name, new collection());
    }

    /**
     * @return string
     */
    public function get_relationship_name(): string {
        return $this->relationship_name;
    }

    /**
     * @return collection|section_element_response
     */
    public function get_responses(): collection {
        return $this->responses;
    }

    /**
     * Add another response.
     *
     * @param section_element_response $response
     * @return $this
     */
    public function append_response(section_element_response $response): self {
        $this->responses->append($response);

        return $this;
    }

    /**
     * Add many responses.
     *
     * @param section_element_response[] $responses
     * @return $this
     */
    public function append_responses(iterable $responses): self {
        foreach ($responses as $response) {
            $this->responses->append($response);
        }

        return $this;
    }

    /**
     * Get the sort order. Note it may not be specified.
     *
     * @return int|null
     */
    public function get_sort_order(): ?int {
        return $this->sort_order;
    }

}