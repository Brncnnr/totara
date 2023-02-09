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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\user;

use context_user;
use container_approval\approval as container_approval;
use core\entity\user;
use core\entity\user_repository;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\field;
use core\pagination\base_paginator;
use core\pagination\cursor;
use core\tenant_orm_helper;
use mod_approval\data_provider\provider;
use mod_approval\data_provider\cursor_paginator_trait;
use totara_core\access;

/**
 * Provider to search for selectable applicants within the default category.
 *
 * @package mod_approval\data_provider\user
 */
class selectable_applicants_for_category extends selectable_applicants_base {

    /**
     * @inheritDoc
     */
    protected function build_query(): repository {
        return $this->get_user_query($this->reference_context);
    }
}
