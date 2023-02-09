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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\api\v2\service\learning_asset\response;

use contentmarketplace_linkedin\api\response\collection as base_collection;
use contentmarketplace_linkedin\core_json\structure\learning_asset_collection;
use stdClass;

class collection extends base_collection {
    /**
     * @return string
     */
    protected static function get_structure_name(): string {
        return learning_asset_collection::class;
    }

    /**
     * @return element[]
     */
    public function get_elements(): array {
        $elements = $this->json_data->elements;

        return array_map(
            function (stdClass $element_data): element {
                return element::create($element_data, true);
            },
            $elements,
        );
    }
}