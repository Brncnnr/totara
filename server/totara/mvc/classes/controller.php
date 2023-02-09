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

use coding_exception;
use context;
use context_system;
use JsonSerializable;
use moodle_page;
use moodle_url;
use stdClass;
use Throwable;
use totara_webapi\serverless;

defined('MOODLE_INTERNAL') || die();

/**
 * Extend this class and implement at least the action() method for a single action controller
 * or any action_* methods for providing multiple actions in one controller.
 *
 * Example usage:
 * ```php
 * $controller = new my_single_action_controller();
 * $controller->process();  // will call ->action()
 *
 * $controller = new my_multiple_action_controller();
 * $controller->process('my_action_name');   // will call ->action_my_action_name();
 * ```
 *
 * For every controller you have to specify the context. The context ideally
 * is as specific as possible. The context is used for require_login and if a course context or a lower one
 * is provided the controller will pass the correct course and / or course module on to require_login.
 *
 * @package totara_mvc
 */
abstract class controller {

    /**
     * Set this to false if the action called does not require a login.
     * Defaults to true to be on the safe side.
     *
     * @var bool
     */
    protected $require_login = true;

    /**
     * Automatically log in as guest if user is not logged in
     * Defaults to true
     *
     * @var bool
     */
    protected $auto_login_guest = true;

    /**
     * @var context
     */
    protected $context;

    /**
     * Optional, If not specified the default layout is used
     *
     * @var string
     */
    protected $layout;

    /**
     * URL as string or moodle_url object, cannot be a relative url
     *
     * @var string|moodle_url
     */
    protected $url = '';

    public function __construct() {
        $this->init_page_object();
    }

    /**
     * Prepares the page object with all the necessary bits to be on the safe side
     *
     * @return void
     */
    protected function init_page_object() {
        $page = $this->get_page();

        // Set the page layout if specified here rather than in the view
        // as once it is set and something triggers the output to get initialized
        // then changing it later won't have an effect
        if (!empty($this->layout)) {
            $page->set_pagelayout($this->layout);
        }

        $context_setup_exception = null;

        try {
            $this->context = $this->setup_context();
        } catch (Throwable $e) {
            // We don't want to display context setup issues to unauthorized users.
            // So if anything goes wrong in the context set up we carry one so
            // authorize call call require_login() if appropriate.
            $context_setup_exception = $e;
        }

        $page->set_context($this->context);

        if ($this->require_login()) {
            $this->authorize();
        }

        if ($context_setup_exception !== null) {
            throw $context_setup_exception;
        }
    }

    /**
     * @return bool
     */
    protected function require_login(): bool {
        global $CFG;
        return $this->require_login || $CFG->forcelogin;
    }

    /**
     * Override get_default_context() either returning a system or a specific context.
     *
     * @return context
     */
    abstract protected function setup_context(): context;

    /**
     * Processes an action. In case of this controller it renders the output.
     *
     * @param string $action if omitted the default action() method is called
     */
    public function process(string $action = '') {
        $view = $this->run_action($action);

        if (empty($this->url)) {
            throw new coding_exception('You have to define an url for this controller.');
        }
        $this->get_page()->set_url($this->url);

        if ($view instanceof viewable) {
            $output = $view->render();
        } else if (is_array($view) || $view instanceof stdClass || $view instanceof JsonSerializable) {
            $output = json_encode($view);
        } else if (is_string($view) || method_exists($view , '__toString')) {
            $output = $view;
        } else {
            throw new coding_exception(
                sprintf(
                    "Expected controller action to return either an implementation of ".
                    "%s or JsonSerializable, array, stdClass or a string. Instead got %s",
                    viewable::class,
                    gettype($view)
                )
            );
        }

        echo $output;
    }

    /**
     * Checks and call require_login if parameter is set, can be overridden if special set up is needed
     *
     * @return void
     */
    protected function authorize(): void {
        if ($this->context) {
            [$context, $course, $cm] = get_context_info_array($this->context->id);
            require_login($course, $this->auto_login_guest, $cm);
        } else {
            require_login(null, $this->auto_login_guest);
        }

    }

    /**
     * Process graphql operation.
     *
     * @param string $operation_name
     * @param array $params
     * @return array
     */
    public function execute_graphql_operation(?string $operation_name = null, array $params = []): array {
        return serverless::execute_operation($operation_name, $params);
    }

    /**
     * This calls the method matching the given action or the default action() if no action was specified
     *
     * @param string $action
     * @return viewable|string|array|stdClass|JsonSerializable
     */
    protected function run_action(string $action = '') {
        $method_name = !empty($action) ? "action_{$action}" : 'action';
        if (!method_exists($this, $method_name)) {
            throw new coding_exception("Missing action method {$method_name}");
        }

        return $this->{$method_name}();
    }

