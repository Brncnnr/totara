<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_xapi
 */
namespace totara_xapi\response;

use totara_xapi\response\facade\result;
use JsonSerializable;
use stdClass;

/**
 * A wrapper for the response from processing the xAPI statement.
 */
class json_result implements result, JsonSerializable {
    /**
     * The wrapped data that was
     * @var array|stdClass
     */
    private $data;

    /**
     * @param array|stdClass $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * @return array|stdClass
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return $this->data;
    }

    /**
     * @return array|stdClass
     */
    public function get_data() {
        return $this->data;
    }
}