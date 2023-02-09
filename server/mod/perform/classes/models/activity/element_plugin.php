<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use coding_exception;
use core_component;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\helpers\child_element_config;
use mod_perform\models\activity\helpers\displays_responses;
use mod_perform\models\activity\helpers\element_clone_helper;
use mod_perform\models\activity\helpers\element_usage;
use mod_perform\models\response\section_element_response;
use mod_perform\rb\helper\element_plugin_response_report_builder;

/**
 * Class element_plugin
 *
 * Base class for defining a type of element, including its specific behaviour.
 *
 * @package mod_perform\models\activity
 */
abstract class element_plugin {

    public const GROUP_QUESTION = 1;
    public const GROUP_OTHER = 2;

    /**
     * Load by plugin name
     *
     * @param string $plugin_name
     *
     * @return static
     */
    final public static function load_by_plugin(string $plugin_name): self {
        $plugin_class = "performelement_{$plugin_name}\\{$plugin_name}";
        if (!is_subclass_of($plugin_class, self::class)) {
            throw new coding_exception('Tried to load an unknown element plugin: ' . $plugin_class);
        }
        return new $plugin_class();
    }

    /**
     * Get all element plugins. Optionally filter to only respondable or non-respondable elements.
     *
     * Returns array of elements, keyed by plugin_name, value is instance of element model.
     *
     * @param bool $get_respondable
     * @param bool $get_non_respondable
     * @return element_plugin[]
     */
    final public static function get_element_plugins(bool $get_respondable = true, bool $get_non_respondable = true): array {
        $elements = core_component::get_plugin_list('performelement');

        $out = [];
        foreach ($elements as $plugin_name => $plugin_path) {
            $element_plugin = self::load_by_plugin($plugin_name);

            if ($get_respondable && $element_plugin->get_is_respondable()) {
                $out[$plugin_name] = $element_plugin;
            }
            if ($get_non_respondable && !$element_plugin->get_is_respondable()) {
                $out[$plugin_name] = $element_plugin;
            }
        }
        return $out;
    }

    /**
     *
     * @return element_plugin[]|displays_responses[]
     */
    final public static function get_derived_responses_plugins(): array {
        $elements = static::get_element_plugins();

        return array_filter($elements, static function (self $element_plugin) {
            return $element_plugin instanceof derived_responses_element_plugin;
        });
    }

    /**
     *
     * @return element_plugin[]|displays_responses[]
     */
    final public static function get_displays_responses_plugins(): array {
        $elements = static::get_element_plugins();

        return array_filter($elements, static function (self $element_plugin) {
            return $element_plugin instanceof displays_responses;
        });
    }

    /**
     *
     * @return element_plugin[]|displays_responses[]
     */
    final public static function get_does_not_display_responses_plugins(): array {
        $elements = static::get_element_plugins();

        return array_filter($elements, static function (self $element_plugin) {
            return !$element_plugin instanceof displays_responses;
        });
    }

    /**
     * Get all aggregatable element plugins.
     *
     * Returns array of elements, keyed by plugin_name, value is instance of element model.
     *
     * @return element_plugin[]
     */
    final public static function get_aggregatable_element_plugins(): array {
        $elements = static::get_element_plugins();

        return array_filter($elements, static function (self $element_plugin) {
            return $element_plugin->get_is_aggregatable();
        });
    }

    /**
     * Get plugin name, used as a key
     *
     * @return string
     */
    final public static function get_plugin_name(): string {
        return explode('\\', static::class)[1];
    }

    /**
     * Get name
     *
     * @return string
     */
    final public function get_name(): string {
        return get_string('name', 'performelement_' . $this->get_plugin_name());
    }

