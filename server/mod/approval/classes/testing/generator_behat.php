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

namespace mod_approval\testing;

use coding_exception;
use core\entity\cohort as cohort_entity;
use core\entity\user as user_entity;
use core\orm\entity\entity;
use core\orm\entity\model;
use core\orm\query\builder;
use core_date;
use DateTime;
use dml_exception;
use hierarchy_organisation\entity\organisation as organisation_entity;
use hierarchy_position\entity\position as position_entity;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\form\form as form_entity;
use mod_approval\entity\form\form_version as form_version_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\entity\workflow\workflow_type as workflow_type_entity;
use mod_approval\model\application\action\action;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\action\withdraw_before_submission;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\application as application_model;
use mod_approval\model\application\application_submission as application_submission_model;
use mod_approval\model\assignment\approver_type\relationship;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\model\assignment\assignment_approver as assignment_approver_model;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\approvalform_base;
use mod_approval\model\form\form as form_model;
use mod_approval\model\form\form_data;
use mod_approval\model\form\form_version as form_version_model;
use mod_approval\model\status;
use mod_approval\model\workflow\interaction\transition\next;
use mod_approval\model\workflow\interaction\transition\stage;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\model\workflow\workflow_stage as workflow_stage_model;
use mod_approval\model\workflow\workflow_stage_approval_level as workflow_stage_approval_level_model;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\model\workflow\workflow_stage_interaction_transition;
use mod_approval\model\workflow\workflow_type as workflow_type_model;
use mod_approval\model\workflow\workflow_version as workflow_version_model;
use moodle_exception;
use ReflectionProperty;
use totara_core\relationship\relationship as relationship_model;
use totara_job\entity\job_assignment as job_assignment_entity;

/**
 * Separate behat generators just for searchability :)
 */
trait generator_behat {

    /**
     * Translate JSON by using a raw JSON string or loading JSON from a file.
     * $record[$field] must be one of:
     * - raw json e.g. '{"foo":"bar"}'
     * - json file in approval's fixtures e.g. 'foobar'  -> mod/approval/tests/fixtures/$dir/foobar.json
     * - json file in a plugin's fixtures e.g. 'foo:bar' -> mod/approval/form/foo/tests/fixtures/$dir/bar.json
     *
     * @param array $record
     * @param string $field array key of $record
     * @param string $dir directory name in fixture
     * @param string $default default value
     * @return string json string
     */
    private function resolve_json(array $record, string $field, string $dir, string $default): string {
        global $CFG;
        if (!isset($record[$field]) || (string) $record[$field] === '') {
            $value = $default;
        } else {
            $value = $record[$field];
        }
        if (substr($value, 0, 1) === '{' && substr($value, -1) === '}') {
            $json = $value;
        } else {
            if (!preg_match('/^((?P<plugin>(?:[a-z0-9\-_]+)):)?(?P<file>(?:[a-z0-9\-_]+))$/', $value, $matches)) {
                throw new coding_exception('Invalid json file');
            }
            if ($matches['plugin'] !== '') {
                $path = "{$CFG->dirroot}/mod/approval/form/{$matches['plugin']}/tests/fixtures/{$dir}/{$matches['file']}.json";
            } else {
                $path = "{$CFG->dirroot}/mod/approval/tests/fixtures/{$dir}/{$matches['file']}.json";
            }
            $json = @file_get_contents($path);
            if (!$json) {
                throw new coding_exception("File not found: {$path}");
            }
        }
        $result = @json_decode($json, false, 512);
        if (!is_object($result)) {
            throw new coding_exception("Invalid JSON: {$value}");
        }
        return $json;
    }

    /**
     * Translate boolean value.
     * Accepted values are true/false, yes/no, 1/0 or empty.
     *
     * @param array $record
     * @param string $field array key of $record
     * @param boolean $default default value
     * @return boolean
     */
    private function resolve_bool(array $record, string $field, bool $default): bool {
        if (!isset($record[$field])) {
            return $default;
        }
        $value = strtolower($record[$field]);
        if ($default) {
            return $value !== 'false' && $value !== 'no' && $value !== '0';
        } else {
            return $value === 'true' || $value === 'yes' || $value === '1';
        }
    }

