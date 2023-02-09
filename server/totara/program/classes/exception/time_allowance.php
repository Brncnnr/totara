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

class time_allowance extends base {

    public function __construct(int $programid, $exceptionob = null) {
        parent::__construct($programid, $exceptionob);
        $this->exceptiontype = manager::EXCEPTIONTYPE_TIME_ALLOWANCE;
    }

    public function handles(int $action): bool {
        return in_array($action, [manager::SELECTIONACTION_OVERRIDE_EXCEPTION,
                                  manager::SELECTIONACTION_AUTO_TIME_ALLOWANCE,
                                  manager::SELECTIONACTION_DISMISS_EXCEPTION]);
    }

    public function handle(int $action = null) {
        if (!$this->handles($action)) {
            return true;
        }

        switch ($action) {
            case manager::SELECTIONACTION_AUTO_TIME_ALLOWANCE:
                return $this->set_auto_time_allowance();
                break;
            case manager::SELECTIONACTION_OVERRIDE_EXCEPTION:
                return $this->override_and_add_program();
                break;
            default:
                return parent::handle($action);
                break;
        }
    }
}
