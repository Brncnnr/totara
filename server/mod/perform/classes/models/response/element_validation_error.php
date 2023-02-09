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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\response;

/**
 * Class element_plugin_validation_error
 *
 * @package mod_perform\models\activity
 */
class element_validation_error {

    /**
     * @var string
     */
    public $error_code;

    /**
     * @var string
     */
    public $error_message;

    /**
     * Element plugin constructor
     *
     * @param string $error_code
     * @param string $error_message
     */
    public function __construct(string $error_code, string $error_message) {
        $this->error_code = $error_code;
        $this->error_message = $error_message;
    }

}
