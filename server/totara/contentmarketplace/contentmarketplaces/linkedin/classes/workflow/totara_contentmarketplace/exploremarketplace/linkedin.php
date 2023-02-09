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
namespace contentmarketplace_linkedin\workflow\totara_contentmarketplace\exploremarketplace;

use contentmarketplace_linkedin\workflow\linkedin_workflow;

/**
 * linkedIn explore marketplace workflow implementation.
 */
class linkedin extends linkedin_workflow {
    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return get_string('explore_lil_marketplace', 'contentmarketplace_linkedin');
    }

    /**
     * @inheritDoc
     */
    public function get_description(): string {
        return get_string('explore_lil_marketplace_description', 'contentmarketplace_linkedin');
    }

}
