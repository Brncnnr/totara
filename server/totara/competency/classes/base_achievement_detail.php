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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;


/**
 * Class base_achievement_detail
 *
 * Pathway plugins should each implement this. It allows for adding and processing information that relates
 * to how a user would have achieved a value for a given pathway. The structure of that information
 * will often be specific to that particular pathway.
 */
abstract class base_achievement_detail {

    /**
     * @var array of data required to give further details about how a pathway value was achieved.
     *
     * It's precise format will depend on the particular pathway and what it needs.
     */
    protected $related_info = [];

    /**
     * @var null|int id of the scale value achieved or null if none achieved
     */
    protected $scale_value_id = null;

    /**
     * The timestamp when this got achieved
     * @var int|null
     */
    protected $achieved_at = null;

    /**
     * Get the array of information required that would in turn allow us to provide more
     * information about how a value was achieved.
     *
     * @return array
     */
    public function get_related_info(): array {
        return $this->related_info;
    }

    /**
     * Set the information required to give details on how a value was achieved.
     *
     * Information from the database might be set here. The implementation of get_achieved_via_strings() can be called
     * which would then process this information into strings that can be displayed.
     *
     * @param array $related_info
     * @return $this
     */
    public function set_related_info(array $related_info): self {
        $this->related_info = $related_info;
        return $this;
    }

    /**
     * @return int|null The id of the value achieved or null if no value was achieved.
     */
    public function get_scale_value_id(): ?int {
        return $this->scale_value_id;
    }

    /**
     * @param int|null $scale_value_id
     * @return $this
     */
    public function set_scale_value_id(?int $scale_value_id): self {
        $this->scale_value_id = $scale_value_id;
        return $this;
    }

    /**
     * @return int|null The timestamp when this got achieved
     */
    public function get_achieved_at(): ?int {
        return $this->achieved_at;
    }

    /**
     * @param int|null $achieved_at
     * @return $this
     */
    public function set_achieved_at(?int $achieved_at): self {
        $this->achieved_at = $achieved_at;
        return $this;
    }

    /**
     * Returns an array of strings that concern how this value was achieved.
     *
     * In the case of activity log, these will be filtered for unique values, concatenated and inserted
     * into a string that says 'Criteria met: x, y, z.'
     *
     * @return array of strings relating to how a user achieved a value
     */
    abstract public function get_achieved_via_strings(): array;

    /**
     * Override in subclasses if this type of achievement detail can have a comment attached, e.g. manual rating.
     *
     * @return string
     */
    public function get_comment(): string {
        return '';
    }
}