    /**
     * Calculate the next sortorder.
     *
     * @param builder $builder
     * @return integer
     */
    private function resolve_sortorder(builder $builder): int {
        $record = $builder->select_raw("MAX({$builder->get_alias()}.sortorder) as max")->one();
        if ($record) {
            return $record->max + 1;
        } else {
            return 1;
        }
    }

    /**
     * Translate username to user id.
     *
     * @param array $record
     * @param string $field array key of $record
     * @param user_entity|null $default default user or null to make the field mandatory
     * @return user_entity
     */
    private function resolve_user(array $record, string $field, ?user_entity $default): user_entity {
        if (!isset($record[$field]) || (string) $record[$field] === '') {
            if ($default) {
                return $default;
            }
            throw new coding_exception("Missing field: {$field}");
        }
        /** @var user_entity $user */
        $user = user_entity::repository()->where('username', $record[$field])->one(true);
        return $user;
    }

    /**
     * Translate job assignment idnumber to job assignment id.
     *
     * @param array $record
     * @param string $field array key of $record
     * @param job_assignment_entity|null $default
     * @return job_assignment_entity|null
     */
    private function resolve_job_assignment(array $record, string $field, ?job_assignment_entity $default): ?job_assignment_entity {
        if (!isset($record[$field]) || (string) $record[$field] === '') {
            return $default;
        }
        /** @var job_assignment_entity $ja */
        $ja = job_assignment_entity::repository()->where('idnumber', $record[$field])->one(true);
        return $ja;
    }

    /**
     * Translate workflow id_number or name to workflow.
     *
     * @param array $record 'workflow' is required
     * @return workflow_model
     */
    private function resolve_workflow(array $record): workflow_model {
        /** @var workflow_entity $workflow_entity */
        $workflow_entity = workflow_entity::repository()
            ->where('id_number', $record['workflow'])
            ->or_where('name', $record['workflow'])
            ->one(true);
        return workflow_model::load_by_entity($workflow_entity);
    }

    /**
     * Translate workflow stage name to workflow_stage.
     *
     * @param array $record 'workflow_stage' is required
     * @return workflow_stage_model
     */
    private function resolve_workflow_stage(array $record): workflow_stage_model {
        /** @var workflow_stage_entity $workflow_stage_entity */
        $workflow_stage_entity = workflow_stage_entity::repository()
            ->where('name', $record['workflow_stage'])
            ->one(true);
        return workflow_stage_model::load_by_entity($workflow_stage_entity);
    }

    /**
     * Translate form title to form.
     *
     * @param array $record 'title' is required
     * @return form_model
     */
    private function resolve_form(array $record): form_model {
        /** @var form_entity $form_entity */
        $form_entity = form_entity::repository()
            ->where('title', $record['form'])
            ->one(true);
        return form_model::load_by_entity($form_entity);
    }

    /**
     * Translate assignment id_number or name to assignment.
     *
     * @param array $record 'assignment' is required
     * @return assignment_model
     */
    private function resolve_assignment(array $record): assignment_model {
        /** @var assignment_entity $assignment_entity */
        $assignment_entity = assignment_entity::repository()
            ->where('id_number', $record['assignment'])
            ->or_where('name', $record['assignment'])
            ->one(true);
        return assignment_model::load_by_entity($assignment_entity);
    }

    /**
     * Translate assignment type and identifier.
     *
     * @param array $record 'type' and 'identifier' are required
     * @return array of [type, identifier]
     */
    private function resolve_assignment_type(array $record): array {
        $type = strtolower($record['type']);
        $identifier = $record['identifier'];
        if ($type === 'organisation') {
            $type = assignment_type\organisation::get_code();
            $identifier = organisation_entity::repository()
                ->where('idnumber', $identifier)
                ->or_where('shortname', $identifier)
                ->one(true)
                ->id;
        } else if ($type === 'position') {
            $type = assignment_type\position::get_code();
            $identifier = position_entity::repository()
                ->where('idnumber', $identifier)
                ->or_where('shortname', $identifier)
                ->one(true)
                ->id;
        } else if ($type === 'cohort') {
            $type = assignment_type\cohort::get_code();
            $identifier = cohort_entity::repository()
                ->where('idnumber', $identifier)
                ->or_where('name', $identifier)
                ->one(true)
                ->id;
        } else {
            throw new coding_exception("Invalid assignment type: {$type}");
        }
        return [$type, $identifier];
    }

