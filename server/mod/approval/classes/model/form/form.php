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
use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_approval\entity\form\form as form_entity;
use mod_approval\entity\form\form_version as form_version_entity;
use mod_approval\model\active_trait;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\workflow;

/**
 * Approval workflow form model
 *
 * Properties:
 *
 * @property-read integer $id Database record ID
 * @property-read string $plugin_name Form plugin name
 * @property-read string $title Human-readable form name
 * @property-read boolean $active Is this form active or not?
 * @property-read int $created Creation timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read form_version $latest_version Latest form_version
 * @property-read form_version $active_version Latest active form_version
 * @property-read collection|form_version[] $versions Collection of form_versions for this form
 * @property-read collection|workflow[] $workflows Collection of workflows which use this form
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(form_entity $entity)
 */
final class form extends model {

    use active_trait;
    use model_trait;

    /** @var form_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'plugin_name',
        'title',
        'active',
        'created',
        'updated',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'latest_version',
        'active_version',
        'versions',
        'workflows',
    ];

    /** @var string[] */
    protected $deactivate_checklist = [
        form_version::class => 'form_id',
        workflow::class => 'form_id'
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return form_entity::class;
    }

    /**
     * Get the latest form version.
     *
     * @return form_version
     */
    public function get_latest_version(): form_version {
        $latest_version = form_version::load_latest_by_form_id($this->id);
        if (!$latest_version) {
            throw new coding_exception('Form version not found');
        }
        return $latest_version;
    }

    /**
     * Get the latest active form version.
     *
     * @return form_version|null
     */
    public function get_active_version(): ?form_version {
        return form_version::load_active_by_form_id($this->id);
    }

    /**
     * Get the form versions for this form.
     *
     * @return collection|form_version[]
     */
    public function get_versions(): collection {
        return $this->entity->versions->map_to(form_version::class);
    }

    /**
     * Get the workflows which use this form
     *
     * @return collection|workflow[]
     */
    public function get_workflows(): collection {
        return $this->entity->workflows->map_to(workflow::class);
    }

    /**
     * Create a new form.
     *
     * @param string $plugin_name Form plugin name
     * @param string $title Human-readable form name
     * @return self
     */
    public static function create(string $plugin_name, string $title): self {
        if ($title === '') {
            throw new coding_exception('title cannot be empty');
        }

        $form_plugin = approvalform_base::from_plugin_name($plugin_name);
        if (!$form_plugin->is_enabled()) {
            throw new coding_exception("The form plugin '{$plugin_name}' is unavailable.");
        }

        $entity = new form_entity();
        $entity->plugin_name = $plugin_name;
        $entity->title = $title;
        $entity->active = true;

        // Wrap the following in a transaction.
        $form = builder::get_db()->transaction(function () use ($entity, $form_plugin) {
            $entity->save();
            $form = self::load_by_entity($entity);

            // Also create a form_version for the form, and activate it.
            $form_version = form_version::create(
                $form,
                $form_plugin->get_form_version(),
                $form_plugin->get_form_schema_json(),
                $form_plugin->default_version_status()
            );

            return $form;
        });

        // Refesh and return the form.
        return $form->refresh();
    }

    /**
     * Delete the record.
     *
     * @param bool $force Flag to force deletion of form_versions even if they are not draft.
     * @return self
     */
    public function delete(bool $force = false): self {
        if (!$this->entity->exists()) {
            return $this;
        }
        builder::get_db()->transaction(function () use ($force) {
            $id = $this->entity->id;
            $vers = form_version_entity::repository()
                ->where('form_id', $id)
                ->get()
                ->map_to(form_version::class);
            foreach ($vers as $ver) {
                $ver->delete($force);
            }
            $this->entity->delete();
        });
        return $this;
    }
}
