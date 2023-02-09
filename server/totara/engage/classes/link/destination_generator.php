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
 * @package totara_engage
 */

namespace totara_engage\link;

use moodle_url;

/**
 * Generator for destination/target pages. Any page in the "to" part of the link
 * should extend & implement this class.
 *
 * @package totara_engage\link
 */
abstract class destination_generator {
    /**
     * Attributes used to build the source URL
     *
     * @var array
     */
    protected $attributes;

    /**
     * @var source_generator
     */
    protected $source_generator;

    /**
     * Params to auto-populate from attributes (no conversion)
     *
     * @var array
     */
    protected $auto_populate = [];

    /**
     * Prevent direct construction
     *
     * @param array $attributes
     */
    private function __construct(array $attributes) {
        $this->attributes = $attributes;
    }

    /**
     * @param array $attributes
     * @return destination_generator
     */
    public static function make(array $attributes): destination_generator {
        return new static($attributes);
    }

    /**
     * @param string $component_or_page
     * @param array $attributes
     * @return destination_generator
     */
    public function from(string $component_or_page, array $attributes = []): destination_generator {
        $this->source_generator = builder::find_source_generator($component_or_page, $attributes);

        return $this;
    }

    /**
     * Create the destination URL with the source attached
     *
     * @return moodle_url
     */
    final public function url(): moodle_url {
        $url = $this->base_url();

        // Auto-populate the params
        foreach ($this->auto_populate as $param_key) {
            if (array_key_exists($param_key, $this->attributes)) {
                $url->param($param_key, $this->attributes[$param_key]);
            } else {
                debugging("Required URL param '$param_key' was not provided", DEBUG_DEVELOPER);
            }
        }

        // Now handle any custom params
        $this->add_custom_url_params($this->attributes, $url);

        // If a source is passed in already, use it
        // Otherwise if a source generator is attached, ask it for one.
        if (!empty($this->attributes['source'])) {
            $url->param('source', $this->attributes['source']);
        } else if ($this->source_generator) {
            $source = $this->source_generator->build_source();
            $url->param('source', $source);
            $this->source_generator->add_custom_url_params($this->attributes, $url);
        }

        return $url;
    }

    /**
     * Return the label for the back button on the article/survey/playlist pages.
     *
     * @return string
     */
    public function label(): string {
        return get_string('back', 'moodle');
    }

    /**
     * @param bool $escaped
     * @return string
     */
    public function out(bool $escaped = false): string {
        return $this->url()->out($escaped);
    }

    /**
     * Helper for the attributes needed to render the back button on some pages
     *
     * @return array|null
     */
    public function back_button_attributes(): ?array {
        return [
            'url' => $this->out(),
            'label' => $this->label(),
            'history' => false,
        ];
    }

    /**
     * @param array $attributes
     * @return destination_generator
     */
    public function set_attributes(array $attributes): destination_generator {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @return destination_generator
     */
    public function set_attribute(string $key, $value): destination_generator {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get_attribute(string $key) {
        return $this->attributes[$key] ?? null;
    }

    /**
     * @return array
     */
    public function get_attributes(): array {
        return $this->attributes;
    }

    /**
     * Build the base URL for the page
     *
     * @return moodle_url
     */
    abstract protected function base_url(): moodle_url;

    /**
     * Generates the array of params required to build this URL. These are attached to the URL.
     *
     * @param array $attributes
     * @param moodle_url $url
     * @return void
     */
    protected function add_custom_url_params(array $attributes, moodle_url $url): void {
    }
}