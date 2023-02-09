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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\form;

use coding_exception;
use mod_approval\exception\malicious_form_data_exception;
use JsonException;
use JsonSerializable;
use mod_approval\form_schema\field_type\application_editor;
use mod_approval\form_schema\form_schema;
use mod_approval\form_schema\form_schema_field;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action as application_action_model;
use mod_approval\model\application\application_submission as application_submission_model;
use mod_approval\model\form\field_conditions\condition_provider;
use mod_approval\model\form\approvalform_base;
use mod_approval\model\workflow\workflow_stage as workflow_stage_model;
use mod_approval\model\workflow\workflow_stage_formview;
use stdClass;

/**
 * Clean form data.
 */
final class form_data implements JsonSerializable {
    /** maximum recursion depth */
    private const MAX_DEPTH = 512;

    /** @var array clean form data */
    private $form_data;

    /**
     * Private constructor.
     *
     * @param array $form_data form data as an array
     */
    private function __construct(array $form_data) {
        if (!is_array($form_data)) {
            throw new malicious_form_data_exception();
        }
        $this->form_data = $form_data;
    }

    /**
     * Deserialise form data from a JSON string.
     *
     * @param string $json
     * @return self
     * @throws malicious_form_data_exception
     */
    public static function from_json(string $json): self {
        $json = rtrim($json, "\r\n");
        if (empty($json)) {
            throw new malicious_form_data_exception('no data');
        }
        if (substr($json, 0, 1) !== '{' || substr($json, -1) !== '}') {
            throw new malicious_form_data_exception();
        }
        try {
            $form_data = @json_decode($json, true, self::MAX_DEPTH, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_BIGINT_AS_STRING);
        } catch (JsonException $ex) {
            throw new malicious_form_data_exception();
        }
        return new self($form_data);
    }

    /**
     * Serialise form data into a JSON string.
     *
     * @return string
     */
    public function to_json(): string {
        return @json_encode($this->form_data, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE, self::MAX_DEPTH);
    }

    /**
     * Create an instance with empty data.
     *
     * @return self
     */
    public static function create_empty(): self {
        return new form_data([]);
    }

    /**
     * Deseriarise form data from the form_data value of a given instance.
     *
     * @param application_action_model|application_submission_model $instance
     * @return self
     * @throws malicious_form_data_exception
     */
    public static function from_instance($instance): self {
        if ($instance instanceof application_action_model) {
            $form_json = $instance->form_data;
        } else if ($instance instanceof application_submission_model) {
            $form_json = $instance->form_data;
        } else {
            throw new coding_exception('Unknown instance supplied');
        }
        return self::from_json($form_json);
    }

    /**
     * Determines whether this instance has a value (possibly a null value) for a given key.
     *
     * @param string $key
     * @return bool
     */
    public function has_value(string $key): bool {
        return array_key_exists($key, $this->form_data);
    }

    /**
     * Get the field value.
     *
     * @param string $key Key
     * @param string $default Default
     * @return string|null
     */
    public function get_value(string $key, ?string $default = null): ?string {
        // If there is a value (might be null or 0) return it.
        if ($this->has_value($key)) {
            return $this->form_data[$key];
        }
        // Otherwise return default.
        return $default;
    }

    /**
     * Filter invalid stage field_keys out of this form_data instance.
     *
     * @param workflow_stage_model $stage
     * @return self
     */
    public function filter_field_keys(workflow_stage_model $stage): self {
        $new_data = [];
        $field_keys = $stage->get_formviews()->key_by('field_key')->all(true);
        foreach ($this->form_data as $key => $value) {
            if (isset($field_keys[$key])) {
                $new_data[$key] = $value;
            }
        }
        return new form_data($new_data);
    }

    /**
     * Is form data ready for submission?
     *
     * @param workflow_stage_model $stage
     * @throws malicious_form_data_exception
     */
    public function check_readiness(workflow_stage_model $stage): void {
        /** @var workflow_stage_formview[] $field_keys */
        $field_keys = $stage->get_formviews()->key_by('field_key')->all(true);
        $json_schema = $stage->workflow_version->form_version->json_schema;
        $form_schema = form_schema::from_json($json_schema);
        // 1. Make sure form_data does not contain illegal keys
        $invalid_keys = [];
        foreach ($this->form_data as $key => $value) {
            if (!isset($field_keys[$key])) {
                $invalid_keys[] = $key;
            }
        }
        if (!empty($invalid_keys)) {
            $keys = implode(' ', $invalid_keys);
            throw new malicious_form_data_exception("Invalid field(s): {$keys}");
        }
        // 2. Filter out optional fields as well as required fields that are already filled
        foreach ($field_keys as $key => $formview) {
            // In case the formview isn't active, remove it here.
            if (!$formview->active) {
                unset($field_keys[$key]);
                continue;
            }
            $value = $this->get_value($key);
            $schema_field = $form_schema->get_field($key);
            if ($this->field_is_required($formview, $schema_field)) {
                if (!self::empty_value($value)) {
                    unset($field_keys[$key]);
                }
            } else {
                unset($field_keys[$key]);
            }
        }
        // 3. Report missing required fields
        if (!empty($field_keys)) {
            $keys = implode(' ', array_keys($field_keys));
            throw new malicious_form_data_exception("Required field(s) are not set: {$keys}");
        }
    }