    /**
     * Translate approver type and identifier.
     *
     * @param array $record 'type' and 'identifier' are required
     * @return array of [type, identifier]
     */
    private function resolve_approver_type(array $record): array {
        $type = strtolower($record['type']);
        $identifier = $record['identifier'];
        if ($type === 'relationship') {
            $type = relationship::TYPE_IDENTIFIER;
            $identifier = relationship_model::load_by_idnumber($identifier)->id;
        } else if ($type === 'user') {
            $type = user::TYPE_IDENTIFIER;
            $identifier = $this->resolve_user($record, 'identifier', null)->id;
        } else {
            throw new coding_exception("Invalid approver type: {$type}");
        }
        return [$type, $identifier];
    }

    /**
     * Translate approval level name to workflow_stage_approval_level.
     *
     * @param array $record 'approval_level' is required, 'workflow_stage' is required only if approval_level is ambiguous
     * @return workflow_stage_approval_level_model
     */
    private function resolve_approval_level(array $record): workflow_stage_approval_level_model {
        $repository = workflow_stage_approval_level_entity::repository()
            ->where('name', $record['approval_level']);
        if (isset($record['workflow_stage']) && (string) $record['workflow_stage'] !== '') {
            $workflow_stage = $this->resolve_workflow_stage($record);
            $repository->where('workflow_stage_id', $workflow_stage->id);
        }
        /** @var workflow_stage_approval_level_entity $workflow_stage_approval_level_entity */
        $workflow_stage_approval_level_entity = $repository->one(true);
        return workflow_stage_approval_level_model::load_by_entity($workflow_stage_approval_level_entity);
    }

    /**
     * Translate application id_number or title to application.
     *
     * @param array $record 'application' is required
     * @return application_model
     */
    private function resolve_application(array $record): application_model {
        /** @var application_entity $application_entity */
        $application_entity = application_entity::repository()
            ->where('title', $record['application'])
            ->one(true);
        return application_model::load_by_entity($application_entity);
    }

    /**
     * Translate interaction id to interaction.
     *
     * @param array $record 'interaction' is required
     * @return workflow_stage_interaction
     */
    private function resolve_interaction(array $record): workflow_stage_interaction {
        /** @var workflow_stage_interaction_entity $workflow_stage_interaction_entity */
        $workflow_stage_interaction_entity = workflow_stage_interaction_entity::repository()
            ->where('id', $record['interaction'])
            ->one(true);
        return workflow_stage_interaction::load_by_entity($workflow_stage_interaction_entity);
    }

    /**
     * Activate the model if necessary.
     *
     * @param array $record 'active' is optional, default to true
     * @param model|object $model
     * @return integer
     */
    private function activate_model(array $record, $model): int {
        $active = $this->resolve_bool($record, 'active', true);
        if ($active) {
            $model->activate();
        } else if (isset($model->active)) {
            $model->deactivate();
        }
        return $model->id;
    }

    /**
     * Change the status of the model if necessary.
     *
     * @param array $record
     * @param model|object $model
     * @return integer
     */
    private function set_model_status(array $record, $model): int {
        if (!isset($record['status'])) {
            return $this->activate_model($record, $model);
        }
        if (isset($record['active'])) {
            throw new coding_exception('Cannot use both active and status');
        }
        $status = $record['status'];
        $enums = [
            status::DRAFT_ENUM => status::DRAFT,
            status::ACTIVE_ENUM => status::ACTIVE,
            status::ARCHIVED_ENUM => status::ARCHIVED,
        ];
        if (!isset($enums[$status])) {
            throw new coding_exception('Invalid status: ' . $status);
        }
        $prop = new ReflectionProperty($model, 'entity');
        $prop->setAccessible(true);
        /** @var entity $entity */
        $entity = $prop->getValue($model);
        $entity->status = $enums[$status];
        if ($entity->changed()) {
            $entity->save();
        }
        return $model->id;
    }

