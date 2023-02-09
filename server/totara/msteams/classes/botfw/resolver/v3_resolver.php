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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\resolver;

use coding_exception;

/**
 * A URL resolver for the v3 REST API.
 */
class v3_resolver implements resolver {
    /**
     * @inheritDoc
     */
    public function start_converstaion_url(string $serviceurl): string {
        $url = $serviceurl;
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }
        $url .= 'v3/conversations';
        return $url;
    }

    /**
     * @inheritDoc
     */
    public function conversation_url(string $serviceurl, string $conversationid, string $route, string $subroute = null): string {
        if (preg_match('/[^a-z]/', $route)) {
            throw new coding_exception('route must contain only a-z letters');
        }
        $url = $this->start_converstaion_url($serviceurl);
        $url .= '/';
        $url .= rawurlencode($conversationid);
        $url .= '/';
        $url .= $route;
        if ((string)$subroute !== '') {
            $url .= '/';
            $url .= rawurlencode($subroute);
        }
        return $url;
    }
}
