<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;


use coding_exception;
use core\collection;
use ReflectionClass;
use stdClass;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\entity\pathway_achievement;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * Base class for pathway plugins
 */
abstract class pathway {

    /* Status constants */
    public const PATHWAY_STATUS_ACTIVE = 0;
    public const PATHWAY_STATUS_ARCHIVED = 3;

    /* Classification constants */
    public const PATHWAY_MULTI_VALUE = 0;
    public const PATHWAY_SINGLE_VALUE = 1;

    public const CLASSIFICATION = self::PATHWAY_MULTI_VALUE;

    /** @var int */
    private $id = 0;

    /** @var competency Competency this relates to. */
    private $competency;

    /** @var int sortorder Sorting order of this pathway within the set */
    private $sortorder = 0;

    /** @var string $path_type Type of pathway instance. Obtained from class */
    private $path_type;

    /** @var int Specific instance id */
    private $path_instance_id;

    /** @var int $status  */
    private $status = self::PATHWAY_STATUS_ACTIVE;

    /** @var bool $valid  */
    protected $valid = false;

    /** @var bool $saved_valid */
    private $saved_valid = false;

    /** @var bool $validated - Bookkeeping to prevent unnecessary validation */
    protected $validated = false;

    /********************************************************************
     * Instantiation
     ********************************************************************/

    final public function __construct() {
        $reflect = new ReflectionClass($this);
        $this->path_type = $reflect->getShortName();
    }

    public static function pathway_type(): string {
        $reflect = new \ReflectionClass(static::class);
        return $reflect->getShortName();
    }


    /**
     * Fetch the instance data from the db
     *
     * @param  int $id
     * @return static
     */
    final public static function fetch(int $id): pathway {
        $entity = pathway_entity::repository()->find_or_fail($id);

        return static::from_entity($entity);
    }

    /**
     * Set the instance attributes from the record
     *
     * @param pathway_entity $entity
     * @return static
     */
    final public static function from_entity(pathway_entity $entity): pathway {
        $classname = self::get_pathway_class($entity);

        /** @var pathway $pathway */
        $pathway = new $classname();
        if ($pathway->path_type !== $entity->path_type) {
            throw new coding_exception('Path type mismatch');
        }

        if (!$entity->competency) {
            throw new coding_exception('Competency for given pathway not found');
        }

        $pathway->set_id($entity->id);
        $pathway->set_competency($entity->competency);
        $pathway->set_sortorder($entity->sortorder);
        $pathway->set_path_instance_id($entity->path_instance_id);
        $pathway->set_status($entity->status);
        $pathway->set_valid($entity->valid);
        $pathway->set_saved_valid($entity->valid);

        $pathway->fetch_configuration();

        return $pathway;
    }

    /**
     * @param stdClass|pathway_entity $pathway
     * @return string
     * @throws coding_exception
     */
    protected static function get_pathway_class($pathway): string {
        $classname = "\\pathway_{$pathway->path_type}\\{$pathway->path_type}";
        if (!class_exists($classname) || !is_subclass_of($classname, pathway::class)) {
            throw new coding_exception("Pathway type '{$pathway->path_type}' does not exist or is not enabled.");
        }
        return $classname;
    }

    /**
     * Load the data specific to the type of pathway.
     */
    abstract protected function fetch_configuration(): void;


    /****************************************************************************
     * Saving
     ****************************************************************************/

    private function ensure_sortorder_exists() {
        global $DB;

        if (empty($this->sortorder)) {
            $sql = "SELECT MAX(sortorder)
                      FROM {totara_competency_pathway}
                     WHERE competency_id = :competency_id
                       AND status = :active";
            $highest_current = $DB->get_field_sql(
                $sql,
                ['competency_id' => $this->get_competency()->id, 'active' => static::PATHWAY_STATUS_ACTIVE]
            );

            // Explicit check of false since the value could be 0.
            if ($highest_current === false || $highest_current === null) {
                $this->set_sortorder(1);
            } else {
                $this->set_sortorder($highest_current + 1);
            }
        }
    }

