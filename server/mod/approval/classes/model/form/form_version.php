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

use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\order;
use mod_approval\entity\form\form_version as form_version_entity;
use mod_approval\exception\model_exception;
use mod_approval\form_schema\form_schema;
use mod_approval\model\application\application;
use mod_approval\model\model_trait;
use mod_approval\model\status;
use mod_approval\model\status_trait;

/**
 * Approval workflow form version entity
 *
 * Properties:
 *
 * @property-read int $id Database record ID
 * @property-read int $form_id Parent form ID
 * @property-read string $version Version identifying string
 * @property-read string $json_schema JSON form schema
 * @property-read int $status Form_version status code (draft|active|archived)
 * @property-read int $created Creation timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 *
 * Relationships:
 * @property-read form $form Parent form
 * @property-read collection|application[] $applications using this form_version
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(form_version_entity $entity)
 */
final class form_version extends model {

    use model_trait;
    use status_trait;

    /** @var form_version_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'form_id',
        'version',
        'json_schema',
        'status',
        'created',
        'updated',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'form',
        'applications'
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return form_version_entity::class;
    }

    /**
     * Get the parent form.
     *
     * @return form
     */
    public function get_form(): form {
        return form::load_by_entity($this->entity->form);
    }

    /**
     * Get the applications using this form version.
     *
     * @return collection|application[]
     */
    public function get_applications(): collection {
        return $this->entity->applications->map_to(application::class);
    }

    /**
     * Create a form version.
     *
     * @param form $form Parent form
     * @param string $version Version identifying string
     * @param string $json_schema JSON form schema
     * @param int $status Status code
     * @return self
     */
    public static function create(form $form, string $version, string $json_schema, int $status = status::ACTIVE): self {
        if (!$form->active) {
            throw new model_exception("Form must be active");
        }
        if ($version === '') {
            throw new model_exception('Version cannot be empty');
        }
        // TODO: add real json validation
        $parsed = @json_decode($json_schema, true, 256);
        if (!is_array($parsed)) {
            throw new model_exception('Malicious json schema');
        }
        $entity = new form_version_entity();
        $entity->form_id = $form->id;
        $entity->version = $version;
        $entity->json_schema = $json_schema;
        $entity->status = $status;
        $entity->save();
        return self::load_by_entity($entity);
    }

    /**
     * Load the latest form version of the form.
     *
     * @param int $form_id Parent form ID
     * @return self
     */
    public static function load_latest_by_form_id(int $form_id): self {
        return self::load_by_entity(
            form_version_entity::repository()
                ->where('form_id', $form_id)
                ->order_by('id', order::DIRECTION_DESC)
                ->first(true)
        );
    }

    /**
     * Load the latest active form version of the form.
     *
     * @param int $form_id Parent form ID
     * @return self|null
     */
    public static function load_active_by_form_id(int $form_id): ?self {
        $active_version = form_version_entity::repository()
            ->where('form_id', $form_id)
            ->where('status', status::ACTIVE)
            ->order_by('id', order::DIRECTION_DESC)
            ->first();
        if (!$active_version) {
            return null;
        } else {
            return self::load_by_entity($active_version);
        }
    }

    /**
     * Set a form_version's schema and version.
     *
     * @param form_schema $new_schema
     * @param string $new_version
     * @return self
     */
    public function set_schema(form_schema $new_schema, string $new_version): self {
        $this->entity->json_schema = $new_schema->to_json();
        $this->entity->version = $new_version;
        $this->entity->save();
        $this->refresh(true);

        return $this;
    }
}
