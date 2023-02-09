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
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\workflow;

use totara_contentmarketplace\interactor\catalog_import_interactor;
use context_coursecat;
use totara_contentmarketplace\workflow\marketplace_workflow;

abstract class linkedin_workflow extends marketplace_workflow {

    /**
     * @return bool
     */
    public function can_access(): bool {
        if (!parent::can_access()) {
            return false;
        }

        $params = $this->manager->get_params();

        $interactor = new catalog_import_interactor();
        if (empty($params['category'])) {
            return $interactor->can_add_course();
        }

        return $interactor->can_add_course_to_category(context_coursecat::instance($params['category']));
    }

}