    /**
     * Modify the underlying entity.
     *
     * @param model $model
     * @param string $name
     * @param mixed $value
     * @param boolean $save Set true to update the actual database record
     * @return mixed
     */
    private function set_entity_attribute(model $model, string $name, $value, bool $save = false) {
        $rp = new ReflectionProperty($model, 'entity');
        $rp->setAccessible(true);
        /** @var entity $entity */
        $entity = $rp->getValue($model);
        $old_value = $entity->get_attribute($name);
        $entity->set_attribute($name, $value);
        if ($save) {
            $entity->save();
        }
        return $old_value;
    }

    /**
     * @param form_data $form_data
     * @param integer|null $time
     * @return form_data
     */
    private function fix_up_form_data(form_data $form_data, int $time = null): form_data {
        $time = $time ?? time();
        $data = (array) $form_data->jsonSerialize();
        if (empty($data)) {
            return $form_data;
        }
        foreach ($data as $key => $value) {
            if (preg_match('/^##\s*(.+?)\s*##\s*(.+?)\s*##$/', $value, $matches)) {
                $data[$key] = (new DateTime('@' . $time))
                    ->setTimezone(core_date::get_user_timezone_object())
                    ->modify($matches[1])
                    ->format($matches[2]);
            }
        }
        return form_data::from_json(json_encode($data));
    }

    /**
     * @param array $record
     * @param callable $callback
     * @return mixed
     */
    private static function do_work(array $record, callable $callback) {
        try {
            return $callback($record);
        } catch (moodle_exception $ex) {
            if ((string) $ex->debuginfo !== '') {
                // Attach debuginfo because behat can display only the message part.
                $message = "{$ex->getMessage()} ($ex->debuginfo)";
                // Keep some exception types for unit test.
                if ($ex instanceof dml_exception) {
                    throw new dml_exception($message);
                }
                if ($ex instanceof coding_exception) {
                    throw new coding_exception($message);
                }
                throw new moodle_exception($message);
            } else {
                throw $ex;
            }
        }
    }

    /**
     * Given the following "workflow types" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_workflow_type_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $workflow_type = workflow_type_model::create($record['name']);
            $this->activate_model($record, $workflow_type);
            return $workflow_type->id;
        });
    }

    /**
     * Given the following "forms" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_form_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            if (empty($record['plugin'])) {
                $record['plugin'] = 'simple';
            }
            $form = form_model::create($record['plugin'], $record['title']);
            // Delete form version created by form_model::create()
            form_version_entity::repository()->where('form_id', $form->id)->delete();
            $this->activate_model($record, $form);
            return $form->id;
        });
    }

    /**
     * Given the following "form versions" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_form_version_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $form = $this->resolve_form($record);
            $plugin = approvalform_base::from_plugin_name($form->plugin_name);
            $json_schema = $this->resolve_json($record, 'json_schema', 'schema', $plugin->get_form_schema_json());
            $form_version = form_version_model::create($form, $record['version'], $json_schema, status::DRAFT);
            $this->set_model_status($record, $form_version);
            return $form_version->id;
        });
    }

    /**
     * Given the following "workflows" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_workflow_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            if (!isset($record['description'])) {
                $record['description'] = '';
            }
            // assignment_id_number optional unless you created multiple workflows using one organisation/position/cohort

            if (!isset($record['assignment_id_number'])) {
                $record['assignment_id_number'] = '';
            }
            /** @var workflow_type_entity $workflow_type_entity */
            $workflow_type_entity = workflow_type_entity::repository()->where('name', $record['workflow_type'])->one(true);
            $workflow_type = workflow_type_model::load_by_entity($workflow_type_entity);
            $form = $this->resolve_form($record);
            [$type, $identifier] = $this->resolve_assignment_type($record);

