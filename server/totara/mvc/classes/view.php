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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_mvc
 */

namespace totara_mvc;

use core_renderer;
use moodle_page;
use renderable;
use renderer_base;

/**
 * Base view class to either get extended to implement own views or used directly as is.
 *
 * By default this class can work with mustache templates. If the template is omitted and
 * the function prepare_output() returns one of the following it will render it directly:
 *  * implements renderable
 *  * implements viewable
 *  * instance of a view
 *  * string
 *
 * If the template data array contains one of the just mentioned parts the view will render those
 * before passing it to the template rendering. With this you can create nested structures of
 * renderable objects.
 *
 * @package totara_mvc
 */
class view implements viewable {

    /**
     * You can override this in a child class defining an array of [string, component]
     *
     * @var string|array
     */
    protected $title = '';

    /**
     * Name of the template to render in form component/templatename
     *
     * @var string
     */
    protected $template = '';

    /**
     * Data passed to the template
     *
     * @var array
     */
    protected $data = [];

    /**
     * @var array|view_override[]
     */
    private $overrides = [];

    /**
     * @param string|null $template name of the template to use
     * @param array $data this is the data passed to the template during rendering, can be modified in prepare_output()
     */
    public function __construct(?string $template, $data = []) {
        $this->data = $data;
        if (!empty($template)) {
            $this->template = $template;
        }
        // If title was specified as an array with ['stringname', 'component'] get the real string here
        if (!empty($this->title) && is_array($this->title)) {
            [$string, $component] = $this->title;
            $this->title = get_string($string, $component);
        }
        // if there's a title on the page we use it
        $page_title = $this->get_page()->title;
        if (empty($this->title) && !empty($page_title)) {
            $this->title = $this->get_page()->title;
        }
    }

    /**
     * Glorified constructors
     *
     * @param string|null $template name of the template to use
     * @param array $data this is the data passed to the template during rendering, can be modified in prepare_output()
     * @return $this
     */
    public static function create(?string $template, $data = []) {
        return new static($template, $data);
    }

    /**
     * Add an override instance which will be applied right before the view will be rendered.
     * This can be used to apply individual overrides for navigation, breadcrumbs, etc.
     *
     * @param view_override $override
     *
     * @return $this
     */
    public function add_override(view_override $override): self {
        $this->overrides[] = $override;

        return $this;
    }

    /**
     * @return array|view_override[]
     */
    public function get_overrides(): array {
        return $this->overrides;
    }

    /**
     * Removes all overrides from this view
     *
     * @return $this
     */
    public function clear_overrides(): self {
        $this->overrides = [];

        return $this;
    }

    /**
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Get the data which will be passed to the template
     *
     * @return array
     */
    public function get_data(): array {
        return $this->data;
    }

    /**
     * Sets the template data overwriting existing data
     *
     * @param array $data
     * @return $this
     */
    public function set_data(array $data): self {
        $this->data = $data;
        return $this;
    }

    /**
     * @return moodle_page
     */
    public function get_page(): moodle_page {
        global $PAGE;
        return $PAGE;
    }

    /**
     * @return renderer_base
     */
    public function get_renderer(): renderer_base {
        global $OUTPUT;
        return $OUTPUT;
    }

    /**
     * Sets the name of the template in form of component/templatename
     *
     * @param string $template
     * @return $this
     */
    public function set_template(string $template): self {
        $this->template = $template;
        return $this;
    }

    /**
     * Sets the title for this view
     *
     * @param string $title
     * @return $this
     */
    public function set_title(string $title): self {
        $this->title = $title;
        return $this;
    }

    /**
     * Call this to render the view and return the rendered output
     *
     * @return string
     */
    public function render() {
        $renderer = $this->get_renderer();
        $this->prepare_page();

        // If we have any overrides apply them
        foreach ($this->overrides as $override) {
            $override->apply($this);
        }

        $output = $this->render_template($this->prepare_output($this->data));
        return $renderer->header().$output.$renderer->footer();
    }

    /**
     * Prepare page object by setting generic properties, like title, etc.
     *
     * @return $this
     */
    protected function prepare_page() {
        $page = $this->get_page();
        if ($this->title) {
            $page->set_title($this->title);
            $page->set_heading($this->title);
        }
        return $this;
    }

    /**
     * This method can be overridden by other controllers i.e. to convert output to json.
     *
     * @param array|string|\stdClass $output
     * @return string
     */
    protected function prepare_output($output) {
        return $output;
    }

    /**
     * If output is a string it will just return the string and not render a template
     * If output is an array and a template name was specified in the view it renders it and returns the output.
     *
     * @param array|string|viewable|\stdClass $output String does get returned unchanged, array|stdClass gets passed to template
     * @return string
     */
    protected function render_template($output): string {
        // There are several ways the output is rendered depending on the type
        $render_function = function ($value) {
            switch (true) {
                case ($value instanceof renderable):
                    return $this->render_widget($value);
                case ($value instanceof view):
                    return $value->render_template($value->prepare_output($value->data));
                case ($value instanceof viewable):
                    return $value->render();
                default:
                    return $value;
            }
        };

        $output = $render_function($output);
        // Strings are returned directly
        if (is_string($output)) {
            return $output;
        }

        if (empty($this->template)) {
            throw new \coding_exception('Expected a template but no template was defined in this view.');
        }

        // Cast to array, in case of stdClass usage
        $output = (array) $output;
        foreach ($output as $key => $value) {
            $output[$key] = $render_function($value);
        }
        return $this->get_renderer()->render_from_template($this->template, $output);
    }

    /**
     * Get core renderer everywhere w/o globals
     *
     * @return core_renderer
     */
    final public static function core_renderer(): renderer_base {
        global $OUTPUT;
        return $OUTPUT;
    }

    /**
     * helper method to render a single widget directly
     *
     * @param renderable $widget
     * @return string
     */
    final protected function render_widget(renderable $widget): string {
        return self::core_renderer()->render($widget);
    }

    /**
     * Sometimes there's no way around catching output as existing methods might just echo something instead of returning it.
     * This method provides a wrapper around that. You just need to provide a closure creating the output you want to capture.
     *
     * @param \Closure $closure
     * @return string
     */
    final protected function capture_output(\Closure $closure): string {
        ob_start();
        $closure();
        return ob_get_clean();
    }

}