    /**
     * Save the pathway
     * @return $this
     */
    final public function save(): pathway {
        global $DB;

        if (empty($this->get_competency())) {
            throw new coding_exception('Unknown Competency');
        }

        $this->ensure_sortorder_exists();
        $this->validate();

        // Check whether anything changed
        $exists = !empty($this->get_id());
        if (!$exists || $this->valid != $this->saved_valid || $this->configuration_is_dirty()) {
            if ($this->is_active()) {
                $this->save_configuration();
            }
        } else if (!empty($this->get_id())) {
            $old_record = $DB->get_record('totara_competency_pathway', ['id' => $this->get_id()]);
            // Only save if certain values change
            if ($old_record->sortorder == $this->get_sortorder()
                && $old_record->status == $this->get_status()
                && $old_record->valid == $this->is_valid()
                && $old_record->path_instance_id == $this->get_path_instance_id()
            ) {
                return $this;
            }
        }

        // If we get here, we have either a new pathway or something changed
        $record = new stdClass();
        $record->competency_id = $this->competency->id;
        $record->sortorder = $this->get_sortorder();
        $record->path_type = $this->get_path_type();
        $record->path_instance_id = $this->get_path_instance_id();
        $record->status = $this->status;
        $record->valid = $this->valid;
        $record->pathway_modified = time();

        if ($this->get_id()) {
            $record->id = $this->get_id();
            $DB->update_record('totara_competency_pathway', $record);
        } else {
            $this->id = $DB->insert_record('totara_competency_pathway', $record);
        }

        $this->saved_valid = $this->valid;

        return $this;
    }

    /**
     * Has the pathway specific configuration changed?
     *
     * @return bool
     */
    abstract protected function configuration_is_dirty(): bool;

    /**
     * Save the pathway specific configuration
     */
    abstract protected function save_configuration();

    /**
     * Archive the pathway
     * @return $this
     */
    private function archive(): pathway {
        if (empty($this->get_id())) {
            return $this;
        }

        // We only archive the pathway.
        // Configuration is deleted
        $this->delete_configuration();
        $this->set_status(static::PATHWAY_STATUS_ARCHIVED);

        // IMPORTANT: We deliberately do not archive pathway_achievements here
        // so that our aggregation task picks all archived pathways up which
        // still have active pathway_achievements
        $this->save();

        return $this;
    }

    /**
     * Archive pathway achievements of this pathway
     */
    public function archive_pathway_achievements() {
        pathway_achievement::repository()
            ->where('status', pathway_achievement::STATUS_CURRENT)
            ->where('pathway_id', $this->get_id())
            ->update([
                'last_aggregated' => time(),
                'status' => pathway_achievement::STATUS_ARCHIVED
            ]);
    }

    /**
     * 'Delete' the pathway and all its associated configuration
     * @return $this
     */
    final public function delete() {
        if ($this->is_active()) {
            $this->archive();
        }

        return $this;
    }

    /**
     * Delete the pathway specific detail
     */
    abstract protected function delete_configuration(): void;

    /**
     * Delete all pathway data relating to a specific competency.
     *
     * @param competency|int $competency Competency entity or ID
     */
    final public static function delete_all_for_competency($competency) {
        global $DB;
        $DB->transaction(function () use ($competency) {
            $pathways = pathway_entity::repository()
                ->where('competency_id', $competency->id ?? $competency);

            foreach ($pathways->get() as $pathway) {
                static::fetch($pathway->id)->delete_configuration();

                pathway_achievement::repository()
                    ->where('pathway_id', $pathway->id)
                    ->delete(false);
            }

            $pathways->delete();
        });
    }

