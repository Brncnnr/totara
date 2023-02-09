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

namespace totara_msteams\botfw\router;

use lang_string;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\dispatchable;

/**
 * A fluently programmable router.
 */
class dynamic_router implements router {
    /**
     * @var array
     */
    private $routes = [];

    /**
     * @return route[]
     */
    public function get_routes(): array {
        $return = [];
        foreach ($this->routes as [$selector, $route]) {
            /** @var route $route */
            $return[] = $route;
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function find_best_match(activity $activity): ?route {
        foreach ($this->routes as [$selector, $route]) {
            /** @var route $route */
            if (self::selector_match($selector, $activity)) {
                if (self::is_feature_disabled($route)) {
                    return null;
                }
                // Ignore any request from a team unless it's permitted.
                if ($route->has(route::TEAM) || empty($activity->conversation->isGroup)) {
                    return $route;
                }
            }
        }
        return null;
    }

    /**
     * Add a routing point.
     *
     * @param callable|array $selector
     * @param dispatchable $dispatcher
     * @param integer $flags 0 or route::QUIET
     * @return self
     */
    public function add($selector, dispatchable $dispatcher, int $flags = 0): self {
        $this->routes[] = [$selector, new route($dispatcher, $flags)];
        return $this;
    }

    /**
     * @return array
     */
    protected function get_routes_internal(): array {
        return $this->routes;
    }

    /**
     * Compare activity->text.
     *
     * @param string $incoming activity->text
     * @param string|lang_string $expected
     * @return boolean
     */
    protected static function compare_text(string $incoming, $expected): bool {
        // MS Teams appends U+00A0 (aka no-break space or &nbsp;) when selecting a command from the list
        $incoming = trim($incoming, " \t\n\r\0\x0B\u{A0}");
        return $incoming === (string)$expected;
    }

    /**
     * @param callable|array $selector
     * @param activity $activity
     * @return boolean
     */
    private static function selector_match($selector, activity $activity): bool {
        if (is_callable($selector)) {
            return call_user_func($selector, $activity);
        }
        if (is_array($selector)) {
            foreach ($selector as $name => $value) {
                if (!isset($activity->{$name})) {
                    return false;
                }
                $field = $activity->{$name};
                if ($name === 'text' && is_string($field)) {
                    if (static::compare_text($field, $value)) {
                        continue;
                    }
                    return false;
                }
                if ($field !== $value) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param route $route
     * @return boolean
     */
    private static function is_feature_disabled(route $route): bool {
        if ($route->has(route::EXTENSION)) {
            if (empty(get_config('totara_msteams', 'messaging_extension_enabled'))) {
                return true;
            }
        } else {
            if (empty(get_config('totara_msteams', 'bot_feature_enabled'))) {
                return true;
            }
        }
        return false;
    }
}
