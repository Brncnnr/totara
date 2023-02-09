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

namespace mod_approval\form_schema;

use core\orm\collection;
use JsonSerializable;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\workflow_stage_formview;
use stdClass;

/**
 * Approval workflows form plugin schema parser and utility.
 *
 * TODO: This parser assumes schema is correct and complete; totara JSON schema validator needs to be
 *   implemented before inclusion in Totara 16.
 *
 * @package mod_approval\form_schema
 */
class form_schema implements JsonSerializable {

    /** maximum recursion depth */
    const MAX_DEPTH = 32;

    /**
     * Non respondable field types in form schema.
     *
     * @var array
     */
    public const NON_RESPONDABLE_FIELD_TYPES = ['label', 'total'];

    /** options for json_decode() */
    const JSON_DECODE_OPTIONS = JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_BIGINT_AS_STRING;

    /** options for json_encode() */
    const JSON_ENCODE_OPTIONS = JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES;

    /** @var stdClass form schema as a stdClass object */
    private $schema;

    /** @var bool flag used by parser */
    private $parsed = false;

    /** @var array map of field_key to section number */
    private $field_sections = [];

    /** @var array collection of fields in the form, indexed by field_key */
    private $fields = [];

    /** @var array collection of single- or multi-choice answer values, indexed by field_key */
    private $field_choices = [];

    /** @var array collection of sections in the form (not counting top level), indexed by section key */
    private $sections = [];

    /** @var array map of section key to section number */
    private $section_keys = [];

    /**
     * Private form_schema constructor; use a from_x() method to instantiate.
     *
     * @param stdClass $decoded json schema as an object
     */
    private function __construct(stdClass $decoded) {
        $this->schema = $decoded;
    }

    /**
     * Parse a field schema for useful info.
     *
     * @param stdClass $field
     * @param string $section_index
     */
    private function parse_field(stdClass $field, string $section_index): void {
        $field_key = $field->key;

        // Create a form_schema_field object
        $index = $section_index . '/' . $field_key;
        $form_schema_field = new form_schema_field($index, $field);
        $this->fields[$field_key] = $form_schema_field;

        // Also find choice labels
        if (!empty($field->attrs->choices)) {
            $this->field_choices[$field_key] = [];
            foreach ($field->attrs->choices as $cx => $choice) {
                if (is_null($choice->key)) {
                    continue;
                }
                $this->field_choices[$field_key][$choice->key] = $choice->label;
            }
        }
        // Also find choice labels hidden in rule definitions -- note these are only set if they don't exist,
        //   conditional labels will need to be supported using a different mechanism.
        if (!empty($field->rules)) {
            foreach ($field->rules as $rx => $rule) {
                if (!empty($rule->set->attrs->choices)) {
                    foreach ($rule->set->attrs->choices as $cx => $choice) {
                        if (is_null($choice->key)) {
                            continue;
                        }
                        if (empty($this->field_choices[$field_key][$choice->key])) {
                            $this->field_choices[$field_key][$choice->key] = $choice->label;
                        }
                    }
                }
            }
        }
    }

    /**
     * Parse the internal schema into fields, choices, and sections.
     */
    private function parse_schema(): void {
        if (!$this->parsed) {
            // Reset all indexes.
            $this->field_sections = [];
            $this->fields = [];
            $this->field_choices = [];
            $this->sections = [];
            $this->section_keys = [];

            // Section top (top level)
            foreach ($this->schema->fields ?? [] as $fx => $field) {
                $this->field_sections[$field->key] = 'top';
                $this->parse_field($field, 'top');
            }
            // For each section
            foreach ($this->schema->sections ?? [] as $sx => $section) {
                $line = $section->line ?? null;
                $this->sections[$section->key] = new form_schema_section($section->key, $section->label, $line);
                $this->section_keys[$section->key] = $sx;
                foreach ($section->fields ?? [] as $fx => $field) {
                    $this->field_sections[$field->key] = $sx;
                    $this->parse_field($field, $sx);
                }
            }
        }
        $this->parsed = true;
    }

    /**
     * Load the schema from a form_version model object.
     *
     * @param form_version $form_version
     * @return form_schema
     */
    public static function from_form_version(form_version $form_version): self {
        $decoded = json_decode($form_version->json_schema, false, self::MAX_DEPTH, self::JSON_DECODE_OPTIONS);
        return new self($decoded);
    }

    /**
     * Load the schema from a JSON string.
     *
     * @param string $json_schema
     * @return form_schema
     */
    public static function from_json(string $json_schema): self {
        $decoded = json_decode($json_schema, false, self::MAX_DEPTH, self::JSON_DECODE_OPTIONS);
        return new self($decoded);
    }

