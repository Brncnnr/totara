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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package totara_program
 */

namespace totara_program\exception;

class unknown extends base {

    public function __construct(int $programid, $exceptionob = null) {
        parent::__construct($programid, $exceptionob);
        $this->exceptiontype = manager::EXCEPTIONTYPE_UNKNOWN;
    }

    public function handles(int $action): bool {
        switch ($action) {
            case manager::SELECTIONACTION_DISMISS_EXCEPTION:
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    public function handle(int $action = null) {
        if (!$this->handles($action)) {
            return true;
        }

        switch ($action) {
            default:
                return parent::handle($action);
                break;
        }
    }
}
