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
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\output;

use core\output\template;
use mod_contentmarketplace\model\content_marketplace;
use totara_contentmarketplace\plugininfo\contentmarketplace;

/**
 * Mustache template to output the content marketplace as a single module
 * within the multi activities course.
 */
class content_marketplace_logo extends template {
    /**
     * @param content_marketplace $content_marketplace
     * @return content_marketplace_logo
     */
    public static function create_from_model(content_marketplace $content_marketplace): content_marketplace_logo {
        $plugin_info = (contentmarketplace::plugin($content_marketplace->learning_object_marketplace_component))
            ->contentmarketplace();

        return new static([
            'url' => $plugin_info->get_logo_url()->out(false),
            'alt' => $plugin_info->get_logo_alt_text(),
        ]);
    }
}