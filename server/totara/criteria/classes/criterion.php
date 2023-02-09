<?php
/*
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria;

use coding_exception;
use core\orm\query\builder;
use ReflectionClass;
use stdClass;
use totara_criteria\entity\criterion as criterion_entity;
use totara_criteria\entity\criteria_item as criteria_item_entity;
use totara_criteria\entity\criteria_metadata as criteria_metadata_entity;
use totara_criteria\hook\criteria_validity_changed;

/**
 * Base class for a single criterion.
 */

abstract class criterion {

    /** Aggregation constants */
    const AGGREGATE_ALL = 1;
    const AGGREGATE_ANY_N = 2;

    const METADATA_COMPETENCY_KEY = 'competency_id';

    /** @var int $id */
    private $id;

    /** @var string $type Type of criterion */
    private $plugin_type;

    /** @var string $idnumber */
    private $idnumber;

    /** @var int $aggregation_method Aggregation method to use */
    private $aggregation_method = self::AGGREGATE_ALL;

    /** @var array $aggregation_params Optional aggregation method parameters (e.g. min number of items) */
    private $aggregation_params = [];

    /** @var array $items */
    private $item_ids = [];

    /** @var array $metadata */
    private $metadata = [];

    /** @var int $last_evaluated */
    private $last_evaluated;

    /** @var bool $valid */
    private $valid = false;

    /** @var bool $saved_valid */
    private $saved_valid = false;

    /** @var bool $validated - Book keeping to prevent multiple checking of validity */
    private $validated = false;

    /**
     * @var null
     */
    private $last_achieved_date;

    /**
     * Constructor.
     */
    public function __construct() {
        $reflect = new ReflectionClass($this);
        $this->plugin_type = $reflect->getShortName();
    }

    public static function criterion_type(): string {
        $reflect = new \ReflectionClass(static::class);
        return $reflect->getShortName();
    }

    /**
     * Fetch specific criterion from the database
     *
     * @param int $id Id of the criterion to fetch
     * @return criterion $this
     */
    final public static function fetch(int $id): criterion {
        $criterion = criterion_entity::repository()->find_or_fail($id);
        return static::fetch_from_entity($criterion);
    }

    /**
     * Fetch specific criterion from the database
     *
     * @param criterion_entity $criterion $criterion
     * @return criterion $this
     */
    final public static function fetch_from_entity(criterion_entity $criterion): criterion {
        $instance = new static();

        if ($criterion->plugin_type != $instance->get_plugin_type()) {
            throw new coding_exception("The specified criterion id is for another type of criterion");
        }

        $instance->set_id($criterion->id);
        $instance->set_idnumber($criterion->idnumber);
        $instance->set_aggregation_method($criterion->aggregation_method ?? static::AGGREGATE_ALL);
        $instance->set_aggregation_params($criterion->aggregation_params ?? []);
        $instance->set_last_evaluated($criterion->last_evaluated);
        $instance->set_valid($criterion->valid);
        $instance->set_saved_valid($criterion->valid);

        $instance->fetch_items($criterion);
        $instance->fetch_metadata($criterion);

        return $instance;
    }

    /*******************************************************************************************************
     * Getters and setters
     *******************************************************************************************************/

