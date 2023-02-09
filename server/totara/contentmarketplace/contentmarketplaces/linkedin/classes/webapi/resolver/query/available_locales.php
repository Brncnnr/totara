<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\webapi\resolver\query;

use contentmarketplace_linkedin\data_provider\locales;
use contentmarketplace_linkedin\dto\locale;
use core\webapi\execution_context;
use core\webapi\middleware;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_contentmarketplace\interactor\catalog_import_interactor;
use totara_contentmarketplace\webapi\middleware\require_content_marketplace;

/**
 * Query resolvers for GraphQL query "contentmarketplace_linkedin_available_locales".
 * The query will fetch all the available locales from the stored learning objects.
 */
class available_locales extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return locale[]
     */
    public static function resolve(array $args, execution_context $ec): array {
        (new catalog_import_interactor())->require_view_catalog_import_page();
        $provider = new locales();
        return $provider->get();
    }

    /**
     * @return middleware[]
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_content_marketplace("contentmarketplace_linkedin")
        ];
    }
}