    /**
     * This is the default action and it can be overridden by the children.
     * If no action is passed to the render method this default action is called.
     * In this case it has to be defined in child classes.
     *
     * @return viewable|string|array|stdClass|JsonSerializable if it cannot be cast to a string the result will be json encoded
     */
    public function action() {
        throw new coding_exception(
            'No default action defined. Either override this or provide a specific action method, i.e. action_list().'
        );
    }

    /**
     * Wrap require_capability function, can be chained for multiple checks.
     *
     * @param $capability
     * @param context|null $context defaults to system context
     * @param null $userid
     * @param bool $doanything
     * @param string $errormessage
     * @param string $stringfile
     * @return $this
     */
    final public function require_capability(
        $capability,
        context $context,
        $userid = null,
        $doanything = true,
        $errormessage = 'nopermissions',
        $stringfile = ''
    ) {
        require_capability($capability, $context, $userid, $doanything, $errormessage, $stringfile);
        return $this;
    }

    /**
     * Returns layout defined for this controller
     *
     * @return string
     */
    final public function get_layout(): ?string {
        return $this->layout;
    }

    /**
     * Returns global page object
     *
     * @return moodle_page
     */
    final public function get_page(): moodle_page {
        global $PAGE;
        return $PAGE;
    }

    /**
     * Returns context defined for this controller
     *
     * @return context
     */
    final public function get_context(): context {
        return $this->context;
    }

    /**
     * Get a required param, will throw an exception if the param does not exist
     *
     * @param string $name Param name
     * @param string $type Param type
     * @return mixed
     */
    final public function get_required_param(string $name, string $type) {
        return $this->get_param($name, $type, null, true);
    }

    /**
     * Get an optional param, does fall back to the given default if param does not exist
     *
     * @param string $name Param name
     * @param mixed $default Default value
     * @param string $type Param type
     * @return mixed
     */
    final public function get_optional_param(string $name, $default, string $type) {
        return $this->get_param($name, $type, $default, false);
    }

    /**
     * Get request param, use required parameter to switch between required and optional params
     * Only use this for flat params, means no arrays. Will throw an exception if it's an array.
     *
     * @param string $name Param name
     * @param string $type Param type
     * @param mixed|null $default Default value
     * @param bool $required Required?
     * @return mixed
     */
    private function get_param(string $name, string $type, $default = null, bool $required = false) {
        $value = $required ? required_param($name, $type) : optional_param($name, $default, $type);
        if (is_array($value)) {
            throw new coding_exception('Requested a non-array param but got an array. Please use get_param_array().');
        }
        return $value;
    }

    /**
     * Get a required param, will throw an exception if the param does not exist
     *
     * @param string $name Param name
     * @param string $type Param type
     * @return array
     */
    final public function get_required_param_array(string $name, string $type): array {
        return $this->get_param_array($name, $type, null, true);
    }

    /**
     * Get an optional param, does fall back to the given default if param does not exist
     *
     * @param string $name Param name
     * @param null|array $default Default value
     * @param string $type Param type
     * @return array
     */
    final public function get_optional_param_array(string $name, ?array $default, string $type): array {
        return $this->get_param_array($name, $type, $default, false);
    }

    /**
     * Get request attribute of array type, use required parameter to switch between required and optional params
     *
     * @param string $name Param name
     * @param string $type Param type in sense of type[]
     * @param null|array $default Default value
     * @param bool $required Required?
     * @return mixed[]
     */
    private function get_param_array(string $name, string $type, ?array $default = null, bool $required = false): array {
        return $required ? required_param_array($name, $type) : optional_param_array($name, $default, $type);
    }

    /**
     * Shortcut to global $USER.
     *
     * @return stdClass
     */
    final public function currently_logged_in_user(): stdClass {
        global $USER;
        return $USER;
    }

    /**
     * Sets the url for this controller, optional. Accepts either string|array combination or a moodle_url as the first argument.
     *
     * @param string|moodle_url $url
     * @param array $params will be ignored if url is a moodle url
     * @return $this
     */
    final protected function set_url($url, array $params = []): self {
        if (!$url instanceof moodle_url) {
            $url = new moodle_url($url, $params);
        } else if (!empty($params)) {
            debugging('Additional params will be ignored if url is passed as moodle url', DEBUG_DEVELOPER);
        }

        $this->url = $url;

        return $this;
    }

}