    /**
     * Get the id of the criterion
     *
     * Public scope for testing purposes
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Set the id of the criterion
     *
     * @param int|null $id New criterion id
     * @return $this
     */
    public function set_id(?int $id): criterion {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the ID number of the criterion
     *
     * Public scope for testing purposes
     *
     * @return string|null
     */
    public function get_idnumber(): ?string {
        return $this->idnumber;
    }

    /**
     * Set the ID number of the criterion
     *
     * @param string|null $idnumber New criterion ID number
     * @return $this
     */
    public function set_idnumber(?string $idnumber): criterion {
        if (!empty($idnumber)) {
            $this->idnumber = $idnumber;
        }
        return $this;
    }

    /**
     * Get the plugin type
     *
     * Public scope for testing purposes

     * @return string
     */
    public function get_plugin_type(): string {
        return $this->plugin_type;
    }

    /**
     * Get the aggregation method
     *
     * Public scope for testing purposes
     *
     * @return int Aggregation method
     */
    public function get_aggregation_method(): int {
        return $this->aggregation_method;
    }

    /**
     * Set the aggregation method
     *
     * Public scope for testing purposes
     *
     * @param  int $aggregation_method
     * @return $this
     */
    public function set_aggregation_method(int $aggregation_method): criterion {
        if (!in_array($aggregation_method, [static::AGGREGATE_ALL, static::AGGREGATE_ANY_N])) {
            throw new coding_exception('Invalid aggregation method used');
        }

        $this->aggregation_method = $aggregation_method;
        $this->validated = false;

        return $this;
    }

    /**
     * Get the aggregation params
     *
     * Public scope for testing purposes
     *
     * @return array Aggregation parameters
     */
    public function get_aggregation_params(): array {
        return $this->aggregation_params ?? [];
    }

    /**
     * Get the number of required aggregation items
     *
     * Public scope for testing purposes
     *
     * @return int
     */
    public function get_aggregation_num_required(): int {
        if ($this->aggregation_method == static::AGGREGATE_ALL) {
            return empty($this->get_item_ids()) ? 1 : count($this->get_item_ids());
        }

        $params = $this->aggregation_params ?? [];
        return $params['req_items'] ?? 1;
    }

    /**
     * Set the aggregation params
     *
     * Public scope for testing purposes
     *
     * @param array|string $params. This can be passed as array or json encoded string
     * @return $this
     */
    public function set_aggregation_params($params): criterion {
        if (is_string($params)) {
            // Json encoded
            $this->aggregation_params = json_decode($params, true);
        } else if (!is_null($params) && !is_array($params)) {
            $this->aggregation_params = (array)$params;
        } else {
            $this->aggregation_params = $params;
        }

        $this->validated = false;
        return $this;
    }

    /**
     * Get the title for the criterion
     *
     * @return string
     */
    public function get_title() {
        $classname = (new ReflectionClass($this))->getShortName();
        return get_string('pluginname', "criteria_{$classname}");
    }

    /**
     * Get the type of items associated with this criterion
     * V1 - Assuming that a criterion can only store items of a single type
     */
    abstract public function get_items_type();

    /**
     * Get the ids of items associated with this criterion
     *
     * Public scope for testing purposes
     *
     * @return array
     */
    public function get_item_ids() {
        return $this->item_ids;
    }

    /**
     * Replace the ids of items associated with this criterion
     *
     * @param int[] $itemids Id of items to link to the criterion
     * @return $this
     */
    public function set_item_ids(array $itemids): criterion {
        $this->item_ids = $itemids;
        sort($this->item_ids);

        $this->validated = false;
        return $this;
    }

    /**
     * Add items to the criterion
     *
     * @param int[] $item_ids Id of items to link to the criterion
     * @return criterion
     */
    public function add_items(array $item_ids): criterion {
        $newitems = array_diff($item_ids, $this->item_ids);
        $this->item_ids = array_merge($this->item_ids, $newitems);
        sort($this->item_ids);

        $this->validated = false;
        return $this;
    }

    /**
     * Remove the specified items
     *
     * @param int[] $item_ids Ids of items to remove
     * @return criterion
     */
    public function remove_items(array $item_ids): criterion {
        $this->item_ids = array_diff($this->item_ids, $item_ids);

        $this->validated = false;
        return $this;
    }

    /**
     * Get the metadata of this criterion
     *
     * @return array
     */
    public function get_metadata() {
        return $this->metadata;
    }

    /**
     * Replace the metadata of this criterion
     *
     * @param stdClass[] $metadata Metadata metakey/metavalue pairs
     * @return $this
     */
    public function set_metadata(array $metadata): criterion {
        $this->metadata = [];
        $this->add_metadata($metadata);
        return $this;
    }

    /**
     * Add metadata to the criterion
     *
     * @param stdClass[]|criteria_metadata_entity[] $metadata Metadata metakey/metavalue pairs
     * @return criterion
     */
    public function add_metadata($metadata): criterion {
        foreach ($metadata as $obj) {
            if (is_array($obj)) {
                $obj = (object)$obj;
            }

            if (empty($obj->metakey) || !isset($obj->metavalue)) {
                throw new coding_exception("Criterion metadata requires a metakey / metavalue pair");
            }
            $this->metadata[$obj->metakey] = $obj->metavalue;
        }

        return $this;
    }

    /**
     * Remove the specified metadata keys
     *
     * @param int[] $metakeys Keys of metadata to remove
     * @return criterion
     */
    public function remove_metadata(array $metakeys): criterion {
        foreach ($metakeys as $metakey) {
            unset($this->metadata[$metakey]);
        }

        return $this;
    }

    /**
     * Set the associated competency
     *
     * @param int $competency_id
     * @return criterion
     */
    public function set_competency_id(int $competency_id): criterion {
        $this->add_metadata([
            (object)['metakey' => self::METADATA_COMPETENCY_KEY, 'metavalue' => $competency_id],
        ]);
        return $this;
    }

    /**
     * Get the associated competency
     *
     * @return int|null
     */
    public function get_competency_id(): ?int {
        foreach ($this->get_metadata() as $metakey => $metaval) {
            if ($metakey == static::METADATA_COMPETENCY_KEY) {
                return $metaval;
            }
        }

        return null;
    }

    /**
     * Get last_evaluated
     *
     * @return int|null
     */
    public function get_last_evaluated(): ?int {
        return $this->last_evaluated;
    }

    /**
     * Set last_evaluated
     *
     * @param int|null $last_evaluated
     * @return criterion
     */
    public function set_last_evaluated(?int $last_evaluated): criterion {
        $this->last_evaluated = $last_evaluated;
        return $this;
    }

    /**
     * Get the last achieved date
     *
     * @return int|null
     */
    public function get_last_achieved_date(): ?int {
        return $this->last_achieved_date;
    }

    /**
     * @return bool
     */
    public function is_valid(): bool {
        return $this->valid;
    }

    /**
     * @param bool $valid
     * @return criterion
     */
    public function set_valid(bool $valid): criterion {
        $this->valid = $valid;
        return $this;
    }

    /**
     * @param bool $valid
     * @return criterion
     */
    public function set_saved_valid(bool $valid): criterion {
        $this->saved_valid = $valid;
        return $this;
    }

    /**
     * Validate whether the criterion's items are valid.
     * Should be overridden by plugin where needed
     *
     * @return bool
     */
    protected function items_are_valid(): bool {
        $nitems = count($this->get_item_ids());

        $validator_class = static::get_item_validator_class();
        if (is_null($validator_class)) {
            return true;
        }

        $nrequired = $this->get_aggregation_num_required();
        if ($nitems < $nrequired) {
            return false;
        }

        $nvalid = 0;

        foreach ($this->get_item_ids() as $item_id) {
            if ($validator_class::validate_item($item_id)) {
                $nvalid++;
            } else {
                // If any item is invalid, the criterion is considered as invalid - can thus stop here
                return false;
            }
        }

        return $nvalid >= $nrequired;
    }

    /**
     * Validate and set the criterion validity
     */
    public function validate() {
        if ($this->validated) {
            return;
        }

        $exists = (bool) $this->get_id();
        if (!$exists && empty($this->item_ids)) {
            $this->update_items();
        }

        $this->valid = $this->items_are_valid();
        $this->validated = true;
    }

    /**
     * Does this criterion have associated items
     * Plugins should overwrite if required
     *
     * @return bool
     */
    public function has_items(): bool {
        return true;
    }

    /**
     * Does this criterion allow for aggregation between items
     * Plugins should overwrite if required
     *
     * @return bool
     */
    public function has_aggregation(): bool {
        return true;
    }

    /**
     * Does this criterion have associated metadata
     * Plugins should overwrite if required
     *
     * @return bool
     */
    public function has_metadata(): bool {
        return true;
    }

    /**
     * Is this a single-use criterion
     * Plugins should overwrite if required
     *
     * @return bool
     */
    public function is_singleuse(): bool {
        return false;
    }

    /**
     * Return the display instance for this criterion
     *
     * @return criterion_display
     */
    public function display_instance(): criterion_display {
        $classname = $this->get_display_class();
        return new $classname($this);
    }

    /**
     * Return the display class for this criterion
     *
     * @return string Class to use for displaying
     */
    abstract protected function get_display_class(): string;


    /*******************************************************************************************************
     * Retrieve and Save
     *******************************************************************************************************/

    /**
     * Load the items belonging to this criterion
     *
     * @param criterion_entity $criterion
     */
    private function fetch_items(criterion_entity $criterion) {
        $this->item_ids = [];

        $rows = $criterion->items;
        foreach ($rows as $row) {
            // item_id is the actual item_id, (course_id, etc)
            $this->item_ids[] = $row->item_id;
        }
        sort($this->item_ids);

        $this->validated = false;
    }

    /**
     * Load the metadata of this criterion
     *
     * @param criterion_entity $criterion
     */
    private function fetch_metadata(criterion_entity $criterion) {
        $this->add_metadata($criterion->metadata);
    }

    /**
     * Check whether any information on the criterion changed from the saved values
     *
     * @return bool
     */
    public function is_dirty(): bool {
        if (empty($this->get_id())) {
            return true;
        }

        if ($this->valid != $this->saved_valid) {
            return true;
        }

        $existing_crit = self::fetch($this->get_id());

        $tst_methods = [
            'get_aggregation_method',
            'get_aggregation_params',
            'get_idnumber',
            'get_item_ids',
            'get_metadata',
        ];

        foreach ($tst_methods as $method) {
            if ($existing_crit->{$method}() != $this->{$method}()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate the criterion attributes
     *
     * @return string|null Error description
     */
    protected function validate_attributes(): ?string {
        return null;
    }

    /**
     * @return string|null Class name of item validator for this criteria type.
     */
    public static function get_item_validator_class(): ?string {
        return null;
    }

    /**
     * Save this criterion and all its items
     * @param bool $execute_hook
     * @return $this
     */
    public function save(bool $execute_hook = true): criterion {
        $err_message = $this->validate_attributes();
        if (!is_null($err_message)) {
            throw new coding_exception($err_message);
        }

        $exists = (bool) $this->get_id();
        $this->validate();

        if (!$this->is_dirty()) {
            return $this;
        }

        $criterion = $exists ? new criterion_entity($this->get_id()) : new criterion_entity();
        $criterion->plugin_type = $this->get_plugin_type();

        if (!empty($this->get_idnumber()) && totara_idnumber_exists(criterion_entity::TABLE, $this->get_idnumber(), $this->id)) {
            throw new coding_exception("ID number '{$this->get_idnumber()}' already exists in " . criterion_entity::TABLE);
        }
        $criterion->idnumber = $this->get_idnumber();

        $criterion->aggregation_method = $this->get_aggregation_method();
        $criterion->aggregation_params = json_encode($this->get_aggregation_params());
        $criterion->criterion_modified = time();
        $criterion->valid = $this->valid;
        $criterion->save();

        $this->set_id($criterion->id);

        $this->save_items();
        $this->save_metadata();

        // Hook must be triggered after the items are saved
        if ($execute_hook && $exists && $this->valid != $this->saved_valid) {
            $hook = new criteria_validity_changed([$this->id]);
            $hook->execute();
        }

        $this->set_saved_valid($this->valid);

        return $this;
    }

    /**
     * Save the criterion items
     */
    private function save_items() {
        $current_items = criteria_item_entity::repository()
            ->where('criterion_id', $this->get_id())
            ->get()
            ->all();

        $current_items = array_column($current_items, 'id', 'item_id');

        foreach ($this->get_item_ids() as $item_id) {
            $this->save_criterion_item($item_id);
            unset($current_items[$item_id]);
        }

        if (!empty($current_items)) {
            criteria_item_entity::repository()
                ->where('id', $current_items)
                ->delete();
        }
    }

    /**
     * Update derived items (e.g. currently linked courses, current child competencies, etc.)
     */
    abstract public function update_items(): criterion;

    /**
     * Save the metadata of this criterion
     */
    private function save_metadata() {
        $current_keys = criteria_metadata_entity::repository()
            ->where('criterion_id', $this->get_id())
            ->get()
            ->all();

        $current_keys = array_column($current_keys, 'id', 'metakey');

        if (!empty($this->metadata)) {
            foreach ($this->metadata as $metakey => $metaval) {
                if (isset($current_keys[$metakey])) {
                    $metadata = new criteria_metadata_entity($current_keys[$metakey]);
                    unset($current_keys[$metakey]);
                } else {
                    $metadata = new criteria_metadata_entity();
                }
                $metadata->metavalue = $metaval;
                $metadata->criterion_id = $this->id;
                $metadata->metakey = $metakey;
                $metadata->save();
            }
        }

        if (!empty($current_keys)) {
            criteria_metadata_entity::repository()
                ->where('id', $current_keys)
                ->delete();
        }
    }

    /**
     * Save the criterion_item if it doesn't exist
     *
     * @param int $item_id Item id to add
     * @return int $id Id of the criterion_item row
     */
    private function save_criterion_item(int $item_id): int {
        // Ensure there is only one of each item
        $item = criteria_item_entity::repository()
            ->where('item_type', $this->get_items_type())
            ->where('item_id', $item_id)
            ->where('criterion_id', $this->id)
            ->one();

        if (empty($item)) {
            // Insert the criterion_item
            $item = new criteria_item_entity();
            $item->criterion_id = $this->id;
            $item->item_type = $this->get_items_type();
            $item->item_id = $item_id;
            $item->save();
        }

        return $item->id;
    }

    /**
     * Save the last_evaluated date of the criterion
     *
     * @return $this
     */
    public function save_last_evaluated(): criterion {
        // Not doing anything if not saved previously or last_evaluated not yet set
        if (empty($this->get_id() or empty($this->get_last_evaluated()))) {
            return $this;
        }

        $criterion = new criterion_entity($this->get_id());
        $criterion->last_evaluated = $this->get_last_evaluated();
        $criterion->save();

        return $this;
    }

    /**
     * Save the valid value of the criterion
     *
     * @return $this
     */
    public function save_valid(): criterion {
        // Not doing anything if not saved previously - or last_evaluated not yet set
        if (empty($this->get_id())) {
            return $this;
        }

        $criterion = new criterion_entity($this->get_id());
        $criterion->valid = $this->valid;
        $criterion->save();

        $this->saved_valid = $this->valid;

        return $this;
    }


    /**
     * Delete the criterion
     */
    public function delete() {
        if (empty($this->get_id())) {
            // Never saved before
            return;
        }

        builder::get_db()->transaction(function () {
            // Delete all the items and metadata
            $this->delete_items();
            $this->delete_metadata();

            // Delete the actual criterion
            criterion_entity::repository()
                ->where('id', $this->get_id())
                ->delete();

            // Unset the id as it doesn't exist anymore
            $this->set_id(null);
        });
    }


    /**
     * Delete the items
     */
    private function delete_items() {
        global $DB;

        // TODO replace with ORM
        $sql =
            "DELETE
               FROM {totara_criteria_item_record}
              WHERE criterion_item_id IN
                (SELECT id
                   FROM {totara_criteria_item}
                  WHERE criterion_id = :criterionid)";
        $DB->execute($sql, ['criterionid' => $this->get_id()]);

        criteria_item_entity::repository()
            ->where('criterion_id', $this->get_id())
            ->delete();
    }

    /**
     * Delete the metadata
     */
    private function delete_metadata() {
        criteria_metadata_entity::repository()
            ->where('criterion_id', $this->get_id())
            ->delete();
    }

    /**
     * Dump the current criterion configuration from the database
     *
     * @param int $id Criterion id
     * @return stdClass Criterion configuration
     */
    public static function dump_criterion_configuration(int $id) {
        global $DB;

        if ($result = $DB->get_record('totara_criteria', ['id' => $id])) {
            $result->items = $DB->get_records('totara_criteria_item', ['criterion_id' => $id]);
            $result->metadata = $DB->get_records('totara_criteria_metadata', ['criterion_id' => $id]);

            return $result;
        }

        return null;
    }


    /*******************************************************************************************************
     * User evaluation
     *******************************************************************************************************/

    /**
     * Evaluate all items and aggregate the results to return a single value
     *
     * @param int $user_id Evaluate criteria satisfaction for this user
     * @return bool
     */
    public function aggregate(int $user_id): bool {
        if (empty($this->item_ids)) {
            return false;
        }

        // TODO: Should we allow for 0 completed??
        if ($this->aggregation_method == self::AGGREGATE_ANY_N && empty($this->aggregation_params['req_items'])) {
            // Default to min 1 item
            $this->aggregation_params['req_items'] = 1;
        }

        $overall = $this->aggregation_method == self::AGGREGATE_ALL;
        $num_completed = 0;

        $item_results = $this->get_item_results($user_id);
        $last_achieved_date = null;

        foreach ($item_results as $item_result) {
            // Aggregate this item completion result and previously evaluated results
            switch ($this->aggregation_method) {
                case self::AGGREGATE_ALL:
                    if (!$item_result->criterion_met) {
                        $overall = false;
                    } else {
                        if ($item_result->timeachieved && $item_result->timeachieved > $last_achieved_date) {
                            $last_achieved_date = $item_result->timeachieved;
                        }
                    }
                    break;

                case self::AGGREGATE_ANY_N:
                    if ($item_result->criterion_met) {
                        $num_completed += 1;
                        if ($num_completed >= (int)$this->aggregation_params['req_items']) {
                            $overall = true;
                        }
                        if ($item_result->timeachieved && $item_result->timeachieved > $last_achieved_date) {
                            $last_achieved_date = $item_result->timeachieved;
                        }
                    }
                    break;

                default:
                    break;
            }
        }

        $this->last_achieved_date = $last_achieved_date;

        return $overall;
    }

    /**
     * @return string that is class name of item_evaluator for this criteria type.
     */
    abstract public static function item_evaluator(): string;

    /**
     * Gets the item records for a user for this criterion. Does not update item records, but the returned results
     * will include elements where there were no corresponding item records. e.g. an item record hadn't been created
     * for a given user and item yet. The elements default to '0' (not complete).
     *
     * @param $user_id
     * @return array with item ids as keys and values of either '1' for criteria met or '0' for not met.
     */
    protected function get_item_results($user_id): array {
        global $DB;

        $sql = "
            SELECT
                i.id,
                COALESCE(r.criterion_met, 0) as criterion_met,
                r.timeachieved
            FROM {totara_criteria_item} i
            LEFT JOIN {totara_criteria_item_record} r ON
                i.id = r.criterion_item_id AND
                r.user_id = :userid
            WHERE i.criterion_id = :criterionid
              AND i.item_type = :itemtype
        ";

        $params = [
            'userid' => $user_id,
            'criterionid' => $this->get_id(),
            'itemtype' => $this->get_items_type()
        ];

        $existing_records = $DB->get_records_sql($sql, $params);

        return $existing_records;
    }


    /*******************************************************************************************************
     * Data exporting
     * TODO - remove once all APIs have been replaced by GraphQL
     *******************************************************************************************************/

    /**
     * @return string
     */
    public function export_configuration_error_description(): string {
        return '';
    }

    /**
     * Return the name of the template for defining this criterion
     * Plugins should overwrite if required
     *
     * @return string Edit template name
     */
    public function get_edit_template(): string {
        return '';
    }

    /**
     * Export the criterion overview data.
     * This contains only enough information to list the criterion
     * Plugins should overwrite if required
     *
     * @return array
     */
    public function export_edit_overview(): array {
        $result = [
            'id' => $this->get_id() ?? 0,
            'key' => $this->get_plugin_type() . '_' . $this->get_id() ?? 0,
            'type' => $this->get_plugin_type(),
            'title' => $this->get_title(),
            'singleuse' => $this->is_singleuse(),
            'criterion_templatename' => $this->get_edit_template(),
            'expandable' => !$this->is_singleuse(),
            'error' => $this->is_valid() ? '' : get_string('error_invalid_configuration', 'totara_criteria'),
        ];

        return $result;
    }

    /**
     * Exportdetail for editing the criterion
     * This contains detail information
     * Plugins should overwrite if required
     *
     * @return array Exported detail
     */
    public function export_edit_detail(): array {
        $results = $this->export_edit_overview();

        if ($this->has_items()) {
            $results['items'] = $this->export_edit_items();
        }

        if (!$this->is_valid()) {
            $results['error'] = $this->export_configuration_error_description();
        }

        if ($this->has_metadata()) {
            $results['metadata'] = $this->export_edit_metadata();
        }

        if ($this->has_aggregation()) {
            $results['aggregation'] = $this->export_edit_aggregation();
        }

        return $results;
    }

    /**
     * Export criterion aggregation data
     * Plugins should overwrite if required
     *
     * @return array
     */
    public function export_edit_aggregation(): array {
        return [
            'method' => $this->aggregation_method,
            'reqitems' => $this->aggregation_params['req_items'] ?? 1,
        ];
    }

    /**
     * Export criterion item data
     * Plugins should overwrite if required
     *
     * @return  array
     */
    public function export_edit_items(): array {
        return [];
    }

    /**
     * Export criterion metadata data
     * Plugins should overwrite if required
     *
     * @return  array
     */
    public function export_edit_metadata(): array {
        $results = [];

        foreach ($this->get_metadata() as $metakey => $metaval) {
            $results[] = [
                'metakey' => $metakey,
                'metavalue' => $metaval,
            ];
        }

        return $results;
    }

    /**
     * Replicates this criterion so that it can be used as a base for _new and
     * independent_ customizations.
     *
     * This should be overwritten by plugins to:
     * - create a new instance of this criterion
     * - configuring the new instance with sensible defaults. 'Sensible' here
     *   means copying generic configuration details; the new criterion is _not_
     *   a deep copy of the current one.
     *
     * @return ?self the criterion template or null if this criteria cannot be
     *         copied.
     */
    public function make_template(): ?self {
        return null;
    }
}
