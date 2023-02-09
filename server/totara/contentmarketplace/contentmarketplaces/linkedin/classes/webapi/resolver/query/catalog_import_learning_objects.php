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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\webapi\resolver\query;

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\data_provider\learning_objects;
use contentmarketplace_linkedin\data_provider\learning_objects_selected_filters;
use totara_contentmarketplace\interactor\catalog_import_interactor;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_contentmarketplace\webapi\middleware\require_content_marketplace;

class catalog_import_learning_objects extends query_resolver {

    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        (new catalog_import_interactor())->require_view_catalog_import_page();

        $input_params = $args['input'];

        $provider = new learning_objects();
        $provider_filters = $input_params['filters'];
        $provider_filters['availability'] = constants::AVAILABILITY_AVAILABLE; // Hard-coded to only return availability (i.e. active) learning.

        // The list of ids are provided, then it is most likely about fetching the list of selelcted
        // learning object items. Therefore we need to ignore all the other filters.
        if (!empty($provider_filters["ids"])) {
            // Ignore all the other filters. AKA remove them.
            $provider_filters = [
                "ids" => $provider_filters["ids"]
            ];
        }

        $provider->add_filters($provider_filters);
        $provider->sort_by($input_params['sort_by']);
        $result = $provider->get_offset_page($input_params['pagination']);

        $selected_filter_labels = (new learning_objects_selected_filters($input_params['filters']))->get();
        $result['selected_filters'] = $selected_filter_labels;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_content_marketplace('linkedin'),
        ];
    }

}
