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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\totara_catalog\dataformatter;

use context;
use stdClass;
use totara_catalog\dataformatter\formatter;
use totara_contentmarketplace\plugininfo\contentmarketplace;

/**
 * Formatter for content marketplace logo.
 */
class course_logo extends formatter {
    /**
     * course_logo constructor.
     * @param string $marketplace_component_field
     * @param string $cm_ids_field
     */
    public function __construct(string $marketplace_component_field, string $cm_ids_field) {
        $this->add_required_field('marketplace_component', $marketplace_component_field);
        $this->add_required_field('cm_ids', $cm_ids_field);
    }

    /**
     * @return array
     */
    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_IMAGE
        ];
    }

    /**
     * @param array    $data
     * @param context $context
     *
     * @return stdClass|null
     */
    public function get_formatted_value(array $data, context $context) {
        if (empty($data['marketplace_component']) || empty($data['cm_ids'])) {
            return null;
        }

        $marketplace_components = explode('|', $data['marketplace_component']);
        if (count($marketplace_components) > 1) {
            // In the case that there are multiple activities, then we just show the logo of the first activity's marketplace
            // by keying the marketplaces by course module ID and then sorting them to get the first marketplace component logo.
            $cm_ids = explode('|', $data['cm_ids']);
            $marketplace_components = array_combine($cm_ids, $marketplace_components);
            ksort($marketplace_components);
        }
        $marketplace_component = reset($marketplace_components);

        $marketplace = (contentmarketplace::plugin($marketplace_component))->contentmarketplace();
        return (object) [
            'url' => $marketplace->get_logo_url()->out(false),
            'alt' => $marketplace->get_logo_alt_text(),
        ];
    }

}