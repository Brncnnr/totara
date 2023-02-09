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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\form;

use mod_approval\exception\model_exception;
use mod_approval\form_schema\field_type\application_editor;
use mod_approval\form_schema\form_schema;
use mod_approval\form_schema\form_schema_field;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\application;
use mod_approval\model\status;
use mod_approval\plugininfo\approvalform;
use totara_core\path;

/**
 * Base class for approvalform sub-plugins
 *
 * Extend as \approvalform_<plugin_name>\approvalform_<plugin_name>.
 */
class approvalform_base {
    /**
     * @var approvalform
     */
    private $plugininfo;

    /**
     * @var form_schema
     */
    private $form_schema;

    /**
     * Private constructor.
     *
     * @param approvalform $plugininfo
     */
    private function __construct(approvalform $plugininfo) {
        $this->plugininfo = $plugininfo;
        $this->form_schema = form_schema::from_json($this->get_form_schema_from_disk());
    }

    /**
     * Factory to instantiate approvalform plugin class.
     *
     * @param string $plugin_name
     * @return static
     */
    public static function from_plugin_name(string $plugin_name): self {
        $plugininfo = approvalform::from_plugin_name($plugin_name);
        if (is_null($plugininfo)) {
            throw new model_exception("'{$plugin_name}' form plugin not found");
        }
        $classname = '\\approvalform_' . $plugin_name . '\\' . $plugin_name;
        if (!class_exists($classname)) {
            throw new \coding_exception("'approvalform_{$plugin_name}' class not implemented", "'{$classname}' does not exist");
        }

        return new $classname($plugininfo);
    }

    /**
     * Getter for plugininfo properties and accessors.
     *
     * @param string $property
     * @return mixed|string|null
     */
    public function __get($property) {
        if (isset($this->plugininfo->$property)) {
            return $this->plugininfo->$property;
        } else if (is_null($property)) {
            return null;
        } else if (method_exists($this, 'get_' . $property)) {
            return $this->{'get_' . $property}();
        }
        throw new \coding_exception('approvalform property does not exist');
    }

    /**
     * Get the raw plugin form schema JSON.
     *
     * @return string
     */
    private function get_form_schema_from_disk(): string {
        // Load JSON schema from disk.
        $schema_path = new path($this->rootdir,'form.json');
        if (!$schema_path->exists() || !$schema_path->is_file() || !$schema_path->is_readable()) {
            throw new \coding_exception('unable to load approvalform JSON schema file');
        }
        return file_get_contents($schema_path->to_native_string());
    }

    /**
     * Loops through top-level fields and section fields to resolve help text file references.
     */
    private function resolve_schema_help_file_references(): void {
        $this->resolve_schema_help_file_references_in_fields($this->form_schema->get_fields());
        foreach ($this->form_schema->get_sections() as $sx => $section) {
            $this->resolve_schema_help_file_references_in_fields($this->form_schema->get_section_fields($section->get_key()));
        }
    }

    /**
     * Loops through array of fields and replaces any help text file references with contents of file.
     *
     * @param form_schema_field[] $fields
     */
    private function resolve_schema_help_file_references_in_fields(array $fields): void {
        foreach ($fields as $fx => $field) {
            /* @var form_schema_field $field */
            if (!empty($field->help_html) && substr($field->help_html, 0, 4) == 'key:') {
                $string = substr($field->help_html, 4);
                if ($string) {
                    $help_text = format_text(get_string($string, $this->plugininfo->component));
                    $this->form_schema->set_field_help_html($field->get_field_key(), $help_text);
                }
            }
        }
    }

    /**
     * Get the plugin form_schema as a class
     *
     * @return form_schema
     */
    public function get_form_schema(): form_schema {
        $this->resolve_schema_help_file_references();
        return $this->form_schema;
    }

    /**
     * Get the plugin form_schema as json
     *
     * @return string JSON
     */
    public function get_form_schema_json(): string {
        $this->resolve_schema_help_file_references();
        return $this->form_schema->to_json();
    }

    /**
     * Get the form schema version.
     *
     * @return null|string
     */
    public function get_form_version(): ?string {
        return $this->form_schema->get_version();
    }

    /**
     * Reset form data when cloning.
     *
     * @param application $application
     * @param form_data $form_data
     * @return form_data $form_data
     */
    public function reset_form_data_when_cloning(application $application, form_data $form_data): form_data {
        return $form_data;
    }

    /**
     * Is this plugin enabled?
     *
     * @return bool|null
     */
    public function is_enabled(): ?bool {
        return $this->plugininfo->is_enabled();
    }

    /**
     * Get the status to use for the initial form_version when creating an instance of this form.
     *
     * ACTIVE assumes that the form schema is being loaded from a static file; override this
     * method to use DRAFT if the plugin's form schema is dynamic or not ready to use when the
     * form model is created.
     *
     * @return int
     */
    public function default_version_status(): int {
        return status::ACTIVE;
    }

    /**
     * Allows the approvalform plugin to modify or recreate a form_schema based on an application instance.
     *
     * @param application_interactor $application_interactor
     * @param form_schema $form_schema
     *
     * @return form_schema
     */
    public function adjust_form_schema_for_application(application_interactor $application_interactor, form_schema $form_schema): form_schema {
        $this->add_meta_to_editor_fields($application_interactor, $form_schema);

        return $form_schema;
    }

    /**
     * Add required meta properties to fields with type: editor
     *
     * @param application_interactor $application_interactor
     * @param form_schema $form_schema
     */
    private function add_meta_to_editor_fields(application_interactor $application_interactor, form_schema $form_schema): void {
        $editor_fields = $form_schema->get_fields_of_type(application_editor::FIELD_TYPE);

        if (empty($editor_fields)) {
            return;
        }
        $application_editor = new application_editor($application_interactor);

        /** @var form_schema_field $editor_field */
        foreach ($editor_fields as $editor_field) {
            $form_schema->set_field_meta($editor_field->get_field_key(), $application_editor->get_editor_meta());
        }
    }

    /**
     * Allows the approvalform plugin to observe and perform specific actions based on an application instance.
     *
     * Note that the data may be incoming (from a mutation/post) or outgoing (to a query/form)
     *
     * @param application $application
     * @param form_data $form_data
     * @return void
     */
    public function observe_form_data_for_application(application $application, form_data $form_data): void {
    }
}