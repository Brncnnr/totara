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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment;

use core\orm\collection;
use core\orm\entity\entity;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_approval\entity\assignment\assignment_approver as approver_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\active_trait;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\console_trait;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\workflow_stage_approval_level as approval_level;
use moodle_transaction;

/**
 * Approval workflow assignment approver entity
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read int $approval_id Parent assignment ID
 * @property-read int $workflow_stage_approval_level_id Approval_level ID for this approver
 * @property-read int $type Assignment type code (relationship|user)
 * @property-read int $identifier Database ID of assignee record
 * @property-read bool $active Is this approver active or not?
 * @property-read int $created Created timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 * @property-read string $name Human-readable name of the approver
 * @property-read null|int $ancestor_id Database record that this approver instance is inherited from, if any
 *
 * Relationships:
 * @property-read assignment $assignment Parent assignment
 * @property-read approval_level $approval_level The stage approval level associated with this approver
 * @property-read approver_entity $approver_entity The entity represented by this approver
 * @property-read null|assignment_approver $ancestor Approver model (if any) that this approver is inherited from
 * @property-read collection|assignment_approver[] $descendants Descendant approvers, those that inherit from this approver on other assignments
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(approver_entity $entity)
 */
class assignment_approver extends model {

    use active_trait;
    use model_trait;
    use console_trait;