    /**
     * Save the valid value of the pathway
     *
     * @return $this
     */
    public function save_valid(): pathway {
        // Not doing anything if not saved previously
        if (empty($this->id)) {
            return $this;
        }

        /** @var pathway_entity $pathway */
        $pathway = new pathway_entity($this->id);
        $pathway->valid = $this->valid;
        $pathway->save();

        $this->saved_valid = $this->valid;

        return $this;
    }


    /****************************************************************************
     * Getters and setters
     ****************************************************************************/

    /**
     * @return int|null Id of the pathway
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * @param int|null $id New pathway id
     * @return $this
     */
    private function set_id(?int $id): pathway {
        $this->id = $id;
        return $this;
    }

    /**
     * @return competency
     */
    public function get_competency(): competency {
        return $this->competency;
    }

    /**
     * @param competency $competency
     * @return $this
     */
    public function set_competency(competency $competency): pathway {
        $this->competency = $competency;

        return $this;
    }

    /**
     * @return int Current sortorder
     */
    public function get_sortorder(): int {
        return $this->sortorder;
    }

    /**
     * @param int $sortorder
     * @return $this
     */
    public function set_sortorder(int $sortorder): pathway {
        $this->sortorder = $sortorder;
        return $this;
    }

    /**
     * @return string
     */
    public function get_path_type(): string {
        return $this->path_type;
    }

    // No set_path_type - derived from class

    /**
     * @return int|null Path instance id
     */
    public function get_path_instance_id(): ?int {
        return $this->path_instance_id;
    }

    /**
     * @param int|null Path instance id
     * @return $this
     */
    protected function set_path_instance_id(?int $instance_id): pathway {
        $this->path_instance_id = $instance_id;
        return $this;
    }

