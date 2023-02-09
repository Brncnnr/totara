<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Chester <simon.chester@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\controllers;

use context_system;
use totara_mvc\admin_controller;
use totara_useraction\action\factory;
use totara_useraction\model\scheduled_rule;

/**
 * Base controller for the scheduled action admin pages.
 */
abstract class base_scheduled_action extends admin_controller {
    /**
     * @var string
     */
    protected $admin_external_page_name = 'totara_useraction_scheduled_actions';

    /**
     * @var scheduled_rule
     */
    protected $scheduled_rule;

    /**
     * @inheritDoc
     */
    protected function setup_context(): \context {
        return context_system::instance();
    }

    /**
     * @return array
     */
    protected function get_user_actions(): array {
        return factory::get_actions();
    }

    /**
     * @param string $param
     * @return scheduled_rule
     */
    protected function get_rule(string $param): scheduled_rule {
        if (!isset($this->scheduled_rule)) {
            try {
                $rule_id = $this->get_required_param($param, PARAM_INT);
                $this->scheduled_rule = scheduled_rule::load_by_id($rule_id);
            } catch (\Exception $ex) {
                throw new \moodle_exception('invalidaccess', '', '', null, $ex);
            }
        }
        return $this->scheduled_rule;
    }
}
