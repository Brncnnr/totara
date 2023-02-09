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
namespace mod_contentmarketplace\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_contentmarketplace\interactor\content_marketplace_interactor as interactor;

class content_marketplace_interactor extends type_resolver {
    /**
     * @param string $field
     * @param interactor $content_marketplace_interactor
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $content_marketplace_interactor, array $args, execution_context $ec) {
        if (!($content_marketplace_interactor instanceof interactor)) {
            throw new coding_exception('Expected content marketplace interactor');
        }

        switch ($field) {
            case 'has_view_capability':
                return $content_marketplace_interactor->has_view_capability();
            case 'is_site_guest':
                return $content_marketplace_interactor->is_site_guest();
            case 'can_enrol':
                return $content_marketplace_interactor->can_enrol();
            case 'can_launch':
                return $content_marketplace_interactor->can_launch();
            case "is_enrolled":
                return $content_marketplace_interactor->is_enrolled();
            case 'non_interactive_enrol_instance_enabled':
                return $content_marketplace_interactor->non_interactive_enrol_instance_enabled();
            case 'supports_non_interactive_enrol':
                return $content_marketplace_interactor->supports_non_interactive_enrol();

            default:
                throw new coding_exception("Unexpected field passed {$field}");
        }
    }

}