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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\link;

use moodle_url;

/**
 * A class to find the final destination for an url
 *
 * @package core\link
 */
class url_final_destination {

    const MAX_URL_REDIRECTS = 3;

    /**
     * @var bool
     */
    private $allow_http = false;

    /**
     * Determine the final URL. By default, this only supports HTTPS but if needed use @see url_final_destination::set_allow_http()
     * If the final url redirect is of a different protocol it will still return it, it's up to the code using this to determine
     * whether it can use the url or not.
     *
     * @param moodle_url $url
     * @param int $max_redirects
     * @return moodle_url
     */
    public function __invoke(moodle_url $url, int $max_redirects = self::MAX_URL_REDIRECTS) {
        $protocol = $this->allow_http ? (CURLPROTO_HTTPS | CURLPROTO_HTTP) : CURLPROTO_HTTPS;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $max_redirects);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, $protocol);
        curl_setopt($ch, CURLOPT_PROTOCOLS, $protocol);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Read the headers as provided by cURL.
        curl_exec($ch);
        $new_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        return new moodle_url($new_url ?? $url);
    }

    /**
     * Allow http, use with care
     *
     * @return $this
     */
    public function set_allow_http(): self {
        $this->allow_http = true;

        return $this;
    }


}