    /** @var approver_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'type',
        'identifier',
        'active',
        'approval_id',
        'workflow_stage_approval_level_id',
        'created',
        'updated',
        'ancestor_id'
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'assignment',
        'approval_level',
        'approver_entity',
        'name',
        'ancestor',
        'descendants'
    ];

    /** @var string[] */
    protected $deactivate_checklist = [];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return approver_entity::class;
    }

    /**
     * Get the parent assignment.
     *
     * @return assignment
     */
    public function get_assignment(): assignment {
        return assignment::load_by_entity($this->entity->assignment);
    }

    /**
     * Get the associated workflow stage approval level.
     *
     * @return approval_level
     */
    public function get_approval_level(): approval_level {
        return approval_level::load_by_entity($this->entity->workflow_stage_approval_level);
    }

    /**
     * Get the approver entity for this assignment_approver instance.
     *
     * @return entity|model
     */
    public function get_approver_entity() {
        return assignment_approver_type::get_instance($this->type)->entity($this->identifier);
    }

    /**
     * If this assignment_approver is inherited, get the original assignment_approver.
     *
     * @return assignment_approver|null
     */
    public function get_ancestor(): ?assignment_approver {
        return !is_null($this->entity->ancestor) ? assignment_approver::load_by_entity($this->entity->ancestor) : null;
    }

    /**
     * Get any assignment_approvers which inherit from this one on assignment overrides.
     *
     * @return collection|assignment_approver[]
     */
    public function get_descendants(): collection {
        return $this->entity->descendants->map_to(assignment_approver::class);
    }

    /**
     * Loads an assignment_approver instance by type, identifier, and assignment_approver_level.
     *
     * Active or inactive, inherited or not, it does not matter.
     *
     * @param int $type The assignment_approver_type code
     * @param int $identifier Database ID of the assigned entity
     * @param assignment_approval_level $assignment_approver_level Assignment approval level to check at
     * @return static|null
     */
    public static function load_by_type_identifier_and_assignment_approver_level(int $type, int $identifier, assignment_approval_level $assignment_approver_level): ?self {
        $entity = approver_entity::repository()
            ->where('approval_id', '=', $assignment_approver_level->get_assignment()->id)
            ->where('workflow_stage_approval_level_id', '=', $assignment_approver_level->get_approval_level()->id)
            ->where('identifier', '=', $identifier)
            ->where('type', '=', $type)
            ->one();
        return !is_null($entity) ? assignment_approver::load_by_entity($entity) : null;
    }

    /**
     * Create a assignment approver.
     *
     * @param assignment $assignment Parent assignment
     * @param approval_level $level The stage approval level
     * @param int $type One of approver_type_class::TYPE_IDENTIFIER
     * @param int $identifier Relationship identifier depending on $type
     * @param int|null $ancestor_id Ancestor assignment_approver ID
     *
     * @return self
     */
    public static function create(assignment $assignment, approval_level $level, int $type, int $identifier, int $ancestor_id = null): self {
        if (empty($assignment) || empty($assignment->id)) {
            throw new model_exception('Assignment cannot be empty during approver creation');
        }

        if (empty($level) || empty($level->id)) {
            throw new model_exception('Workflow stage approval level cannot be empty during approver creation');
        }

        if (!$level->active) {
            throw new model_exception("Approval level must be active");
        }
        $approver_type = assignment_approver_type::get_instance($type);

        if (!$approver_type->is_valid($identifier)) {
            throw new model_exception("Invalid assignment_approver identifier");
        }

        // See if this approver already exists (might be deactivated, might be a descendant).
        $assignment_approver_level = new assignment_approval_level($assignment, $level);
        $approver = self::load_by_type_identifier_and_assignment_approver_level($type, $identifier, $assignment_approver_level);
        self::log('Approver lookup');
        // Start transaction, a bunch of things need to happen consistently here.
        $transaction = builder::get_db()->start_delegated_transaction();

        // If this is NOT an inherited approver, and there ARE any inherited approvers on this assignment at this level,
        //   they need to be deactivated.
        if (is_null($ancestor_id)) {
            $assignment_approver_level->deactivate_inherited_approvers($transaction);
            self::log('Create deactivated inherited approvers');
        }
        if (!is_null($approver)) {
            self::log('Approver was found');
            // Reload in case approver was deactivated above.
            $approver->refresh(true);
            // Approver already exists, is it active?
            if ($approver->active) {
                throw new model_exception("Matching active approver already exists for this assignment and approval level");
            }
            // Set ancestor_id.
            $approver->set_ancestor_id($ancestor_id);
            self::log('Approver de-ancestored');
            // Activate.
            $approver->activate();
            self::log('Approver activated');
        } else {
            self::log('Approver not found');
            $entity = new approver_entity();
            $entity->approval_id = $assignment->id;
            $entity->workflow_stage_approval_level_id = $level->id;
            $entity->type = $type;
            $entity->identifier = $identifier;
            $entity->active = false;
            $entity->ancestor_id = $ancestor_id;
            $entity->save();

            $approver = self::load_by_entity($entity);
            $approver->activate();
            self::log('New approver activated');
        }

        $transaction->allow_commit();

        return $approver;
    }

    /**
     * Create/activate inherited assignment_approver instances on the same approval_level in descendant assignments.
     *
     * Note that this is relying on the behaviour of assignment_approver_level::get_descendant_levels() to only touch
     * truly descendant levels, and skip those that have their own direct (non-inherited) approver(s) set.
     *
     * @param moodle_transaction $transaction This method requires a database transaction
     * @param assignment_approval_level|null $assignment_approval_level The level from which to create descendants; defaults to this level where this approver is set.
     */
    public function create_descendants(moodle_transaction $transaction, assignment_approval_level $assignment_approval_level = null): void {
        if (is_null($assignment_approval_level)) {
            $assignment_approval_level = new assignment_approval_level($this->assignment, $this->approval_level);
            $assignment_approval_level->set_activemode(true);
        }
        // The get_descendants() lookup below must be limited to active assignments.
        if ($assignment_approval_level->get_activemode() == false) {
            throw new model_exception('It is not allowed to create inherited approvers on inactive assignments, but assignment_approval_level is not in active mode.');
        }
        $descendant_levels = $assignment_approval_level->get_descendants();
        foreach ($descendant_levels as $descendant_level) {
            $descendant_approver = self::load_by_type_identifier_and_assignment_approver_level($this->type, $this->identifier, $descendant_level);
            if (!is_null($descendant_approver)) {
                // Approver already exists. If it was inactive, activate.
                $descendant_approver->activate(true);
                $descendant_approver->set_ancestor_id($this->id);
            } else {
                $entity = new approver_entity();
                $entity->approval_id = $descendant_level->get_assignment()->id;
                $entity->workflow_stage_approval_level_id = $this->workflow_stage_approval_level_id;
                $entity->type = $this->type;
                $entity->identifier = $this->identifier;
                $entity->active = false;
                $entity->save();

                $descendant_approver = self::load_by_entity($entity);
                $descendant_approver->activate(true);
                $descendant_approver->set_ancestor_id($this->id);
            }
        }
    }

    /**
     * Sets and saves the ancestor_id field of this assignment_approver instance.
     *
     * @param int|null $id Database ID of ancestor assignment_approver record; set to null by default.
     */
    protected function set_ancestor_id(int $id = null): void {
        if (!is_null($id)) {
            $ancestor = self::load_by_id($id);
            if ($ancestor->approval_id == $this->approval_id) {
                throw new model_exception('Unable to create an ancestor relationship on the same assignment');
            }
            if ($ancestor->type != $this->type
                || $ancestor->identifier != $this->identifier
                || $ancestor->workflow_stage_approval_level_id != $this->workflow_stage_approval_level_id
            ) {
                throw new model_exception('Ancestor/descendant mismatch');
            }
        }
        $this->entity->ancestor_id = $id;
        $this->entity->save();
    }

    /**
     * Activate approver and assign role to users.
     *
     * Changes made to active_trait::activate() must be synchronised with this function
     *
     * @param bool $skip_decendants_check Flag to skip the check of inherited approvers which should also be deactivated
     * @return self
     */
    public function activate(bool $skip_decendants_check = false): self {
        $transaction = builder::get_db()->start_delegated_transaction();
        if (!$this->entity->active) {
            $this->log("Activating approver {$this->entity->id} for assignment {$this->entity->approval_id}");

            $this->entity->active = true;
            $this->entity->save();
            $context = $this->assignment->get_context();
            $approver_role_id = builder::table('role')->where('shortname', 'approvalworkflowapprover')->value('id');
            if ($this->type == user_approver_type::TYPE_IDENTIFIER) {
                role_assign($approver_role_id, $this->identifier, $context->id, 'mod_approval', $this->assignment->id);
                $this->log('Role assigned');
            }
        }
        if (!$skip_decendants_check) {
            $this->log('Checking descendents');
            $this->create_descendants($transaction);
            $this->log('Descendents checked');
        }
        $transaction->allow_commit();
        return $this;
    }

    /**
     * Deactivate approver and unassigned role from users.
     *
     * Changes made to active_trait::deactivate() must be synchronised with this function
     *
     * @param bool $skip_decendants_check Flag to skip the check of inherited approvers which should also be deactivated
     * @return self
     */
    public function deactivate(bool $skip_decendants_check = false): self {
        if ($this->entity->active) {
            // Check for active dependencies
            if (!$this->can_deactivate()) {
                throw new model_exception("Cannot deactivate object with active dependencies");
            }

            $transaction = builder::get_db()->start_delegated_transaction();

            $this->entity->active = false;
            $this->entity->save();

            // If there are no other instances of this entity on the assignment, remove the approver role.
            $same_at_other_levels = \mod_approval\entity\assignment\assignment_approver::repository()
                ->where('active', '=', true)
                ->where('approval_id', '=', $this->approval_id)
                ->where('type', '=', user_approver_type::TYPE_IDENTIFIER)
                ->where('identifier', '=', $this->entity->identifier)
                ->get();
            if ($same_at_other_levels->count() == 0) {
                $context = $this->assignment->get_context();
                $approver_role_id = builder::table('role')->where('shortname', 'approvalworkflowapprover')->value('id');
                if ($this->type == user_approver_type::TYPE_IDENTIFIER) {
                    role_unassign($approver_role_id, $this->identifier, $context->id, 'mod_approval', $this->assignment->id);
                }
            }
            // Check for descendants which should also be deactivated.
            if (!$skip_decendants_check) {
                if ($this->descendants->count()) {
                    $this->deactivate_descendant_approvers($transaction);
                }
            }

            $transaction->allow_commit();
        }
        return $this;
    }

    /**
     * Deactivate inherited approvers of this one on descendant assignments.
     *
     * @param moodle_transaction $transaction This method requires a database transaction
     */
    protected function deactivate_descendant_approvers(moodle_transaction $transaction): void {
        $assignment_approver_level = new assignment_approval_level($this->assignment, $this->approval_level);
        $descendant_levels = $assignment_approver_level->get_descendants();
        foreach ($descendant_levels as $descendant_level) {
            $descendant_approver = self::load_by_type_identifier_and_assignment_approver_level($this->type, $this->identifier, $descendant_level);
            if (!is_null($descendant_approver) && $descendant_approver->ancestor_id == $this->id && $descendant_approver->active) {
                $descendant_approver->deactivate(true);
            }
        }
    }

    /**
     * Delete the record.
     *
     * @return self
     */
    public function delete(): self {
        // Unassign role.
        $this->deactivate();

        // Delete all the assignment_approvers that have ancestor_id = this->id.
        $this->delete_inherited_approvers();

        // Delete entity.
        $this->entity->delete();

        return $this;
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return assignment_approver_type::get_instance($this->type)->entity_name($this->identifier);
    }

    /**
     * Delete inherited approvers.
     *
     */
    private function delete_inherited_approvers(): void {
        $approvers = approver_entity::repository()
            ->where('ancestor_id', $this->id)
            ->get()
            ->map_to(assignment_approver::class);
        foreach ($approvers as $approver) {
            $approver->delete(true);
        }
    }
}
