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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\hook;

class userdata_normalise_label extends \totara_core\hook\base {

    /**
     * @var array
     */
    private $grouplabels;

    /**
     * Update GDPR totara export/purge userdata form label.
     *
     * userdata_normalise_label constructor.
     *
     * @param array $grouplabels
     */
    public function __construct(array $grouplabels) {
        $this->grouplabels = $grouplabels;
    }

    /**
     * @return array
     */
    public function get_grouplabels(): array {
        return $this->grouplabels;
    }

    /**
     * @param $grouplabels
     * @return $this
     */
    public function set_grouplabels($grouplabels): self {
        $this->grouplabels = $grouplabels;

        return $this;
    }
}