    /**
     * This method return element's admin form vue component name
     *
     * This function is going to be deprecated. Use element_plugin::get_admin_edit_component() instead
     *
     * @return string
     * @deprecated since Totara 13.2
     */
    public function get_admin_form_component(): string {
        debugging(
            '\mod_perform\models\activity\element_plugin::get_admin_form_component() is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );

        return $this->get_component_path('ElementAdminForm');
    }

    /**
     * This method return element's admin form vue component name
     *
     * @return string
     */
    public function get_admin_edit_component(): string {
        return $this->get_component_path('AdminEdit');
    }

    /**
     * This method return element's admin display vue component name
     *
     * This function is going to be deprecated. Use element_plugin::get_admin_view_component() instead
     *
     * @return string
     * @deprecated since Totara 13.2
     */
    public function get_admin_display_component(): string {
        debugging(
            '\mod_perform\models\activity\element_plugin::get_admin_display_component() is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );

        return $this->get_component_path('ElementAdminDisplay');
    }

    /**
     * This method return element's admin view vue component name
     *
     * @return string
     */
    public function get_admin_view_component(): string {
        return $this->get_component_path('AdminView');
    }

    /**
     * This method return element's admin read only display vue component name
     *
     * This function is going to be deprecated. Use element_plugin::get_admin_summary_component() instead
     *
     * @return string
     * @deprecated since Totara 13.2
     */
    public function get_admin_read_only_display_component(): string {
        debugging(
            '\mod_perform\models\activity\element_plugin::get_admin_read_only_display_component() is deprecated and should no longer be used.'
            . ' There is no alternative.',
            DEBUG_DEVELOPER
        );

        return $this->get_component_path('ElementAdminReadOnlyDisplay');
    }

    /**
     * This method return element's admin read only display vue component name
     *
     * @return string
     */
    public function get_admin_summary_component(): string {
        return $this->get_component_path('AdminSummary');
    }

    /**
     * This method return element's print vue component name
     *
     * @return string
     */
    public function get_participant_print_component(): string {
        return $this->get_component_path('ParticipantPrint');
    }

    /**
     * This method return element's user form vue component name
     * @return string
     */
    public function get_participant_form_component(): string {
        return $this->get_component_path('ParticipantForm');
    }

    /**
     * This method return element's user form vue component name
     * @return string
     * @deprecated since Totara 13.2
     */
    public function get_participant_response_component(): string {
        debugging(
            '\mod_perform\models\activity\element_plugin::get_participant_response_component() is deprecated and should no longer be used.'
            . 'Only classes expending \mod_perform\models\activity\respondable_element_plugin should implement this method',
            DEBUG_DEVELOPER
        );

        return $this->get_component_path('ElementParticipantResponse');
    }

    /**
     * Modify json data to add extra information to it.
     *
     * @param element_entity $element
     * @return string|null
     */
    public function process_data(element_entity $element): ?string {
        return $element->data;
    }

    /**
     * Calculate the full path to a tui component related to this element plugin.
     *
     * @param string $suffix
     * @return string
     */
    protected function get_component_path(string $suffix): string {
        return 'performelement_' .
            $this->get_plugin_name() .
            '/components/' .
            $this->get_component_name_prefix() .
            $suffix;
    }

    /**
     * This method return element's default component name prefix
     *
     * @return string
     */
    protected function get_component_name_prefix(): string {
        $prefix = '';
        foreach (explode('_', self::get_plugin_name()) as $name) {
            $prefix .= ucfirst($name);
        }

        return $prefix;
    }

    /**
     * When an element is about to be saved in a section, validate that the configuration of the element
     * meets any requirements of the element plugin
     *
     * If a problem is discovered, throw an exception.
     *
     * @param element_entity $element
     */
    public function validate_element(element_entity $element): void {
    }

    /**
     * When an element is about to be saved in a section, clean the data and remove
     * everything which shouldn't be in there
     *
     * @param element_entity $element
     */
    public function clean_element(element_entity $element): void {
    }

    /**
     * Do any required actions after the element has been created.
     *
     * @param element $element
     */
    public function post_create(element $element): void {
        // Can be overridden if necessary.
    }

    /**
     * Do any required actions after the element configuration has been updated.
     *
     * @param element $element
     */
    public function post_update(element $element): void {
        // Can be overridden if necessary.
        $this->post_create($element);
    }

    /**
     * Can the user respond to this element.
     *
     * @return bool
     */
    public function get_is_respondable(): bool {
        return $this instanceof respondable_element_plugin;
    }

    /**
     * Can this element be used in aggregation.
     *
     * @return bool
     */
    public function get_is_aggregatable(): bool {
        return false;
    }

    /**
     * Get clone helper
     *
     * @return element_clone_helper|null
     */
    public function get_clone_helper(): ?element_clone_helper {
        return null;
    }

    /**
     * return true if element has title
     *
     * @return bool
     */
    abstract public function has_title(): bool;

    /**
     * Return Title Text
     *
     * @return string
     */
    abstract public function get_title_text(): string;

    /**
     * Return Title Help Text
     *
     * @return string
     */
    public function get_title_help_text(): ?string {
        return null;
    }

    /**
     * return true if element title is required
     *
     * @return bool
     */
    abstract public function is_title_required(): bool;

    /**
     * Return if element plugin is a Question element group or Other element group
     *
     * @return int
     */
    abstract public function get_group(): int;

    /**
     * Return position key to sort element plugin in the list
     *
     * @return int
     */
    abstract public function get_sortorder(): int;

    /**
     * Return the aggregatable value
     * Aggregatable plugins should override
     *
     * @param string|null $encoded_response_data
     * @param string|null $encoded_element_data
     * @return float
     */
    public function get_aggregatable_value(?string $encoded_response_data, ?string $encoded_element_data): ?float {
        if (!$this instanceof displays_responses && !$this->get_is_aggregatable()) {
            return null;
        }

        $decoded_response = $this->decode_response($encoded_response_data, $encoded_element_data);
        if ($decoded_response === null) {
            return null;
        }

        return (int)$decoded_response;
    }

    /**
     * Builds the response data.
     *
     * @param section_element_response $section_element_response
     *
     * @return string|null
     */
    public function build_response_data(section_element_response $section_element_response): ?string {
        return $section_element_response->raw_response_data;
    }

    /**
     * Builds the response data formatted lines.
     *
     * @param section_element_response $section_element_response
     *
     * @return string|null
     */
    public function build_response_data_formatted_lines(section_element_response $section_element_response): ?string {
        return $section_element_response->raw_response_data;
    }

    /**
     * Child element configuration.
     *
     * @return child_element_config
     */
    public function get_child_element_config(): child_element_config {
        return new child_element_config();
    }

    /**
     * Can return an optional report builder helper to add or manipulate data on the response report.
     *
     * A plugin can override this method and provide its own helper.
     *
     * @return element_plugin_response_report_builder|null
     */
    public function get_response_report_builder_helper(): ?element_plugin_response_report_builder {
        return null;
    }

    /**
     * Any extra static data an element plugin wants to provide i.e. a fixed set of configuration options.
     *
     * @return array
     */
    public function get_extra_config_data(): array {
        return [];
    }

    /**
     * Configuration of where the element can be used.
     *
     * @return element_usage
     */
    public function get_element_usage(): element_usage {
        return new element_usage();
    }

    /**
     * Is this element enabled?
     * 
     * @return bool
     */
    public function is_enabled(): bool {
        return true;
    }
}
