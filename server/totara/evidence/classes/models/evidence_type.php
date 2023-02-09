<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\models;

use coding_exception;
use context_system;
use core\entity\user;
use core\orm\collection;
use core\orm\query\builder;
use required_capability_exception;
use totara_core\advanced_feature;
use totara_evidence\customfield_area;
use totara_evidence\entity;
use totara_evidence\entity\evidence_type_field;
use totara_evidence\event;
use totara_evidence\models\helpers\multilang_helper;

/**
 * Evidence type.
 *
 * This class represents an admin defined evidence type and
 * abstracts common functions associated with evidence types,
 * and prevents evidence types from having an invalid state.
 *
 * @package totara_evidence\models
 *
 * @property-read string $idnumber Evidence type ID number (raw)
 * @property-read string $display_idnumber Evidence type ID number for display
 * @property-read string $description Evidence type description (raw)
 * @property-read string $display_description Evidence type description for display
 * @property-read int $descriptionformat Description format
 * @property-read int $location Location of type
 * @property-read int $status Status
 * @property-read evidence_type_field[]|collection $fields Custom fields
 */
class evidence_type extends evidence {

    /**
     * The type is a normal type and has no specific additional abilities or constraints
     */
    public const LOCATION_EVIDENCE_BANK = 0;

    /**
     * The type cannot be modified or deleted. Now only used for indicating system types
     */
    public const LOCATION_RECORD_OF_LEARNING = 1;

    public const STATUS_ACTIVE = 1;

    public const STATUS_HIDDEN = 0;

    public const DESCRIPTION_FILEAREA = 'type_description';

    public const DEFAULT_SYSTEM_TYPES = [
        'course' => 'coursecompletionimport',
        'certification' => 'certificationcompletionimport',
    ];

    protected $entity_attribute_whitelist = [
        'id',
        'name',
        'idnumber',
        'description',
        'location',
        'status',
        'created_by',
        'created_by_user',
        'created_at',
        'modified_by',
        'modified_by_user',
        'modified_at',
        'fields',
    ];

    protected $model_accessor_whitelist = [
        'display_name',
        'display_created_at',
        'display_modified_at',
        'descriptionformat',
        'display_description',
        'display_idnumber',
    ];

    private static $default_system_type_fields = null;

    protected static function get_entity_class(): string {
        return entity\evidence_type::class;
    }

    /**
     * Is this evidence type in use?
     *
     * @return bool
     */
    public function in_use(): bool {
        return $this->entity->items()->exists();
    }

    /**
     * Can this evidence type be modified by the current user?
     *
     * @param bool $require Require that this type can be modified
     * @param bool $prevent_in_use Do we care if the type is in use or not? We do care by default.
     * @return bool
     */
    public function can_modify(bool $require = false, bool $prevent_in_use = true): bool {
        // Must exist in the database.
        if (!$this->entity->exists()) {
            if ($require) {
                throw new coding_exception('Evidence type no longer exists');
            }
            return false;
        }

        // Must not be a system defined type.
        if ($this->is_system()) {
            if ($require) {
                throw new coding_exception("Evidence type with ID {$this->id} is a system type and can not be modified");
            }
            return false;
        }

        // Must have the appropriate capability.
        if (!self::can_manage($require)) {
            return false;
        }

        // Must not already be in use.
        if ($prevent_in_use && $this->in_use()) {
            if ($require) {
                throw new coding_exception("Evidence type with ID {$this->id} is currently in use elsewhere");
            }
            return false;
        }

        return true;
    }