    /**
     * Create an instance based on the form schema without sections and fields.
     *
     * @param form_schema $form_schema
     * @return self
     */
    public static function create_empty(form_schema $form_schema): self {
        return new self($form_schema->empty_clone_schema());
    }

    /**
     * Get the version from the schema if present.
     *
     * @return null|string
     */
    public function get_version(): ?string {
        return $this->schema->version ?? null;
    }

    /**
     * Get all fields in the form schema, indexed by field_key.
     *
     * @return form_schema_field[]
     */
    public function get_fields(): array {
        $this->parse_schema();
        return $this->fields;
    }

    /**
     * Get all sections in the form schema, indexed by section key.
     *
     * @return form_schema_section[]
     */
    public function get_sections(): array {
        $this->parse_schema();
        return $this->sections;
    }

    /**
     * Get all top-level fields, that is, fields which are not in a section, indexed by field_key.
     *
     * @return form_schema_field[]
     */
    public function get_top_level_fields(): array {
        $this->parse_schema();
        $fields = [];
        foreach ($this->field_sections as $field_key => $number) {
            if ($number === 'top') {
                $fields[$field_key] = $this->fields[$field_key];
            }
        }
        return $fields;
    }

    /**
     * Get all fields in a section, indexed by field_key.
     *
     * @param string $key
     * @return form_schema_field[]
     */
    public function get_section_fields(string $key): array {
        $this->parse_schema();
        $fields = [];
        $section_number = $this->section_keys[$key] ?? null;
        foreach ($this->field_sections as $field_key => $number) {
            if ($number === $section_number) {
                $fields[$field_key] = $this->fields[$field_key];
            }
        }
        return $fields;
    }

    /**
     * Does this schema have this field?
     *
     * @param string $key
     * @return bool
     */
    public function has_field(string $key): bool {
        $this->parse_schema();
        return array_key_exists($key, $this->fields);
    }

    /**
     * Get field matching field_key.
     *
     * @param string $key
     * @return null|form_schema_field
     */
    public function get_field(string $key): ?form_schema_field {
        $this->parse_schema();
        return $this->fields[$key] ?? null;
    }

    /**
     * Get fields that have type specified.
     *
     * @param string $field_type
     * @return form_schema_field[]
     */
    public function get_fields_of_type(string $field_type): array {
        /** @var form_schema_field $field */
        return array_filter($this->get_fields(), function ($field) use ($field_type) {
            return $field->type === $field_type;
        });
    }

    /**
     * Get the section that a field is in, or null for top level / unspecified.
     *
     * @param string $key
     * @return null|form_schema_section
     */
    public function get_field_section(string $key): ?form_schema_section {
        $this->parse_schema();
        $section_ix = $this->field_sections[$key] ?? 'top';
        if ($section_ix === 'top') {
            return null;
        } else {
            $section_key = array_search($section_ix, $this->section_keys);
            return $this->sections[$section_key] ?? null;
        }
    }

    /**
     * Encodes the schema and returns as JSON string.
     *
     * @return string
     */
    public function to_json(): string {
        return json_encode($this->schema, self::JSON_ENCODE_OPTIONS, self::MAX_DEPTH);
    }

    /**
     * Clones the internal schema object without sections and fields.
     *
     * @return stdClass
     */
    private function empty_clone_schema(): stdClass {
        $schema = new stdClass();
        // Port top-level properties over to the new schema.
        foreach ($this->schema as $key => $value) {
            if ($key == 'fields' || $key == 'sections') {
                $schema->{$key} = [];
            } else {
                $schema->{$key} = $value;
            }
        }
        return $schema;
    }

    /**
     * Correctly adds a stdClass field to a stdClass schema.
     *
     * @param stdClass $field
     * @param stdClass $schema by reference
     */
    private function add_field_to_schema(stdClass $field, stdClass &$schema): void {
        $schema_section = $this->get_field_section($field->key);
        if (is_null($schema_section)) {
            $schema->fields[] = $field;
        } else {
            $section = $schema_section->to_stdClass();
            if (array_search($section->key, array_column($schema->sections, 'key')) === false) {
                $section->fields = [];
                $schema->sections[] = $section;
            }
            $sx = array_search($section->key, array_column($schema->sections, 'key'));
            $schema->sections[$sx]->fields[] = $field;
        }
    }

