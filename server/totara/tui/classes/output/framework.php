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
use core_renderer;
use moodle_page;
use totara_tui\local\locator\bundle;
use totara_tui\local\requirement;

/**
 * This class tracks requirements for TUI.
 */
final class framework implements \core\output\framework {

    public const COMPONENT = 'tui';

    /** @var string[] List of Totara components to load TUI bundles for. */
    private $components = [];

    /** @var string[] List of components that must be loaded, even if they do not directly have resources to load. */
    private $forceload = [];

    /** @var array Map of Totara components to their state in the sort. */
    private $final_component_state;

    /** @var string[] Final sorted component list. */
    private $final_components;

    /** @var requirement[] Cache of generated bundle objects. */
    private $bundles = null;

    /** @var array */
    private $css_urls = [];

    /**
     * Returns an instance of this framework.
     *
     * @return \core\output\framework
     */
    public static function new_instance(): \core\output\framework {
        return new self();
    }

    /**
     * Returns the instance of this framework that is held by the given pages requirements manager.
     *
     * @param moodle_page $page
     * @return framework
     */
    public static function get(moodle_page $page): framework {
        /** @var framework $framework */
        $framework = $page->requires->framework(__CLASS__);
        return $framework;
    }

    /**
     * Initialise this instance of the framework
     */
    public function initialise(): void {
        // We always require Tui component so that components wishing to auto-initialise from attributes can.
        $this->require_component(self::COMPONENT);
    }

    /**
     * A hook into page_requirements_manager::get_head_code()
     * @param moodle_page $page
     * @param core_renderer $renderer
     */
    public function get_head_code(moodle_page $page, core_renderer $renderer): void {
        // Always require the theme bundle first.
        $this->require_theme_bundle($page->theme);

        // Include the CSS for loaded TUI components.
        $this->resolve_css_urls($page->theme->name);
    }

    /**
     * Requires the page theme bundle.
     * @param \theme_config $theme
     */
    private function require_theme_bundle(\theme_config $theme) {
        $themes = array_map(
            function($value) {
                return 'theme_' . $value;
            },
            array_merge([$theme->name],  $theme->parents)
        );
        if (bundle::any_have_resources($themes)) {
            $component = 'theme_' . $theme->name;
            $this->require_component($component);
            $this->forceload[] = $component;
        }
    }

    /**
     * Resolve the required CSS urls, given the target theme the page is using.
     * @param string $themename
     */
    private function resolve_css_urls(string $themename) {
        $this->css_urls = [];
        $requirement_url_options = ['theme' => $themename];
        foreach ($this->get_bundles(requirement::TYPE_CSS) as $bundle) {
            $this->css_urls[] = $bundle->get_url($requirement_url_options);
        }
    }

    /**
     * Inject CSS URLs for required bundles into the given array of URLs.
     * @param array $urls Passed by reference.
     */
    public function inject_css_urls(array &$urls): void {
        // Add TUI SCSS bundles.
        // Find last tui_scss url. It should be the theme.
        $count = count($urls);
        for ($theme_index = $count - 1; $theme_index >= 0; $theme_index--) {
            if (strpos($urls[$theme_index], 'tui_scss')) {
                break;
            }
        }
        // Add the bundles before the theme CSS so theme CSS can override the CSS in them.
        array_splice($urls, $theme_index === -1 ? $count : $theme_index, 0, $this->css_urls);
    }

    /**
     * Injects JavaScript URLS for required bundles into the given array of URLs.
     * @param array $urls Passed by reference
     * @param bool $initialiseamd
     */
    public function inject_js_urls(array &$urls, bool $initialiseamd): void {
        if (!$initialiseamd) {
            return;
        }
        foreach ($this->get_bundles('js') as $bundle) {
            $urls[] = $bundle->get_url();
        }
    }

    /**
     * Initialises and registers the requested component.
     *
     * @param string $name The name of the component to load.
     * @param moodle_page|null $page If null $PAGE is used.
     * @return component
     */
    public static function vue($name, moodle_page $page = null): component {
        global $PAGE;
        if ($page === null) {
            $page = $PAGE;
        }
        $component = new component($name);
        $component->register($page);
        return $component;
    }

    /**
     * Request the TUI bundle(s) for the provided component be loaded.
     *
     * @param string $component Totara component
     */
    public function require_component(string $component) {
        if (!$component) {
            throw new coding_exception('component is required');
        }
        if ($this->is_component_required($component)) {
            // It's already been required.
            return;
        }

        // Invalidate cache.
        $this->final_components = null;
        $this->bundles = null;

        $this->components[] = $component;
    }

