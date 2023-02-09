<?php
/**
 * This file is part of Totara TXP
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\controllers\client;

use moodle_url;
use totara_core\advanced_feature;
use totara_mvc\tui_view;

class add_client extends base_clients {

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        advanced_feature::require('api');
        $this->set_url(new moodle_url('/totara/api/client/add.php'));

        $title = get_string('add_client', 'totara_api');
        $this->get_page()->navbar->add($title);

        return tui_view::create('totara_api/pages/AddClient', $this->get_tui_props());
    }
}