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

namespace totara_contentmarketplace\views;

use moodle_page;
use totara_contentmarketplace\controllers\catalog_import;
use totara_contentmarketplace\explorer;
use totara_mvc\view;
use totara_mvc\view_override;
use totara_contentmarketplace\plugininfo\contentmarketplace;

/**
 * Class override_catalog_import_nav_breadcrumbs
 */
class override_catalog_import_nav_breadcrumbs implements view_override {
    /**
     * @var catalog_import
     */
    protected $controller;

    /**
     * @var contentmarketplace
     */
    protected $plugin;

    /**
     * override_catalog_import_nav_breadcrumbs constructor.
     * @param catalog_import $controller
     */
    public function __construct(catalog_import $controller) {
        $this->controller = $controller;
        $this->plugin = $controller->get_plugin();
    }

    /**
     * @inheritDoc
     */
    public function apply(view $view): void {
        $this->apply_nav_breadcrumbs($view->get_page());
        $this->add_manage_content_button($view->get_page());
    }

    /**
     * Customize navigation breadcrumbs.
     *
     * @param moodle_page $page
     * @return void
     */
    private function apply_nav_breadcrumbs(moodle_page $page): void {
        if ($this->controller->get_mode() === explorer::MODE_CREATE_COURSE) {
            $page->navbar->add(get_string('administrationsite'));
            $page->navbar->add(get_string('courses'));
            $page->navbar->add(get_string('createcourse', 'totara_contentmarketplace'));
            $page->navbar->add($this->plugin->displayname);
        } else {
            $page->navbar->add(get_string('contentmarketplace', 'totara_contentmarketplace'));
            $page->navbar->add($this->plugin->displayname);
            $page->navbar->add(get_string('explore', 'totara_contentmarketplace'));
        }
    }

    /**
     * @param moodle_page $page
     * @return void
     */
    private function add_manage_content_button(moodle_page $page): void {
        global $OUTPUT;

        if ($this->controller->can_manage_marketplace_plugins()) {
            $url = $this->plugin->contentmarketplace()->settings_url("content_settings");
            $search_form = $OUTPUT->single_button($url, get_string("manage_available_content", "totara_contentmarketplace"), "get");
            $page->set_button($search_form);
        }
    }
}