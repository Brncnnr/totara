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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\repository;

use core\orm\entity\repository;
use totara_core\extended_context;

class notification_event_log_repository extends repository {
    /**
     * @param extended_context $extended_context
     * @return $this
     */
    public function filter_by_extended_context(extended_context $extended_context): notification_event_log_repository {
        $this->where('context_id', $extended_context->get_context_id());
        $this->where('component', $extended_context->get_component());
        $this->where('area', $extended_context->get_area());
        $this->where('item_id', $extended_context->get_item_id());

        return $this;
    }
}