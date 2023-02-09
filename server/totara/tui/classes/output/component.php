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

namespace totara_tui\output;

use coding_exception;
use moodle_page;
use renderable;

/**
 * Renderable TUI component
 */
final class component implements renderable {
    /**
     * @var string Component name
     */
    private $name;

    /**
     * @var array|null Component props
     */
    private $props;

    /**
     * Create a new instance of component
     *
     * @param string $name Module ID of the component to render.
     *     e.g. 'totara_core/components/Example`.
     * @param array|null $props Props to pass to the component.
     *     Props are not processed or escaped in any way, they are passed as-is
     *     to the component. Must be encodable as JSON.
     */
    public function __construct(string $name, array $props = null) {
        $this->name = $name;
        $this->props = $props;
    }

    /**
     * Get component name
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get component props
     * @return array|null Mapping of prop keys to values
     */
    public function get_props(): ?array {
        return $this->props;
    }

    /**
     * Returns true if this component has props
     * @return bool
     */
    public function has_props(): bool {
        return ($this->props !== null);
    }

    /**
     * Get the props for this component
     * @return string JSON encoded props string.
     * @throws coding_exception if there are problems json encoding data for the component.
     */
    public function get_props_encoded(): string {
        if (!$this->has_props()) {
            // If you get here and want to remove this then you must work out and confirm what empty props look like for Vue.
            // It wasn't required during development and it's a bad idea to support something you don't need!
            throw new coding_exception('Encoded props requested, but there are no props.');
        }
        $data = json_encode($this->props);
        if ($data === false && json_last_error() !== JSON_ERROR_NONE) {
            throw new coding_exception('Invalid component data encountered while attempting to encode a component of type ' . static::class, json_last_error_msg());
        }
        return $data;
    }

    /**
     * Register this component against the given page instance.
     * @param moodle_page $page
     * @return moodle_page
     */
    public function register(moodle_page $page): moodle_page {
        return self::register_component($this->get_name(), $page);
    }

    /**
     * Register a Tui component usage by name.
     * @chainable
     * @param string $name
     * @param moodle_page $page
     * @return moodle_page The page instance passed in as an argument, to make this chainable.
     * @throws coding_exception If it is too late to register a new component use.
     */
    public static function register_component(string $name, moodle_page $page): moodle_page {
        $pos = strpos($name, '/');
        if ($pos !== false) {
            $name = substr($name, 0, $pos);
        }
        $framework = self::get_framework($page);
        if (!$framework->is_component_required($name)) {
            if ($page->state >= moodle_page::STATE_IN_BODY) {
                throw new coding_exception('Unable to register component as the header has already been printed.');
            }
            $framework->require_vue($name);
        }
        return $page;
    }

    /**
     * Returns the Tui framework instance associated with the given page instance.
     * @param moodle_page $page
     * @throws coding_exception
     * @return framework
     */
    private static function get_framework(moodle_page $page): framework {
        /** @var framework $framework */
        $framework = $page->requires->framework(framework::class);
        return $framework;
    }

    /**
     * Returns a span tag that can be transformed by the Tui front end framework into a Tui component.
     *
     * This method does not include javascript to the page, it will have to be done via calling renderer/OUTPUT.
     *
     * @return string
     */
    public function out_html(): string {
        $attributes = [
            $this->encode_html_attribute('data-tui-component', $this->get_name()),
        ];

        if ($this->has_props()) {
            $attributes[] = $this->encode_html_attribute('data-tui-props', $this->get_props_encoded());
        }

        return '<span ' . join(' ', $attributes) . '></span>';
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    private function encode_html_attribute(string $key, string $value): string {
        // TL-22100: use htmlspecialchars() rather than s() as s() will unencode some double encoded HTML entities, resulting
        // in prop injection and potential XSS. This is not a standard approach, you should be using s() normally.
        $key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return "{$key}=\"{$value}\"";
    }
}
