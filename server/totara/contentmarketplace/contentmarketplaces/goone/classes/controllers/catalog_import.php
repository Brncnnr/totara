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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone\controllers;

use totara_contentmarketplace\controllers\catalog_import as base_catalog_import;
use totara_contentmarketplace\views\override_catalog_import_nav_breadcrumbs;
use totara_mvc\view;

final class catalog_import extends base_catalog_import {
    /**
     * @inheritDoc
     */
    public function action(): view {
        $explorer = $this->get_explorer();
        return $this->create_view('totara_contentmarketplace/explorer', (array)$explorer->get_data())
            ->set_title($explorer->get_heading())
            ->add_override(new override_catalog_import_nav_breadcrumbs($this));
    }

}