            $workflow = workflow_model::create(
                $workflow_type,
                $form,
                $record['name'],
                $record['description'],
                $type,
                $identifier,
                $record['id_number'],
                $record['assignment_id_number']
            );
            $this->activate_model($record, $workflow);
            return $workflow->id;
        });
    }

    /**
     * Given the following "workflow versions" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_workflow_version_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $workflow = $this->resolve_workflow($record);
            /** @var form_version_entity $form_version_entity */
            $form_version_entity = form_version_entity::repository()
                ->where('version', $record['form_version'])
                ->one(true);
            $form_version = form_version_model::load_by_entity($form_version_entity);
            $this->set_entity_attribute($form_version, 'status', status::ACTIVE);
            $workflow_version = workflow_version_model::create($workflow, $form_version);
            $this->set_model_status($record, $workflow_version);
            return $workflow_version->id;
        });
    }

    /**
     * Given the following "workflow stages" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_workflow_stage_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $workflow = $this->resolve_workflow($record);
            $workflow_stage = workflow_stage_model::create($workflow->latest_version, $record['name'], $record['type']);
            $this->activate_model($record, $workflow_stage);
            return $workflow_stage->id;
        });
    }

    /**
     * Given the following "approval levels" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_approval_level_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $workflow_stage = $this->resolve_workflow_stage($record);
            $approval_level = $workflow_stage->add_approval_level($record['name']);
            $this->activate_model($record, $approval_level);
            return $approval_level->id;
        });
    }

    /**
     * Given the following "form views" exist in "mod_approval" plugin
     * Working for configuration formview as well
     *
     * @param array $record
     * @return integer
     */
    public function create_formview_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $workflow_stage = $this->resolve_workflow_stage($record);
            $this->set_entity_attribute($workflow_stage, 'active', true);

            $enum = formviews::resolve_visibility_enum($this->resolve_bool($record, 'required', false), $this->resolve_bool($record, 'disabled', false));
            $workflow_stage->configure_formview([['field_key' => $record['field_key'], 'visibility' => $enum]]);

            $formview = $workflow_stage->formviews->find('field_key', $record['field_key']);
            $this->activate_model($record, $formview);
            return $formview->id;
        });
    }

    /**
     * Given the following "assignments" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_assignment_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            [$type, $identifier] = $this->resolve_assignment_type($record);
            $workflow = $this->resolve_workflow($record);
            $default = $this->resolve_bool($record, 'default', false);
            $assignment = assignment_model::create(
                $workflow->container,
                $type,
                $identifier,
                $default,
                $record['id_number']
            );
            $this->activate_model($record, $assignment);
            return $assignment->id;
        });
    }

    /**
     * Given the following "approvers" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_approver_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            [$type, $identifier] = $this->resolve_approver_type($record);
            $assignment = $this->resolve_assignment($record);
            $approval_level = $this->resolve_approval_level($record);
            $this->set_entity_attribute($approval_level, 'active', true);
            $approver = assignment_approver_model::create($assignment, $approval_level, $type, $identifier);
            $this->activate_model($record, $approver);
            return $approver->id;
        });
    }

    /**
     * Given the following "applications" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_application_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            if (!isset($record['title']) || (string) $record['title'] === '') {
                $record['title'] = null;
            }
            $workflow = $this->resolve_workflow($record);
            $assignment = $this->resolve_assignment($record);
            $applicant = $this->resolve_user($record, 'user', null);
            $creator = $this->resolve_user($record, 'creator', $applicant);
            $ja = $this->resolve_job_assignment($record, 'job_assignment', null);
            $workflow_version = $workflow->latest_version;
            $workflow_version_status = $this->set_entity_attribute($workflow_version, 'status', status::ACTIVE, true);
            $form_version_status = $this->set_entity_attribute($workflow_version->form_version, 'status', status::ACTIVE, true);
            $application = application_model::create(
                $workflow->latest_version,
                $assignment,
                $creator->id,
                $applicant->id,
                $ja,
                $record['title']
            );
            $this->set_entity_attribute($workflow_version, 'status', $workflow_version_status, true);
            $this->set_entity_attribute($workflow_version->form_version, 'status', $form_version_status, true);
            if (isset($record['workflow_stage']) && (string) $record['workflow_stage'] !== '') {
                $workflow_stage = $this->resolve_workflow_stage($record);
                builder::table(application_entity::TABLE)
                    ->where('id', $application->id)
                    ->update(['current_stage_id' => $workflow_stage->id]);
            }
            if (isset($record['id_number']) && (string) $record['id_number'] !== '') {
                builder::table(application_entity::TABLE)
                    ->where('id', $application->id)
                    ->update(['id_number' => $record['id_number']]);
            }
            return $application->id;
        });
    }

    /**
     * Given the following "application submissions" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_application_submission_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $application = $this->resolve_application($record);
            $form_data = form_data::from_json($this->resolve_json($record, 'form_data', 'form', '{}'));
            $form_data = $this->fix_up_form_data($form_data);
            $user = $this->resolve_user($record, 'user', $application->user);
            $submission = application_submission_model::create_or_update($application, $user->id, $form_data);
            return $submission->id;
        });
    }

    /**
     * Given the following "application actions" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_application_action_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $application = $this->resolve_application($record);
            $user = $this->resolve_user($record, 'user', null);
            $action = strtolower($record['action']);
            $submission = $application->get_last_submission();
            if (!$submission) {
                throw new coding_exception('No submission');
            }
            if ($action === 'submit') {
                $submission->publish($user->id);
                submit::execute($application, $user->id);
            } else if ($action === 'approve') {
                if (!$application->current_state->is_stage_type(approvals::get_code())) {
                    throw new coding_exception('Cannot approve');
                }
                approve::execute($application, $user->id);
            } else if ($action === 'reject') {
                if (!$application->current_state->is_stage_type(approvals::get_code())) {
                    throw new coding_exception('Cannot reject');
                }
                reject::execute($application, $user->id);
            } else if ($action === 'withdraw') {
                if ($application->current_state->is_stage_type(approvals::get_code())) {
                    withdraw_in_approvals::execute($application, $user->id);
                } else {
                    withdraw_before_submission::execute($application, $user->id);
                }
            } else {
                throw new coding_exception("Invalid action: {$action}");
            }
            return 42; // return something non-zero
        });
    }

    /**
     * Given the following "interaction" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_interaction_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $workflow_stage = $this->resolve_workflow_stage($record);
            $record['action'] = strtoupper($record['action']);
            $action = action::from_enum($record['action']);
            $interaction = workflow_stage_interaction::create(
                $workflow_stage,
                $action
            );
            return $interaction->id;
        });
    }

    /**
     * Given the following "interaction_transition" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_interaction_transition_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $workflow_stage_interaction = $this->resolve_interaction($record);
            if (!isset($record['condition'])) {
                $record['condition'] = null;
            }
            if (!isset($record['priority'])) {
                $record['priority'] = 1;
            }
            $transition = strtolower($record['transition']);
            if ($transition === 'next') {
                $transition = new next($record['data']);
            } else if ($transition === 'stage') {
                if (!isset($record['workflow_stage'])) {
                    throw new coding_exception('Cannot move to stage');
                }
                $workflow_stage = $this->resolve_workflow_stage($record);
                $transition = new stage($record['data'], $workflow_stage->id);
            } else {
                throw new coding_exception("Invalid transition: {$transition}");
            }
            $interaction_transition = workflow_stage_interaction_transition::create(
                $workflow_stage_interaction,
                $record['condition'],
                $transition,
                $record['priority']
            );
            return $interaction_transition->id;
        });
    }

    /**
     * Given the following "interaction_action" exist in "mod_approval" plugin
     *
     * @param array $record
     * @return integer
     */
    public function create_interaction_action_for_behat(array $record): int {
        return self::do_work($record, function (array $record) {
            $workflow_stage_interaction = $this->resolve_interaction($record);
            if (!isset($record['condition_key'])) {
                $record['condition_key'] = null;
            }
            if (!isset($record['condition_data'])) {
                $record['condition_data'] = null;
            }
            if (!isset($record['effect_data'])) {
                $record['effect_data'] = null;
            }
            // TODO review the method ones workflow_stage_interaction_action model will created and add tests

            $interaction_action = workflow_stage_interaction_action::create(
                $workflow_stage_interaction,
                $record['condition_key'],
                $record['condition_data'],
                $record['effect'],
                $record['effect_data']
            );
            return $interaction_action->id;
        });
    }

}
