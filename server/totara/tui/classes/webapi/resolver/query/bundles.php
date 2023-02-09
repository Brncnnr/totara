<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

namespace totara_tui\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\query_resolver;
use totara_tui\local\requirement_description;
use totara_tui\output\framework;

/**
 * Bundles query resolver
 */
class bundles extends query_resolver {
    public static function resolve(array $args, execution_context $ec) {
        global $PAGE;

        if (!isset($args['components'])) {
            // GraphQL asserts this for us, but the resolve method may be called directly as well.
            throw new \coding_exception('Missing required arg: components.');
        }

        if (!isset($args['theme'])) {
            // GraphQL asserts this for us, but the resolve method may be called directly as well.
            throw new \coding_exception('Missing required arg: theme.');
        }

        $components = [];
        foreach ($args['components'] as $component) {
            if ($component === framework::clean_component_name($component)) {
                $components[] = $component;
            }
        }
        if (empty($components)) {
            return [];
        }
        $theme = $args['theme']; // Cleaned by GraphQL, uses type param_theme.

        $framework = framework::get($PAGE);
        foreach ($components as $component) {
            $framework->require_component($component);
        }

        $options = [
            'theme' => $theme,
        ];
        return array_map(function ($bundle) use ($options) {
            return requirement_description::from_requirement($bundle, $options);
        }, $framework->get_bundles());
    }
}
