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
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\webapi\resolver\query;


use core\webapi\middleware\require_login_course_via_coursemodule;
use core\webapi\query_resolver;
use core\webapi\execution_context;
use mod_contentmarketplace\interactor\content_marketplace_interactor;
use mod_contentmarketplace\model\content_marketplace as content_marketplace_model;


/**
 * Resolver for content_marketplace
 */
abstract class content_marketplace extends query_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     * @return object
     */
    final public static function resolve(array $args, execution_context $ec): object {
        $cm = content_marketplace_model::from_course_module_id($args['cm_id']);

        (new content_marketplace_interactor($cm))->require_view();

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($cm->get_context());
        }

        return $cm;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login_course_via_coursemodule('cm_id')
        ];
    }
}