    /**
     * Checks if the field is required. Based on formview value and schema_field.
     *
     * @param workflow_stage_formview $formview
     * @param form_schema_field $schema_field
     * @return bool
     */
    private function field_is_required(workflow_stage_formview $formview, form_schema_field $schema_field): bool {
        $required = $formview->required;

        // hidden fields are not required to be filled
        if ($schema_field->hidden) {
            return false;
        }
        if (is_object($schema_field->conditional) && !$this->evaluate_rule_test($schema_field->conditional)) {
            return false;
        }

        if (is_array($schema_field->rules)) {
            foreach ($schema_field->rules as $rule) {
                if (
                  (isset($rule->set->required) || isset($rule->set->hidden)) &&
                  $this->evaluate_rule_test($rule->test)
                ) {
                    if (!empty($rule->set->hidden)) {
                        return false;
                    }
                    if (!empty($rule->set->required)) {
                        $required = $rule->set->required;
                    }
                }
            }
        }

        return $required;
    }

    /**
     * Checks if the test for a rule passes.
     *
     * @param stdClass $test Test definition (key/condition/value)
     * @return bool
     */
    private function evaluate_rule_test(stdClass $test): bool {
        $field_value = $this->get_value($test->key);
        $condition = condition_provider::get_instance($test->condition);
        return $condition->assert($test->value, $field_value);
    }

    /**
     * Merge this form data with the other and return a new instance.
     *
     * @param form_data $that
     * @return form_data
     */
    public function concat(form_data $that): form_data {
        return new self(array_merge($this->form_data, $that->form_data));
    }

    /**
     * Filter out this form data by the form schema and return a new instance.
     *
     * @param form_schema $schema
     * @param application_interactor $application_interactor
     * @param boolean $set_default set default values for missing fields
     * @return form_data
     */
    public function apply_form_schema(form_schema $schema, application_interactor $application_interactor, bool $set_default = false): form_data {
        $data = [];
        foreach ($schema->get_fields() as $key => $form_schema_field) {
            // If the form_data has a value for this field, use it to set the field value.
            if ($set_default) {
                $field_value = $this->get_value($key, $form_schema_field->default);
            } else {
                $field_value = $this->get_value($key, null);
            }
            $data[$key] = $this->process_field_value_for_application($field_value, $application_interactor, $form_schema_field);
        }
        return new self($data);
    }

    /**
     * Sets up the form data for cloning.
     * Form fields can plugin here and perform specific actions if needed.
     *
     * @param application $source Source application.
     * @param application $destination Destination application.
     * @return form_data
     */
    public function clone_form_data(application $source, application $destination): form_data {
        $approval_form = approvalform_base::from_plugin_name($source->form_version->form->plugin_name);
        $form_data = $approval_form->reset_form_data_when_cloning($destination, $this);
        $form_data->clone_editor_fields($source, $destination);

        return $form_data;
    }

    /**
     * Prepares responses from editor to be saved in the database.
     * Moves files from draft to application area.
     *
     * @param application $source
     * @param application $destination
     * @return void
     */
    private function clone_editor_fields(application $source, application $destination): void {
        $schema = form_schema::from_form_version($source->form_version);
        $editor_fields = $schema->get_fields_of_type(application_editor::FIELD_TYPE);

        foreach ($editor_fields as $editor_field) {
            $form_key = $editor_field->get_field_key();

            $value = $this->get_value($form_key);
            if (empty($value)) {
                continue;
            }
            application_editor::copy_files_to_application($source, $destination);
        }
    }

    /**
     * Prepare the form fields for submission.
     * Form fields can plugin here and perform specific actions if needed.
     *
     * @param application_interactor $application_interactor
     * @param approvalform_base $approval_form
     * @return form_data
     */
    public function prepare_fields_for_submission(application_interactor $application_interactor, approvalform_base $approval_form): form_data {
        $this->prepare_editor_fields_for_submission($application_interactor, $approval_form);

        return $this;
    }

    /**
     * Prepares responses from editor to be saved in the database.
     * Moves files from draft to application area.
     *
     * @param application_interactor $application_interactor
     * @param approvalform_base $approval_form
     * @return void
     */
    private function prepare_editor_fields_for_submission(application_interactor $application_interactor, approvalform_base $approval_form): void {
        $application = $application_interactor->get_application();
        $schema = form_schema::from_form_version($application->form_version)
            ->apply_formviews($application->current_stage->formviews);

        $editor_fields = $approval_form->adjust_form_schema_for_application($application_interactor, $schema)
            ->get_fields_of_type(application_editor::FIELD_TYPE);

        $editor = new application_editor($application_interactor);

        foreach ($editor_fields as $editor_field) {
            $form_key = $editor_field->get_field_key();

            $value = $this->get_value($form_key);
            if (empty($value)) {
                continue;
            }
            // todo: TL-31479 consider validation for empty weka object. throw error if required & submit.
            $this->form_data[$form_key] = $editor->move_files_to_application_area($value);
        }
    }

    /**
     * Process the field value for the application.
     *
     * @param mixed $field_value
     * @param application_interactor $application_interactor
     * @param form_schema_field $form_schema_field
     * @return mixed
     */
    private function process_field_value_for_application($field_value, application_interactor $application_interactor, form_schema_field $form_schema_field) {
        switch ($form_schema_field->type) {
            case application_editor::FIELD_TYPE:
                $field_value = (new application_editor($application_interactor))->set_value_formats($field_value);
                break;
        }

        return $field_value;
    }

    /**
     * @param null|boolean|integer|float|string|array|stdClass $value
     * @return boolean
     */
    private static function empty_value($value): bool {
        if ($value === null) {
            return true;
        }
        if ($value === false) {
            return true;
        }
        if (is_array($value)) {
            return empty($value);
        }
        if (is_object($value)) {
            return empty(get_object_vars($value));
        }
        return (string)$value === '';
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        // Cast to object for compatibility with form_schema.
        return (object)$this->form_data;
    }
}
