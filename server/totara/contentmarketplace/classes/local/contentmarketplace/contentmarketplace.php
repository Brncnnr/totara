<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\local\contentmarketplace;

use html_writer;
use moodle_url;
use totara_contentmarketplace\plugininfo\contentmarketplace as contentmarketplace_plugininfo;

defined('MOODLE_INTERNAL') || die();

/**
 * A content marketplace definition class. All content marketplace plugins must override this.
 *
 * @package totara_contentmarketplace
 */
abstract class contentmarketplace {

    /**
     * This plugins name, must be set by the extending class.
     * @var string
     */
    public $name;

    /**
     * A translated fullname for this plugin - set during construction.
     * @var string
     */
    public $fullname;

    /**
     * A translated description for this plugin - set during construction.
     * @var string
     */
    public $descriptionhtml;

    /**
     * The plugin directory - set during construction.
     * @var string
     */
    private $plugin_directory;

    /**
     * The plugin name - set during construction.
     * @var string
     */
    private $plugin_name;

    public function __construct() {

        if (empty($this->name)) {
            throw new \coding_exception('All content marketplace must define a name.', static::class);
        }
        if (clean_param($this->name, PARAM_COMPONENT) !== $this->name) {
            // This is a coding exception, but its required for security, we use this in directory paths, and as part of a
            // frankenstyle name. We use PARAM_COMPONENT as its stricter than PARAM_SAFEDIR.
            throw new \coding_exception('The content marketplace name is not safe.', $this->name);
        }

        $this->plugin_directory = \core_component::get_plugin_directory('contentmarketplace', $this->name);
        if (empty($this->plugin_directory)) {
            throw new \coding_exception('Invalid marketplace name given, it needs to match plugin name.', $this->name);
        }
        $this->plugin_name = 'contentmarketplace_'.$this->name;

        $this->descriptionhtml = $this->get_description_html();
        $this->fullname = get_string('pluginname', $this->get_plugin_name());
    }

    /**
     * Returns the absolute directory path for content marketplace instance
     *
     * @return string
     */
    final public function get_plugin_directory() {
        return $this->plugin_directory;
    }

    /**
     * Returns the plugins frankenstyle name, e.g. contentmarketplace_goone
     *
     * @return string
     */
    final public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Returns the URL for the plugin.
     *
     * @return string
     */
    abstract public function url();

    /**
     * Returns the path to a page used to create the course(es), relative to the site root.
     *
     * @return string
     */
     abstract public function course_create_page();

    /**
     * Returns a HTML snippet with the content marketplace logo image.
     *
     * @param integer $width Width of the image (100px by default)
     * @return string Logo HTML
     */
    public function get_logo_html($width = 100) {
        return html_writer::img(
            $this->get_padded_logo_url()->out(false),
            $this->get_logo_alt_text(),
            [
                'title' => $this->fullname,
                'width' => $width,
            ]
        );
    }

    /**
     * Get the HTML to display for the description of this marketplace.
     *
     * @return string
     */
    public function get_description_html(): string {
        return get_string('plugin_description_html', $this->get_plugin_name());
    }

    /**
     * Returns a HTML snippet that enables user to go through the content marketplace
     * setup process.
     *
     * @param string $label
     * @return string HTML snippet with the setup code.
     */
    public function get_setup_html($label) {
        if (!contentmarketplace_plugininfo::plugin($this->name)->has_never_been_enabled()) {
            // Plugin has been configured in the past, so don't need to show the initial enable link.
            return '';
        }

        $enable_url = new moodle_url('/totara/contentmarketplace/marketplaces.php', [
            'id' => $this->name,
            'enable' => 1,
            'sesskey' => sesskey(),
        ]);

        return html_writer::link(
            $enable_url,
            $label,
            [
                'class' => 'tcm-enable',
                'data-action' => 'enable',
                'data-marketplace' => $this->name,
                'title' => get_string('enable_plugin', 'totara_contentmarketplace', $this->fullname),
            ]
        );
    }

    /**
     * Returns URL for given settings tab.
     *
     * @param $tab
     * @return string
     * @throws \moodle_exception
     */
    public function settings_url($tab = null) {
        return new \moodle_url(
            "/totara/contentmarketplace/marketplaces.php",
            array(
                "id" => $this->name,
                "tab" => $tab
            )
        );
    }

    /**
     * Returns the source for the given resource for use in file record.
     *
     * @return string
     */
    public function get_source($id) {
        return "content-marketplace://{$this->name}/{$id}";
    }

    /**
     * Get the logo for this content marketplace.
     *
     * @return moodle_url
     */
    public function get_logo_url(): moodle_url {
        global $OUTPUT;
        return $OUTPUT->image_url('logo', $this->get_plugin_name());
    }

    /**
     * Get the logo for this marketplace, in a square ratio with padding.
     *
     * @return moodle_url
     */
    public function get_padded_logo_url(): moodle_url {
        global $OUTPUT;
        return $OUTPUT->image_url('logo_padded', $this->get_plugin_name());
    }

    /**
     * Get the alt text to use for when displaying the logo.
     *
     * @return string
     */
    public function get_logo_alt_text(): string {
        return get_string(
            'logo_alt',
            'totara_contentmarketplace',
            get_string('pluginname', $this->get_plugin_name())
        );
    }

}
