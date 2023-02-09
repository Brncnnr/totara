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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\model\workflow\stage_feature;

use coding_exception;
use mod_approval\entity\workflow\workflow_stage_formview as workflow_stage_formview_entity;
use mod_approval\exception\model_exception;
use mod_approval\form_schema\form_schema;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\stage_type\waiting;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;

/**
 * Formview feature:
 *
 * This class handles configuring formviews for a workflow stage.
 */
class formviews extends base {

    /**
     * Editable visibility enum.
     *
     * @var string
     */
    public const EDITABLE = 'EDITABLE';

    /**
     * Editable and required visibility enum.
     *
     * @var string
     */
    public const EDITABLE_AND_REQUIRED = 'EDITABLE_AND_REQUIRED';

    /**
     * Read only visibility enum.
     *
     * @var string
     */
    public const READ_ONLY = 'READ_ONLY';

    /**
     * Hidden visibility enum.
     *
     * @var string
     */
    public const HIDDEN = 'HIDDEN';

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('formviews', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public static function get_enum(): string {
        return 'FORMVIEWS';
    }

    /**
     * @inheritDoc
     */
    public static function get_sort_order(): int {
        return 10;
    }

    /**
     * Resolve required & disabled boolean to visibility enum.
     *
     * @param bool $required
     * @param bool $disabled
     * @return string
     */
    public static function resolve_visibility_enum(bool $required, bool $disabled): string {
        switch (true) {
            case ($required === false && $disabled === false):
                return self::EDITABLE;
            case ($required === true && $disabled === false):
                return self::EDITABLE_AND_REQUIRED;
            case ($required === false && $disabled === true):
                return self::READ_ONLY;
            default:
                // Note: hidden fields are deleted.
                throw new coding_exception("Can not resolve visibility enum, unknown configuration");
        }
    }

    /**
     * Configure the form field with the field_key to a specific visibility for this stage.
     * Underneath this method creates, update and deletes formview entities as required.
     *
     * @param string $field_key Form field key from JSON schema
     * @param string $visibility_enum Enum representing the visibility of the form field
     * @return void
     */
    public function configure(string $field_key, string $visibility_enum) {
        if (!$this->stage->active) {
            throw new model_exception("Workflow stage must be active");
        }

        if (!$this->stage->workflow_version->is_draft()) {
            throw new model_exception("Can not configure formviews for non-draft workflow");
        }
        $this->validate_field_key($field_key);

        $stage_formviews = $this->stage->formviews->key_by('field_key');

        /** @var workflow_stage_formview $formview */
        $existing_formview = $stage_formviews[$field_key];

        // Updates existing formviews or creates a new one.
        switch ($visibility_enum) {
            case self::EDITABLE:
                $existing_formview
                    ? $this->update($field_key, false, false)
                    : $this->add($field_key, false, false);
                break;
            case self::EDITABLE_AND_REQUIRED:
                $existing_formview
                    ? $this->update($field_key, true, false)
                    : $this->add($field_key, true, false);
                break;
            case self::READ_ONLY:
                $existing_formview
                    ? $this->update($field_key, false, true)
                    : $this->add($field_key, false, true);
                break;
            case self::HIDDEN:
                $this->delete($field_key);
                break;
            default:
                throw new coding_exception("Unknown formview visibility");
        }
    }

    /**
     * Validate the field key if it exists in the form.
     *
     * @param string $field_key
     * @return void
     */
    private function validate_field_key(string $field_key): void {
        $schema = form_schema::from_form_version($this->stage->workflow_version->form_version);

        if (!$schema->has_field($field_key)) {
            throw new coding_exception("$field_key field key not available in schema");
        }
    }

    /**
     * Add formview to workflow stage.
     *
     * @param string $field_key Form field key from JSON schema
     * @param boolean $required Is the field required?
     * @param boolean $disabled Is the field disabled?
     * @param string|null $default_value Override the default value at this stage
     * @return workflow_stage_formview
     */
    private function add(
        string $field_key,
        bool $required,
        bool $disabled,
        ?string $default_value = null
    ): workflow_stage_formview {
        if (empty($field_key)) {
            throw new model_exception('Workflow stage form view field key cannot be empty');
        }
        if (!$this->stage->active) {
            throw new model_exception("Workflow stage must be active");
        }

        $entity = new workflow_stage_formview_entity();
        $entity->field_key = $field_key;
        $entity->workflow_stage_id = $this->stage->id;
        $entity->required = $required;
        $entity->disabled = $disabled;
        $entity->default_value = $default_value;
        $entity->active = true;
        $entity->save();

        return workflow_stage_formview::load_by_entity($entity);
    }

    /**
     * Update settings of a formview.
     *
     * @param string $field_key
     * @param bool $required
     * @param bool $disabled
     * @return void
     */
    private function update(string $field_key, bool $required, bool $disabled): void {
        workflow_stage_formview_entity::repository()
            ->where('field_key', $field_key)
            ->where('workflow_stage_id', $this->stage->id)
            ->update([
                'required' => $required,
                'disabled' => $disabled,
            ]);
    }

    /**
     * Delete formview from workflow stage.
     *
     * @param string $field_key
     * @return void
     */
    private function delete(string $field_key): void {
        workflow_stage_formview_entity::repository()
            ->where('field_key', $field_key)
            ->where('workflow_stage_id', $this->stage->id)
            ->delete();
    }

    /**
     * Create formview for form_submission workflow stage.
     *
     * @param array $fields
     * @return void
     */
    private function add_for_form(array $fields): void {
        $stages_id = $this->stage->workflow_version->stages->keys();
        $all_fields = workflow_stage_formview_entity::repository()
            ->where_in('workflow_stage_id', array_values($stages_id))
            ->order_by('id')
            ->get(true)
            ->pluck('field_key');

        foreach ($fields as $field) {
            if (in_array($field->get_field_key(), $all_fields)) {
                continue;
            }
            // Avoiding impossible condition
            if ($field->required == 'true' && $field->disabled == 'true') {
                $this->add($field->get_field_key(), false, $field->disabled);
            // Always creates required = true unless in schema we have something else
            } else {
                $this->add($field->get_field_key(), $field->required, $field->disabled);
            }
        }
    }

    /**
     * Create formview for approvers workflow stage.
     *
     * @param workflow_stage $stage
     * @return void
     */
    private function add_for_approvers(workflow_stage $stage): void {
        $fields = workflow_stage_formview_entity::repository()
            ->where('workflow_stage_id', '=', $stage->id)
            ->get()
            ->map_to(workflow_stage_formview::class);

        foreach ($fields as $field) {
            /** @var workflow_stage_formview $field */
            $field->clone($this->stage);
        }
    }

    /**
     * @inheritdoc
     */
    public function add_default(): void {
        $schema = form_schema::from_form_version($this->stage->workflow_version->form_version);
        $fields = $schema->get_fields();

        $type = $this->stage->type::get_enum();

        switch ($type) {
            case form_submission::get_enum():
                $this->add_for_form($fields);
                break;
            case approvals::get_enum():
                $stage = $this->stage->workflow_version->get_previous_stage($this->stage->id);
                if ($stage) {
                    $this->add_for_approvers($stage);
                } else {
                    $this->add_for_form($fields);
                }
                break;
            case waiting::get_enum():
                break;
            default:
                throw new coding_exception("Cannot create formviews for this stage type");
        }
    }
}