    /**
     * Rebuilds schema based on formviews collection
     *
     * @param collection|workflow_stage_formview[] $formviews
     * @return form_schema
     */
    public function apply_formviews(collection $formviews): self {
        $this->parse_schema();
        $schema = $this->empty_clone_schema();
        // Build the fields and sections from formviews.
        foreach ($formviews as $formview) {
            $this->apply_formview($formview, $schema);
        }
        // Return a new form_schema instance.
        return new self($schema);
    }

    /**
     * Applies a formview's properties to the current schema.
     *
     * @param workflow_stage_formview $formview
     * @param stdClass $schema by reference
     */
    private function apply_formview(workflow_stage_formview $formview, stdClass &$schema): void {
        $schema_field = $this->get_field($formview->field_key);
        if (!$schema_field) {
            return;
        }
        $field = $schema_field->to_stdClass();
        $field->required = $formview->required;
        $field->disabled = $formview->disabled;
        if (!empty($formview->default_value)) {
            $field->default = $formview->default_value;
        }
        $this->add_field_to_schema($field, $schema);
    }

    /**
     * Merge this schema with the other and return a new instance.
     *
     * @param form_schema $that
     * @return form_schema
     */
    public function concat(form_schema $that): form_schema {
        $accumulation = clone $this->schema;
        if (!isset($accumulation->fields)) {
            $accumulation->fields = [];
        }
        if (!isset($accumulation->sections)) {
            $accumulation->sections = [];
        }
        $add_or_update_field = function (&$acc_fields, $src_field) {
            foreach ($acc_fields as &$acc_field) {
                if ($acc_field->key === $src_field->key) {
                    $acc_field = $src_field;
                    return;
                }
            }
            $acc_fields[] = $src_field;
        };
        $add_or_update_section = function (&$acc_sections, $src_section) use ($add_or_update_field) {
            foreach ($acc_sections as &$acc_section) {
                if ($acc_section->key === $src_section->key) {
                    if (!isset($acc_section->fields)) {
                        $acc_section->fields = [];
                    }
                    foreach ($src_section->fields ?? [] as $src_field) {
                        $add_or_update_field($acc_section->fields, $src_field);
                    }
                    return;
                }
            }
            $acc_sections[] = $src_section;
        };
        foreach ($that->schema->fields ?? [] as $src_field) {
            $add_or_update_field($accumulation->fields, $src_field);
        }
        foreach ($that->schema->sections ?? [] as $src_section) {
            $add_or_update_section($accumulation->sections, $src_section);
        }
        // Return a new form_schema instance.
        return new self($accumulation);
    }

    /**
     * Sets a property of a field on the internal schema, and marks the schema as unparsed.
     *
     * @param string $field_key
     * @param string $property
     * @param mixed $value
     */
    private function set_field_property(string $field_key, string $property, $value): void {
        if ($this->has_field($field_key)) {
            $index = $this->get_field($field_key)->get_section_index();
            if ($index == 'top') {
                foreach ($this->schema->fields as $fx => $field) {
                    if ($field->key == $field_key) {
                        $this->schema->fields[$fx]->{$property} = $value;
                        $this->parsed = false;
                        break;
                    }
                }
            } else {
                foreach ($this->schema->sections[$index]->fields as $fx => $field) {
                    if ($field->key == $field_key) {
                        $this->schema->sections[$index]->fields[$fx]->{$property} = $value;
                        $this->parsed = false;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Set a schema field's default value.
     *
     * @param string $key
     * @param string $value
     */
    public function set_field_default(string $key, string $value): void {
        $this->set_field_property($key, 'default', $value);
    }

    /**
     * Set a schema field disabled (or not).
     *
     * @param string $key
     * @param bool $value
     */
    public function set_field_disabled(string $key, bool $value): void {
        $this->set_field_property($key, 'disabled', $value);
    }

    /**
     * Set schema field help text
     *
     * @param string $key
     * @param string $value
     */
    public function set_field_help(string $key, string $value): void {
        $this->set_field_property($key, 'help', $value);
    }

    /**
     *
     * Set schema field help_html text
     *
     * @param string $key
     * @param string $value
     */
    public function set_field_help_html(string $key, string $value): void {
        $this->set_field_property($key, 'help_html', $value);
    }

    /**
     *
     * Set schema field meta
     *
     * @param string $key
     * @param array $value
     */
    public function set_field_meta(string $key, array $value): void {
        $this->set_field_property($key, 'meta', $value);
    }

    /**
     * Set schema field validation
     *
     * @param string $key
     * @param array $value
     */
    public function set_field_validations(string $key, array $value): void {
        $this->set_field_property($key, 'validations', $value);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return $this->schema;
    }
}