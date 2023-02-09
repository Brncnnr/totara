<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_useraction
 */
namespace totara_useraction\task;

use core\task\scheduled_task;
use totara_useraction\data_provider\scheduled_rule as scheduled_rule_data_provider;
use totara_useraction\model\scheduled_rule;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * Executes the active scheduled rules.
 */
class execute_active_scheduled_rules extends scheduled_task {

    /**
     * @inheritDoc
     */
    public function get_name() {
        return get_string('execute_active_scheduled_rules','totara_useraction');
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        /** @var scheduled_rule[] $active_rules*/
        $active_rules = scheduled_rule_data_provider::get_all_active_rules();
        $execution_data = execution_data::instance();

        foreach ($active_rules as $rule) {
            $rule->execute($execution_data);
        }
    }
}