    /**
     * Returns true if the given component has already been required.
     * @param string $component
     * @return bool
     */
    public function is_component_required(string $component): bool {
        return (in_array($component, $this->components));
    }

    /**
     * Request the TUI bundle(s) for the provided component be loaded.
     *
     * This must be called before $OUTPUT->header(), as by then the <head> will have
     * already been sent, meaning we wouldn't be able to add the CSS bundle.
     *
     * @param string $name Vue component name, e.g. 'mod_example/pages/Example'.
     */
    public function require_vue(string $name) {
        $pos = strpos($name, '/');
        if ($pos !== false) {
            $component = substr($name, 0, $pos);
        } else {
            $component = $name;
        }
        $this->require_component($component);
    }

    /**
     * Get the full list of components to load TUI bundles for, including dependencies.
     *
     * @return string[]
     */
    public function get_final_components(): array {
        if ($this->final_components !== null) {
            return $this->final_components;
        }

        // figure out what order to load the component bundles in
        // this algorithm is a variant of the depth-first topological sort:
        // https://en.wikipedia.org/wiki/Topological_sorting

        $this->final_component_state = [];
        $this->final_components = [];

        foreach ($this->components as $bundle) {
            $this->get_final_components_visit($bundle, '(direct)');
        }

        return $this->final_components;
    }

    /**
     * Visit component for topological sort.
     *
     * @param string $bundle The bundle to require.
     * @param string $reqby Who required this bundle?
     */
    private function get_final_components_visit(string $bundle, string $reqby) {
        if (isset($this->final_component_state[$bundle])) {
            if ($this->final_component_state[$bundle] === true) {
                // already processed.
                return;
            } else {
                // if state is false we are already processing this dependency,
                // so there must be a circular dependency in the graph
                throw new coding_exception("Circular dependency in TUI bundle \"{$bundle}\" requested by \"${reqby}\"");
            }
        }

        // mark the bundle as processing
        $this->final_component_state[$bundle] = false;

        // process every dependency
        $dependencies = bundle::get_bundle_dependencies($bundle);
        foreach ($dependencies as $dependency) {
            $this->get_final_components_visit($dependency, $bundle);
        }

        // mark the bundle as processed
        $this->final_component_state[$bundle] = true;

        // all of our dependencies, and their dependencies, and so on have been added at this point, so add ourselves
        $this->final_components[] = $bundle;
    }

    /**
     * Get details of each bundle we need to load.
     *
     * @param string|null $type Filter to type of bundle, e.g. 'js' or 'css'. Null for all.
     * @return requirement[]
     */
    public function get_bundles($type = null): array {
        $bundles = $this->get_bundles_internal();
        if ($type !== null) {
            $filtered = [];
            foreach ($bundles as $bundle) {
                if ($bundle->get_type() == $type) {
                    $filtered[] = $bundle;
                }
            }
            return $filtered;
        }
        return $bundles;
    }

    /**
     * Internal implementation of get_bundles()
     *
     * @return requirement[]
     */
    private function get_bundles_internal() {
        if ($this->bundles !== null) {
            return $this->bundles;
        }

        $components = $this->get_final_components();
        // We only want the components.
        $components = array_values($components);

        $requirements = [];

        $requires_tui = false;
        foreach ($components as $component) {
            if ($component === self::COMPONENT) {
                $requires_tui = true;
                continue;
            }
            $js = new requirement\js($component);
            $scss = new requirement\scss($component);

            if (in_array($component, $this->forceload)) {
                $js->force_resource_to_load();
                $scss->force_resource_to_load();
            }

            $requirements[] = $js;
            $requirements[] = $scss;
        }

        $this->bundles = array_filter($requirements, function(requirement $requirement) {
            return $requirement->has_resources_to_load();
        });

        if (!empty($this->bundles) || $requires_tui) {
            array_unshift(
                $this->bundles,
                new requirement\vendors_js(),
                new requirement\js(self::COMPONENT),
                new requirement\scss(self::COMPONENT)
            );
        }

        return $this->bundles;
    }

    /**
     * Clean component name.
     *
     * @param string $name
     * @return string The cleaned name
     */
    public static function clean_component_name(string $name): string {
        return clean_param($name, PARAM_SAFEDIR);
    }

    /**
     * Clean bundle name.
     *
     * @param string $name
     * @return string The cleaned name
     */
    public static function clean_bundle_name(string $name): string {
        return clean_param($name, PARAM_SAFEDIR);
    }
}
