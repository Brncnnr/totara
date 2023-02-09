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

namespace totara_tui\local;

use totara_core\path;

/**
 * Represents a TUI bundle file for a Totara component
 */
abstract class requirement {

    const TYPE_JS = 'js';
    const TYPE_CSS = 'css';

    /**
     * @var string Totara component this requirement is part of, e.g. 'mod_example'
     */
    protected $component;

    /**
     * @var string Name of the bundle (e.g. tui_bundle.js)
     */
    protected $name;

    /**
     * @var bool Set to true if this requirement should be requested, even if there is no immediate resource to serve.
     */
    private $required = false;

    /**
     * Create a new instance of requirement
     *
     * @param string $component Totara component this requirement is part of, e.g. 'mod_example'
     * @param string $name Name of the bundle (e.g. tui_bundle.js)
     */
    public function __construct(string $component, string $name) {
        $this->component = $component;
        $this->name = $name;
    }

    /**
     * Get the type of the bundle, e.g. 'js' or 'css'
     *
     * @return string
     */
    abstract public function get_type(): string;

    /**
     * Get the URL the requirement is served from
     *
     * @param array $options Extra context that may be needed to generate the URL
     *   - theme: Name of current theme
     * @return \moodle_url
     */
    abstract public function get_url(array $options = null): \moodle_url;

    /**
     * Returns true if this requirement has resources to load.
     */
    public final function has_resources_to_load(): bool {
        if ($this->required) {
            return true;
        }
        return !is_null($this->get_required_resource());
    }

    /**
     * Returns true if this requirement is required.
     * @return path|null The path to the resource, or null if it does not have one.
     */
    abstract public function get_required_resource(): ?path;

    /**
     * Forces the requirement to load even if it does not have a resource file.
     */
    public final function force_resource_to_load() {
        $this->required = true;
    }

    /**
     * Get the Totara component this requirement is part of, e.g. 'mod_example'
     *
     * @return string
     */
    public final function get_component(): string {
        return $this->component;
    }

    /**
     * Get the name of the bundle, e.g. 'tui_bundle.js'
     *
     * @return string
     */
    public final function get_name(): string {
        return $this->name;
    }

    /**
     * Get data to serve through the API
     *
     * @param array $options Options to pass to {@see requirement::get_url()}
     * @return object
     */
    public final function get_api_data(array $options = null): \stdClass {
        $data = new \stdClass;
        $data->id = $this->get_component() . ':' . $this->get_name();
        $data->type = $this->get_type();
        $data->component = $this->get_component();
        $data->name = $this->get_name();
        $data->url = $this->get_url($options)->out(false);
        return $data;
    }
}
