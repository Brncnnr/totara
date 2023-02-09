<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers\workflow\types;

use core\notification;
use mod_approval\model\workflow\workflow_type;

/**
 * Class toggle, activate/deactivate workflow type
 */
class toggle extends base {

    public const URL = '/mod/approval/workflow/types/toggle.php';

    /**
     * @inheritDoc
     */
    public function action() {
        $this->can_manage_workflows();
        $workflow_type = workflow_type::load_by_id($this->get_id_param());
        if ($workflow_type->active) {
            $workflow_type->deactivate(true);
        } else {
            $workflow_type->activate();
        }
        redirect(
            $this->get_report_url(),
            get_string('success:update_workflow_type', 'mod_approval'),
            null,
            notification::SUCCESS
        );
    }
}