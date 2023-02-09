<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone\workflow\core_course\coursecreate;

use totara_contentmarketplace\explorer;
use totara_contentmarketplace\workflow\marketplace_workflow;

class contentmarketplace extends marketplace_workflow {

    public function get_name(): string {
        return get_string('addcoursego1', 'contentmarketplace_goone');
    }

    public function get_description(): string {
        return get_string('addcoursego1_description', 'contentmarketplace_goone');
    }

    public function can_access(): bool {
        // Content marketplaces are enabled.
        if (!parent::can_access()) {
            return false;
        }

        // Allowed to add content from marketplaces.
        $params = $this->manager->get_params();
        $category = $params['category'] ?? get_config('core', 'defaultrequestcategory');
        $context = empty($category) ? \context_system::instance() : \context_coursecat::instance($category);
        return has_capability('totara/contentmarketplace:add', $context);
    }

    public function get_workflow_url(): \moodle_url {
        $url = parent::get_workflow_url();
        $url->param('mode', explorer::MODE_CREATE_COURSE);
        return $url;
    }

}
