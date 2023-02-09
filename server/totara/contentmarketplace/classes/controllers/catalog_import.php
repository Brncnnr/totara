<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\controllers;

use coding_exception;
use container_course\course;
use contentmarketplace_linkedin\exception\course_not_found;
use contentmarketplace_linkedin\exception\section_not_found;
use context;
use context_course;
use context_coursecat;
use context_system;
use core_component;
use core_container\factory;
use core_container\section\section;
use core_container\section\section_factory;
use dml_missing_record_exception;
use moodle_exception;
use moodle_url;
use totara_contentmarketplace\explorer as explorer_model;
use totara_contentmarketplace\interactor\catalog_import_interactor;
use totara_contentmarketplace\local;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_mvc\controller;
use totara_mvc\tui_view;
use totara_mvc\view;

/**
 * Common logic across all explorer controller for sub plugins.
 */
class catalog_import extends controller {
    /**
    * @var string
    */
    protected $layout = 'noblocks';

    /**
    * @var contentmarketplace
    */
    protected $plugin;

    /**
    * @var explorer_model
    */
    protected $explorer;

    /** @var bool|null */
    protected $is_add_activity_page;

    /** @var int|null */
    protected $course_id;

    /** @var course */
    protected $course;

    /** @var int|null */
    protected $section_id;

    /** @var section|null */
    protected $section;

    /**
    * explorer constructor.
    */
    public function __construct() {
        parent::__construct();

        // If plugin is set, we just skip assignment.
        if (!isset($this->plugin)) {
            $this->plugin = contentmarketplace::plugin($this->get_marketplace());
        }

        $this->explorer = new explorer_model($this->get_marketplace(), $this->get_layout(), $this->get_category_id());
        $this->url = new moodle_url('/totara/contentmarketplace/explorer.php', ['marketplace' => $this->get_marketplace()]);
    }

    /**
     * @return context_system|context_coursecat
    */
    protected function setup_context(): context {
        $category_id = $this->get_category_id();
        if ($this->is_add_activity_page()) {
            $course_id = $this->get_course_id();
            return context_course::instance($course_id);
        } elseif (is_null($category_id)) {
            return context_system::instance();
        } else {
            return context_coursecat::instance($category_id);
        }
    }

    /**
    * Checks and call require_login if parameter is set, can be overridden if special set up is needed
    *
    * @return void
    */
    final protected function authorize(): void {
        parent::authorize();

        local::require_contentmarketplace();
        $this->check_plugin_enabled();

        $this->authorize_with_interactor();
    }

    /**
     * Authorise the user using the interactor class.
     * This can be overridden to use a plugin-specific interactor.
     */
    protected function authorize_with_interactor(): void {
        /** @var context_course|context_coursecat|context_system $context */
        $context = $this->get_context();
        $interactor = new catalog_import_interactor();

        if (CONTEXT_COURSE === $context->contextlevel) {
            $interactor->require_add_activity_to_course($context);
        } elseif (CONTEXT_COURSECAT === $context->contextlevel) {
            // If we are going to add courses via the course category
            // level context page, then we can check if the user has the
            // capability enabled for such context in order to view the page.
            $interactor->require_add_course_to_category($context);
        } else {
            $interactor->require_view_catalog_import_page();
        }
    }

    /**
     * @return mixed
     */
    public function action() {
        $class = $this->get_current_controller_class();

        return (new $class())->action();
    }

    /**
     * @return string
     */
    private function get_current_controller_class(): string {
        $classes = core_component::get_namespace_classes(
            'controllers',
            self::class,
            $this->plugin->component
        );

        if (empty($classes)) {
            throw new coding_exception("{$this->plugin->component} has to define explorer controller.");
        }

        if (count($classes) > 1) {
            throw new coding_exception("Only one catalog_import controller should be returned.");
        }


        $class = reset($classes);

        if (!is_subclass_of($class, self::class)) {
            throw new coding_exception("{$class} is not sub class of explorer");
        }

        return $class;
    }

    /**
     * Returns tui view for explorer controllers
     *
     * @param string $component
     * @param array $props
     * @return tui_view
     */
    public function create_tui_view(string $component, array $props = []): tui_view {
        return tui_view::create($component, $props);
    }

    /**
     * Returns view for all explorer controllers
     *
     * @param string $template
     * @param array $data
     * @return view
     */
    public function create_view(string $template, array $data = []): view {
        return view::create($template, $data);
    }

    /**
     * @return int|null
     */
    public function get_category_id(): ?int {
        return $this->get_optional_param('category', null, PARAM_INT);
    }

    /**
     * @return string
     */
    public function get_mode(): string {
        return $this->get_optional_param('mode', explorer_model::MODE_EXPLORE, PARAM_ALPHAEXT);
    }

    /**
     * @return string
     */
    public function get_marketplace(): string {
        return $this->get_required_param('marketplace', PARAM_ALPHA);
    }

    /**
     * This will be called before the constructor
     *
     * @return void
     */
    private function check_plugin_enabled(): void {
        if (!isset($this->plugin)) {
            $this->plugin = contentmarketplace::plugin($this->get_marketplace());
        }

        if (!$this->plugin->is_enabled()) {
            throw new moodle_exception('error:disabledmarketplace', 'totara_contentmarketplace', '', $this->plugin->displayname);
        }
    }

    /**
     * @return bool
     */
    public function can_manage_marketplace_plugins(): bool {
        return has_capability('totara/contentmarketplace:config', $this->get_context());
    }

    /**
     * @return explorer_model
     */
    public function get_explorer(): explorer_model {
        return $this->explorer;
    }

    /**
     * @return contentmarketplace
     */
    public function get_plugin(): contentmarketplace {
        return $this->plugin;
    }

    /**
     * @return bool
     */
    protected function is_add_activity_page(): bool {
        if (!is_null($this->is_add_activity_page)) {
            return $this->is_add_activity_page;
        }
        $course_id = $this->get_course_id();
        $section_id = $this->get_section_id();
        $mode = $this->get_mode();
        return ($mode === explorer_model::MODE_ADD_ACTIVITY) && !is_null($course_id) && !is_null($section_id);
    }

    /**
     * @return int|null
     */
    public function get_course_id(): ?int {
        if (is_null($this->course_id) && !is_null($this->get_section_id())) {
            try {
                $this->section = section_factory::from_id((int) $this->get_section_id());
            } catch (dml_missing_record_exception $exception) {
                throw new section_not_found((string) $this->section_id);
            }
            if (!$this->section instanceof section) {
                throw new section_not_found((string) $this->section_id);
            }
            $this->course_id = $this->section->get_container_id();
            try {
                $this->course = factory::from_id($this->course_id);
            } catch (dml_missing_record_exception $exception) {
                throw new course_not_found((string) $this->course_id);
            }
            if (!$this->course instanceof course) {
                throw new course_not_found((string) $this->course_id);
            }
        }
        return $this->course_id;
    }

    /**
     * @return int|null
     */
    public function get_section_id(): ?int {
        if (is_null($this->section_id)) {
            $this->section_id = $this->get_optional_param('section_id', null, PARAM_INT);
        }
        return $this->section_id;
    }
}