    /**
     * @return bool
     */
    public function is_active(): bool {
        return $this->status == static::PATHWAY_STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function is_archived(): bool {
        return $this->status == static::PATHWAY_STATUS_ARCHIVED;
    }

    /**
     * @return int
     */
    public function get_status(): int {
        return $this->status;
    }

    /**
     * Return a string representation of the pathway status
     *
     * @return string
     */
    public function get_status_name(): string {
        $string_keys = [
            static::PATHWAY_STATUS_ACTIVE => 'ACTIVE',
            static::PATHWAY_STATUS_ARCHIVED => 'ARCHIVED',
        ];

        if (!isset($string_keys[$this->status])) {
            throw new coding_exception('Missing string representation for pathway classification');
        }

        return $string_keys[$this->status];
    }

    /**
     * Set the pathway status
     *
     * @param int $status New status
     * @return $this
     */
    protected function set_status(int $status): pathway {
        if ($status !== static::PATHWAY_STATUS_ACTIVE
            && $status !== static::PATHWAY_STATUS_ARCHIVED
        ) {
            throw new coding_exception('Unknown pathway status');
        }
        $this->status = $status;

        return $this;
    }

    /**
     * @return bool
     */
    public function is_valid(): bool {
        return $this->valid;
    }

    /**
     * @param bool $valid
     * @return pathway
     */
    public function set_valid(bool $valid): pathway {
        $this->valid = $valid;
        return $this;
    }

    /**
     * @param bool $valid
     * @return pathway
     */
    public function set_saved_valid(bool $valid): pathway {
        $this->saved_valid = $valid;
        return $this;
    }

    /**
     * @return bool
     */
    public function is_validated(): bool {
        return $this->validated;
    }

    /**
     * Get pathway classification (Single or Multi value)
     *
     * @return int
     */
    public function get_classification(): int {
        return static::CLASSIFICATION;
    }

    /**
     * Return a string presentation of the pathway classification
     *
     * @return string
     */
    public function get_classification_name(): string {
        $string_keys = [
            static::PATHWAY_MULTI_VALUE => 'MULTIVALUE',
            static::PATHWAY_SINGLE_VALUE => 'SINGLEVALUE',
        ];

        if (!isset($string_keys[static::CLASSIFICATION])) {
            throw new coding_exception('Missing string representation for pathway classification');
        }

        return $string_keys[static::CLASSIFICATION];
    }

    /**
     * Get human readable label for this pathway.
     * All pathways of classification single will have the same label if this is not overriden
     *
     * @return string
     */
    public static function get_label(): string {
        if ((new static())->get_classification() === static::PATHWAY_SINGLE_VALUE) {
            return get_string('achievement_path_group_label_single', 'totara_competency');
        }
        // overwrite in children
        debugging('This pathway does not have a label. Every pathway needs a label so that it shows up correctly in the interface.', DEBUG_DEVELOPER);
        return 'na';
    }

    /**
     * Returns the scale value associated with this instance of this pathway.
     *
     * Override this method for pathways that can be set to correspond to particular value.
     *
     * @return scale_value|null A null return value indicates that any scale value may be returned
     */
    public function get_scale_value(): ?scale_value {
        return null;
    }

    /**
     * Return a summary of the set of criteria associated with this pathway.
     * Criteria detail should contain a title with optional items and aggregation
     *
     * This should be overwritten by plugins to provide their information
     *
     * @return array List of summarized criteria associated with the pathway
     */
    public function get_summarized_criteria_set(): array {
        return [(object)['item_type' => $this->get_title()]];
    }

    /**
     * Get the achievement detail object for this pathway type
     *
     * @return base_achievement_detail
     */
    public function get_achievement_detail(): base_achievement_detail {
        $detail_class = pathway_factory::get_namespace($this->get_path_type()) . '\\achievement_detail';
        if (!is_subclass_of($detail_class, base_achievement_detail::class)) {
            throw new coding_exception('Not detail class found', "No achievement_detail class found for {$this->get_path_type()}");
        }
        return new $detail_class();
    }

    /**
     * Validate and set the pathway configuration validit
     * @return $this
     */
    final public function validate(): pathway {
        if ($this->is_validated()) {
            return $this;
        }

        $this->valid = $this->is_configuration_valid();
        $this->validated = true;

        return $this;
    }

    /**
     * Validate the configuration
     * Should be overridden by plugins
     * @return bool
     */
    protected function is_configuration_valid(): bool {
        return true;
    }

    /**
     * Can satisfying this pathway's criteria lead to proficiency
     * @return bool
     */
    public function leads_to_proficiency(): bool {
        if (!$this->is_active() || !$this->is_valid()) {
            return false;
        }

        if ($this->get_classification() == self::PATHWAY_MULTI_VALUE) {
            return true;
        }

        $scale_value = $this->get_scale_value();
        if (is_null($scale_value)) {
            debugging('A single value pathway without a scale value exists in the single_value pathway.');
            return false;
        }

        return (bool)$scale_value->proficient;
    }

    /**
     * Is this a single-use pathway
     * Plugins should overwrite if required
     *
     * @return bool
     */
    public function is_singleuse(): bool {
        return false;
    }

    /**
     * Is this specific pathway type enabled - i.e. can it be added as a pathway?
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return true;
    }


    /*******************************************************************************************************
     * Data exporting
     *******************************************************************************************************/

    /**
     * Return the name of the template to use for editing this pathway
     * Plugins should overwrite if required
     *
     * @return string Template name
     */
    public function get_edit_template(): string {
        return '';
    }

    /**
     * Return the tite for the pathway
     * Plugins should overwrite if required
     *
     * @return string
     */
    public function get_title(): string {
        $classname = (new ReflectionClass($this))->getShortName();
        return ucfirst(get_string('pluginname', "pathway_{$classname}"));
    }

    /**
     * Return a short description of the content of this pathway
     * Plugins should overwrite if required
     *
     * @return string Short description
     */
    public function get_short_description(): string {
        return '';
    }

    /**
     * Export the pathway overview.
     * This contains only enough information to list the pathway
     * Plugins should overwrite if required
     *
     * @return array
     */
    public function export_edit_overview(): array {
        $id = $this->get_id();
        $result = [
            'id' => $id,
            'key' => "pw_$id",
            'type' => $this->get_path_type(),
            'title' => $this->get_title(),
            'sortorder' => $this->get_sortorder(),
            'classification' => $this->get_classification(),
            'pathway_templatename' => $this->get_edit_template(),
            'criteria_type_level' => $this->get_path_type(),
            'singleuse' => $this->is_singleuse(),
        ];

        if (!$this->is_valid()) {
            $result['error'] = get_string('error_invalid_configuration', 'totara_competency');
        }

        if ($this->get_classification() == static::PATHWAY_SINGLE_VALUE) {
            $result['scalevalue'] = $this->get_scale_value()->id;
        }

        return $result;
    }

    /**
     * Export detail for editing the pathway
     * This contains detail information
     * Plugins should overwrite if required
     *
     * @return array Exported detail
     */
    public function export_edit_detail(): array {
        return $this->export_edit_overview();
    }

    /**
     * Get the current configurations for all active pathways belonging to the specified competency in an associative array
     *
     * @param int $competency_id Competency id
     * @return array
     */
    public static function dump_competency_pathways(int $competency_id) {
        global $DB;

        $result = $DB->get_records('totara_competency_pathway', ['competency_id' => $competency_id, 'status' => static::PATHWAY_STATUS_ACTIVE]);
        foreach ($result as $id => $pathway) {
            if (!is_null($pathway->path_instance_id)) {
                $pathway->detail = pathway_factory::dump_pathway_configuration($pathway->path_type, $pathway->path_instance_id);
            } else {
                $pathway->detail = null;
            }
        }

        return $result;
    }

    /**
     * Retrieve the pathway configuration
     *
     * @param int|null $id Instance id
     * @return stdClass|null
     */
    public static function dump_pathway_configuration(?int $id = null) {
        return null;
    }


    /**************************************************************************
     * User specific
     **************************************************************************/

    /**
     * Calculates what value the user has achieved for this pathway.
     *
     * The value is included in the pathway's implementation of base_achievement_detail. This allows
     * us to get the scale value id from that instance and also information related to how the
     * value was achieved.
     *
     * @param int $user_id
     * @return base_achievement_detail as implemented by the pathway plugin in question
     */
    abstract public function aggregate_current_value(int $user_id): base_achievement_detail;


    /**************************************************************************
     * Copying pathways
     **************************************************************************/

    /**
     * Returns a list of pathways that has at most one single use pathway per
     * pathway type.
     *
     * @param collection<self>|self[] $pathways list of pathways to adjust.
     *
     * @return collection<self> the updated list.
     */
    final public static function remove_extra_single_use_pathways(
        $pathways
    ): collection {
        $raw = null;
        if ($pathways instanceof collection) {
            $raw = $pathways;
        } else if (is_array($pathways)) {
            $raw = collection::new($pathways);
        } else {
            throw new coding_exception(
                __METHOD__ . ' only accepts collection<pathway> or pathway[]'
            );
        }

        return $raw->reduce(
            function (stdClass $tuple, self $pathway): stdClass {
                if ($pathway->is_singleuse()) {
                    $type = $pathway->get_path_type();
                    if (in_array($type, $tuple->single_use)) {
                        return $tuple;
                    }

                    $tuple->single_use[] = $type;
                }

                $tuple->valid->append($pathway);
                return $tuple;
            },
            (object) ['single_use' => [], 'valid' => collection::new([])]
        )
        ->valid;
    }

    /**
     * Replicates this pathway at the specified competency.
     *
     * This should be overwritten by plugins to:
     * - create a new instance of this pathway
     * - configuring the new instance with sensible defaults
     * - adding the new instance to the specified competency.
     * - return the updated competency (_without_ reloading its changed pathways)
     *
     * @param competency $competency competency to copy pathway to.
     *
     * @return competency the updated competency.
     */
    public function copy_to_competency(competency $competency): competency {
        return $competency;
    }
}
