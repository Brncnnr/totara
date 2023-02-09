<?php
/**
 * This file is part of Totara Engage
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace;

use container_workspace\entity\workspace_discussion;
use core\orm\query\builder;
use core\plugininfo\container;
use totara_core\advanced_feature;

final class plugininfo extends container {
    public function get_usage_for_registration_data() {
        $data = array();
        $data['numworkspaces'] = builder::table('workspace')->count();
        $data['numworkspacediscussions'] = workspace_discussion::repository()->count_all_non_deleted();
        $data['workspacesenabled'] = (int)advanced_feature::is_enabled('container_workspace');

        return $data;
    }
}