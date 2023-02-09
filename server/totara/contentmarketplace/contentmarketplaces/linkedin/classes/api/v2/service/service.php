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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\api\v2\service;

use contentmarketplace_linkedin\api\response\result;
use moodle_url;
use totara_core\http\response;

/**
 * Service interface which represent for the API endpoint linkedin learning.
 * Note that each service implementation should represent for one endpoint and its query parameters only.
 */
interface service {
    /**
     * Returns the moodle url object that has all the filter applied (maybe) and the destination.
     *
     * @param moodle_url $endpoint_url
     * @return moodle_url
     */
    public function apply_to_url(moodle_url $endpoint_url): moodle_url;

    /**
     * @param response $response
     * @return result
     */
    public function wrap_response(response $response): result;
}