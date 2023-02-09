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
 * @package totara_core
 */

namespace totara_core\virtualmeeting\exception;

use totara_core\virtualmeeting\exception\base_exception;
use Throwable;

/**
 * unsupported exception
 */
class unsupported_exception extends base_exception {
    /**
     * Exception: authentication not available for plugin
     *
     * @param string $plugin
     * @param Throwable $previous
     * @return self
     */
    public static function auth(string $plugin, Throwable $previous = null): self {
        return new self('authentication not available for plugin: '.$plugin, 0, $previous);
    }

    /**
     * Exception: info unsupported by plugin
     *
     * @param string $plugin
     * @return self
     */
    public static function info(string $plugin): self {
        return new self('info unsupported by plugin: '.$plugin);
    }

    /**
     * Exception: feature unsupported by plugin
     *
     * @param string $plugin
     * @return self
     */
    public static function feature(string $plugin): self {
        return new self('feature unsupported by plugin: '.$plugin);
    }
}
