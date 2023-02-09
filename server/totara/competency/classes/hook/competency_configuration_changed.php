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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\hook;

use totara_core\hook\base;

class competency_configuration_changed extends base {

    /** @var int */
    protected $competency_id;
    /**
     * @var string|null
     */
    protected $change_type;

    /**
     * @param int $competency_id ;
     * @param string|null $change_type
     */
    public function __construct(int $competency_id, ?string $change_type = null) {
        $this->competency_id = $competency_id;
        $this->change_type = $change_type;
    }

    /**
     * @return int
     */
    public function get_competency_id(): int {
        return $this->competency_id;
    }

    /**
     * @return string|null
     */
    public function get_change_type(): ?string {
        return $this->change_type;
    }

}
