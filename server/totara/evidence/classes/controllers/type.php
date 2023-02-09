<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\controllers;

use context;
use context_system;
use totara_core\advanced_feature;
use totara_evidence\models\evidence_type;
use totara_mvc\admin_controller;

abstract class type extends admin_controller {

    protected $admin_external_page_name = 'manage_evidence_types';

    protected function setup_context(): context {
        return context_system::instance();
    }

    protected function authorize(): void {
        parent::authorize();

        advanced_feature::require('evidence');
        evidence_type::can_manage(true);
    }

}
