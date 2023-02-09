<?php
/*
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package block_totara_featured_links
*/

namespace block_totara_featured_links\form\validator;

defined('MOODLE_INTERNAL') || die();

use totara_form\element_validator;

/**
 * Class is_valid_url
 * Makes sure the value passed by the url input is http://, https:// or /
 * @package block_totara_featured_links
 */
class is_valid_url extends element_validator {

    /**
     * The URL needs to start with http://, https:// or /
     *
     * @return void adds errors to element
     */
    public function validate() {
        $url = $this->element->get_data()['url'];
        if (!empty($url)) {
            $url = purify_uri($url, false, false);
            // If $url string is empty after purify_uri() => URI is unsafe.
            if (empty($url)) {
                $this->element->add_error(get_string('urlvalidationerror', 'totara_form'));
            } else if (\core_text::substr($url, 0, 1) !== '/') {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $this->element->add_error(get_string('urlvalidationerror', 'totara_form'));
                }
            }
        }
    }
}