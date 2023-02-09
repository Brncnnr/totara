<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\totara_engage\link;

use moodle_url;
use totara_engage\link\destination_generator;

/**
 * Build the link to the catalog page
 *
 * @package totara_catalog\totara_engage\link
 */
final class catalog_destination extends destination_generator {
    /**
     * @return string
     */
    public function label(): string {
        return get_string('back_button', 'totara_catalog');
    }

    /**
     * @return moodle_url
     */
    protected function base_url(): moodle_url {
        $source_url = optional_param('source_url', null, PARAM_URL);

        if ($source_url) {
            return new moodle_url($source_url);
        }

        return new moodle_url('/totara/catalog/index.php');
    }

    /**
     * @return array|null
     */
    public function back_button_attributes(): ?array {
        $attributes = parent::back_button_attributes();
        $attributes['history'] = true;

        if (!empty($this->attributes)) {
            $url = new \moodle_url($attributes['url']);

            // Add the attributes to the url.
            foreach ($this->attributes as $k => $v) {
                if (is_array($v)) {
                    $v = implode(',', $v);
                }

                $url->param($k, $v);
            }
            $attributes['url'] = $url->out();
        }

        return $attributes;
    }
}