    /**
     * Is this type visible and new instances can be created?
     *
     * @return bool
     */
    public function is_visible(): bool {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Is this a system, read-only type?
     *
     * @return bool
     */
    public function is_system(): bool {
        return $this->is_default_system_type();
    }

    /**
     * @return bool
     */
    public function is_editable(): bool {
        return !$this->is_default_system_type();
    }

    /**
     * @param string $type
     * @return static
     */
    public static function load_by_default_system_type(string $type): self {
        if (empty(static::DEFAULT_SYSTEM_TYPES[$type])) {
            throw new coding_exception('Invalid system type specified: ' . $type);
        }
        $entity = entity\evidence_type::repository()
            ->where('idnumber', static::DEFAULT_SYSTEM_TYPES[$type])
            ->one();
        return static::load_by_entity($entity);
    }

    /**
     * @return bool
     */
    public function is_default_system_type(): bool {
        return in_array($this->idnumber, self::DEFAULT_SYSTEM_TYPES);
    }

    /**
     * Return a list of custom fields excluding system fields
     * @return array
     */
    public function get_import_fields(): array {
        if (!advanced_feature::is_enabled('evidence')) {
            return [];
        }

        // We do not support importing of file and multiselect customfields
        return $this->fields
            ->filter(function ($field) {
                return !in_array($field->datatype, ['file', 'multiselect']);
            })
            ->map(function ($field) {
                return 'customfield_' . ($field->shortname ?? $field->fullname);
            })
            ->to_array();
    }

    /**
     * Create a new evidence type
     *
     * @param string $name
     * @param string|null $idnumber
     * @param string|null $description
     * @param string|null $descriptionformat
     * @param int|null $location
     *
     * @return evidence_type
     */
    public static function create(
        string $name,
        string $idnumber = null,
        string $description = null,
        string $descriptionformat = null,
        int $location = self::LOCATION_EVIDENCE_BANK
    ): self {
        if (empty(trim($name))) {
            throw new coding_exception('A name must be specified');
        }
        if ($idnumber !== null && strlen($idnumber) > 0 && totara_idnumber_exists(entity\evidence_type::TABLE, $idnumber)) {
            throw new coding_exception('ID number already exists');
        }
        self::can_manage(true);

        $entity = new entity\evidence_type();
        $entity->name = $name;
        $entity->idnumber = $idnumber;
        $entity->description = $description;
        $entity->descriptionformat = $descriptionformat;
        $entity->modified_by = user::logged_in()->id;
        $entity->created_by = user::logged_in()->id;
        $entity->location = $location;
        $entity->status = self::STATUS_ACTIVE;
        $entity->save();

        $event = event\evidence_type_created::create_from_type($entity);
        $event->trigger();

        return new static($entity);
    }

    /**
     * Update an existing evidence type
     *
     * @param string|null $name
     * @param string|null $idnumber
     * @param string|null $description
     * @param string|null $descriptionformat
     *
     * @return evidence_type
     */
    public function update(
        string $name = null,
        string $idnumber = null,
        string $description = null,
        string $descriptionformat = null,
        int $location = null
    ): self {
        if ($name === null && $idnumber === null && $description === null && $descriptionformat === null) {
            throw new coding_exception('Must specify an attribute to change');
        }
        $this->can_modify(true);
        if ($idnumber != '' && totara_idnumber_exists(entity\evidence_type::TABLE, $idnumber, $this->entity->id)) {
            throw new coding_exception('ID number already exists');
        }

        if ($name != '') {
            $this->entity->name = $name;
        }
        if ($idnumber !== null) {
            $this->entity->idnumber = $idnumber;
        }
        if ($description !== null) {
            $this->entity->description = $description;
        }
        if ($descriptionformat !== null) {
            $this->entity->descriptionformat = $descriptionformat;
        }
        if ($location !== null) {
            $this->entity->location = $location;
        }
        $this->entity->modified_by = user::logged_in()->id;
        $this->entity->save();
        $this->entity->refresh();

        $event = event\evidence_type_updated::create_from_type($this->entity);
        $event->trigger();

        return $this;
    }

    /**
     * Delete this evidence type and its associated fields
     */
    public function delete(): void {
        $this->can_modify(true);

        $event = builder::get_db()->transaction(function () {
            $event = event\evidence_type_deleted::create_from_type(new entity\evidence_type($this->entity->id));

            $file_storage = get_file_storage();
            foreach ($this->entity->fields as $field) {
                /** @var entity\evidence_type_field $field */
                // Delete any files used in the default value for a text area field
                if ($field->datatype === 'textarea') {
                    $file_storage->delete_area_files(
                        context_system::instance()->id,
                        customfield_area\evidence::get_filearea_component(),
                        'textarea',
                        $field->id
                    );
                }

                $field->delete();
            }
            $this->entity->delete();

            return $event;
        });

        $event->trigger();
    }

    /**
     * Set the status of this type
     *
     * @param int $status One of the STATUS constants defined in this class
     *
     * @return self
     */
    public function update_status(int $status): self {
        if (!in_array($status, [
            self::STATUS_ACTIVE,
            self::STATUS_HIDDEN,
        ])) {
            throw new coding_exception("Invalid status '{$status}' provided to evidence type with ID {$this->id}");
        }
        $this->can_modify(true, false);

        $this->entity->status = $status;
        $this->entity->save();
        $this->entity->refresh();

        $event = event\evidence_type_updated::create_from_type($this->entity);
        $event->trigger();

        return $this;
    }

    /**
     * This evidence type's name
     *
     * @return string
     */
    public function get_display_name(): string {
        return format_string(multilang_helper::parse_type_name_string($this->name));
    }

    /**
     * This evidence type's ID number
     *
     * @return string
     */
    public function get_display_idnumber(): string {
        return s($this->idnumber);
    }

    /**
     * This evidence type's description
     *
     * @return string
     */
    public function get_display_description(): string {
        global $CFG;
        require_once("$CFG->dirroot/lib/filelib.php");

        $context = context_system::instance();

        $description = multilang_helper::parse_type_description_string($this->description);

        $description = file_rewrite_pluginfile_urls(
            $description,
            'pluginfile.php',
            $context->id,
            'totara_evidence',
            self::DESCRIPTION_FILEAREA,
            $this->get_id()
        );

        return format_text($description, $this->get_descriptionformat(), ['context' => $context]);
    }

    /**
     * Get the format for the description field.
     * Defaults to HTML.
     *
     * @return string
     */
    public function get_descriptionformat(): string {
        return $this->entity->descriptionformat ?? FORMAT_HTML;
    }

    /**
     * Get data
     *
     * @param bool $include_items If true, it includes evidence_items.
     * @return array
     */
    public function get_data(bool $include_items = true): array {
        return array_merge(parent::get_data(), [
            'fields' => $this->entity->fields->to_array(),
            'items' => $include_items ? $this->entity->items->to_array() : [],
            'is_visible' => $this->is_visible(),
            'is_system' => $this->is_system(),
            'display_idnumber' => $this->get_display_idnumber(),
            'display_description' => $this->get_display_description(),
            'descriptionformat' => $this->get_descriptionformat()
        ]);
    }

    /**
     * Is the user allowed to manage this evidence type?
     *
     * @param bool $require If true throws an exception, otherwise returns false if not allowed
     * @return bool
     * @throws required_capability_exception
     */
    public static function can_manage(bool $require = false): bool {
        $context = context_system::instance();
        if ($require) {
            require_capability('totara/evidence:managetype', $context);
        }
        return has_capability('totara/evidence:managetype', $context);
    }

}