<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\testing;

use core\entity\user;
use core\orm\collection;
use core\orm\query\builder;
use totara_evidence\customfield_area\evidence as customfields;
use totara_evidence\entity\evidence_field_data;
use totara_evidence\entity\evidence_item;
use totara_evidence\entity\evidence_type;
use totara_evidence\entity\evidence_type_field;
use totara_evidence\event\evidence_item_created;
use totara_evidence\event\evidence_type_created;
use totara_evidence\models;
use totara_job\job_assignment;
use totara_plan\entity\plan_evidence_relation;

final class generator extends \core\testing\component_generator {

    /**
     * @var int Number of evidence items to generate
     */
    protected $max_evidence_items;

    /**
     * @var int Number of evidence types to generate
     */
    protected $max_evidence_types;

    /**
     * @var int Number of users to generate
     */
    protected $max_evidence_users;

    /**
     * @var int|null Optional - minimum number of custom fields a type can have
     */
    protected $min_evidence_fields;

    /**
     * @var int|null Optional - maximum number of custom fields a type can have
     */
    protected $max_evidence_fields;

    /**
     * @var int An arbitrary date before now
     */
    protected $time_past;

    /**
     * @var int The current time
     */
    protected $time_now;

    /**
     * @var string[] Names of evidence types
     */
    protected $evidence_type_names;

    /**
     * @var array Predefined field settings
     */
    protected $evidence_field_data;

    /**
     * @var int The current tally of evidence items
     */
    protected $evidence_items_count;

    /**
     * @var int The current tally of evidence types
     */
    protected $evidence_types_count;

    /**
     * @var int The current tally of users
     */
    protected $evidence_users_count;

    /**
     * @var object[] Users that exist
     */
    protected $evidence_users;

    /**
     * @var evidence_type[] Evidence types that exist
     */
    protected $evidence_types;

    /**
     * @var evidence_item[] Evidence items that exist
     */
    protected $evidence_items;

    /**
     * Set this to true to create files automatically
     * @var bool
     */
    protected $is_creating_files = false;

    protected function __construct() {
        parent::__construct();
        $this->time_past = strtotime("2017-01-01T00:00:00+0000");
        $this->set_min_max();
    }

    /**
     * Set some variables and reset the generator.
     *
     * NOTE: can be used only once at the start of test.
     *
     * @param int $max_evidence_items
     * @param int $max_evidence_types
     * @param int $max_evidence_users
     * @param int $min_evidence_fields
     * @param int $max_evidence_fields
     */
    public function set_min_max(
        int $max_evidence_items = 200,
        int $max_evidence_types = 45,
        int $max_evidence_users = 20,
        int $min_evidence_fields = 0,
        int $max_evidence_fields = 6
    ) {
        $this->max_evidence_items = $max_evidence_items;
        $this->max_evidence_types = $max_evidence_types;
        $this->max_evidence_users = $max_evidence_users;
        $this->min_evidence_fields = $min_evidence_fields;
        $this->max_evidence_fields = $max_evidence_fields;
        $this->reset();
    }

    /**
     * Call this to change the set_create_files flag to true or false
     *
     * @param bool $create
     */
    public function set_create_files($create = true): void {
        $this->is_creating_files = $create;
    }

    /**
     * Generate all permutations of type names and randomise their order
     *
     * @return string[]
     */
    protected function create_evidence_type_names(): array {
        $nouns = [
            "Building",
            "Alcohol",
            "Safety",
            "Drug",
            "Food",
            "Health",
            "Security",
            "Inspection",
            "Landlord"
        ];

        $documents = [
            "Licence",
            "Certificate",
            "Diploma",
            "Permit",
            "Form"
        ];

        $evidence_type_names = [];
        foreach ($nouns as $noun) {
            foreach ($documents as $document) {
                $evidence_type_names[] = $noun . ' ' . $document;
            }
        }

        shuffle($evidence_type_names);
        return $evidence_type_names;
    }

    /**
     * Refresh data from the database and restore original values
     */
    public function reset(): void {
        $this->time_now = time();

        $this->evidence_items = evidence_item::repository()->get()->all();
        $this->evidence_types = evidence_type::repository()
            ->where('idnumber', '<>', 'coursecompletionimport')
            ->where('idnumber', '<>', 'certificationcompletionimport')
            ->get()
            ->all();
        $this->evidence_users = builder::table('user')
            ->where('username', '<>', 'guest')
            ->order_by('id')
            ->get()
            ->all();

        $this->evidence_items_count = count($this->evidence_items);
        $this->evidence_types_count = count($this->evidence_types);
        $this->evidence_users_count = count($this->evidence_users);

        $this->evidence_type_names = $this->create_evidence_type_names();
        $this->evidence_field_data = [
            [
                'datatype'    => 'checkbox',
                'fullname'    => 'Confirmation of Consent',
                'shortname'   => 'CONSENT',
                'description' => 'Confirms that the user understands the legal purpose of this evidence and consents to it.'
            ],
            [
                'datatype'    => 'datetime',
                'param1'      => '2019',
                'param2'      => '2039',
                'param3'      => '1',
                'fullname'    => 'Time of Achievement',
                'shortname'   => 'DATE',
                'description' => 'The time and date at which this evidence was awarded.'
            ],
            [
                'datatype'    => 'file',
                'fullname'    => 'Photo of Certificate',
                'shortname'   => 'PHOTO',
                'description' => 'Photographic proof of the learner possessing this certificate'
            ],
            [
                'datatype'    => 'textarea',
                'defaultdata' => '<p>Please add any extra relevant information here. Thank you.</p>',
                'param1'      => '30',
                'param2'      => '10',
                'fullname'    => 'Additional Notes',
                'shortname'   => 'NOTES',
                'description' => 'Additional information that the learner can provide about this evidence'
            ],
            [
                'datatype'    => 'multiselect',
                'fullname'    => 'Degree(s)',
                'shortname'   => 'DEGREE',
                'description' => 'What degree(s) the learner possesses',
                'param1'      => json_encode([
                    [
                        'option'  => 'Associates',
                        'icon'    => 'learning-programs',
                        'default' => '0',
                        'delete'  => '0',
                    ],
                    [
                        'option'  => 'Bachelors',
                        'icon'    => 'learning-programs',
                        'default' => '1',
                        'delete'  => '0',
                    ],
                    [
                        'option'  => 'Masters',
                        'icon'    => 'learning-programs',
                        'default' => '0',
                        'delete'  => '0',
                    ],
                    [
                        'option'  => 'Doctorate',
                        'icon'    => 'learning-programs',
                        'default' => '0',
                        'delete'  => '0',
                    ],
                ])
            ],
            [
                'datatype'     => 'text',
                'param1'       => '30',
                'param2'       => '2048',
                'fullname'     => 'Qualification Name',
                'shortname'    => 'NAME',
                'description'  => 'Name of the specific qualification'
            ]
        ];

        parent::reset();
    }

    /**
     * Create a user and return its record
     *
     * @param array $record Predefined attributes
     * @return user
     */
    public function create_evidence_user(array $record = []): user {
        $number = $this->number_padding($this->evidence_users_count + 1, $this->max_evidence_users);
        $userdata = array_merge([
            'username' => "evidence_user_$number",
            'idnumber' => $number
        ], $record);
        $userdata['password'] = $userdata['username'];
        $user = \core\testing\generator::instance()->create_user($userdata);
        $this->evidence_users[] = $user;
        $this->evidence_users_count++;
        return new user($user);
    }

    /**
     * Create a user and return its data in an array for behat
     *
     * @param array $record Predefined attributes
     * @return array
     */
    public function create_evidence_user_for_behat(array $record = []): array {
        return (array) $this->create_evidence_user($record);
    }

    /**
     * Create an evidence type and return its entity
     *
     * @param array $record
     * @return evidence_type
     */
    public function create_evidence_type_entity(array $record = []): evidence_type {
        $use_field_types = [];
        if (isset($record['field_types'])) {
            $use_field_types = $record['field_types'];
            $use_generic_fields = false;
            $fields_num = count($use_field_types);
            unset($record['field_types']);
        } else if (isset($record['fields'])) {
            $fields_num = $record['fields'];
            unset($record['fields']);
            $use_generic_fields = true;
        } else {
            $min = $this->min_evidence_fields;
            $max = $this->max_evidence_fields;
            if ($min === $max) {
                $fields_num = $min;
            } else {
                $fields_num = ($this->evidence_types_count % ($max - $min)) + $min;
            }
            $use_generic_fields = false;
        }

        $title = array_pop($this->evidence_type_names) ??
            "Evidence Type #" . $this->number_padding($this->evidence_types_count + 1, $this->max_evidence_types);

        // ID Number format: First 4 letters of the name, plus 3 digit number
        $idnumber = strtoupper(substr($title, 0, 4)) .
            $this->number_padding($this->evidence_types_count + 1, $this->max_evidence_types) . 0 . rand(1, 9);

        // Description: either use manually defined description or generate a random one
        $description = "A formal document pertaining to a {$title}, legally granted under New Zealand law.<br><br>" .
            $this->generate_description();

        // Pick a time created & modified, randomly between now and the last 5 years
        $time_created = $this->get_date($this->evidence_types_count, $this->max_evidence_types);
        $time_modified = (random_int(0, 1) % 1) ? $time_created : $this->time_now;

        if (isset($record['user']) && !is_numeric($record['user'])) {
            $user_created = $user_modified = $this->get_user_from_name($record['user'])->id;
            unset($record['user']);
        } else {
            global $USER;
            $user_created = $user_modified = $USER->id;
        }

        $location = $record['location'] ?? models\evidence_type::LOCATION_EVIDENCE_BANK;
        $status = $record['status'] ?? models\evidence_type::STATUS_ACTIVE;

        $evidence_type_data = array_merge([
            'name'         => $title,
            'idnumber'     => $idnumber,
            'description'  => $description,
            'created_at'   => $time_created,
            'modified_at'  => $time_modified,
            'created_by'   => $user_created,
            'modified_by'  => $user_modified,
            'location'     => $location,
            'status'       => $status,
            'descriptionformat' => FORMAT_HTML,
        ], $record);
        unset($evidence_type_data['add_image_to_description']);

        $evidence_type = (new class ($evidence_type_data) extends evidence_type {
            protected $set_updated_when_created = false;
        })->save();

        $this->create_evidence_type_fields($evidence_type, $fields_num, $use_generic_fields, $use_field_types);

        if (isset($record['add_image_to_description'])) {
            $description_file = $this->create_test_file([
                'itemid' => $evidence_type->id,
                'component' => 'totara_evidence',
                'filearea' => models\evidence_type::DESCRIPTION_FILEAREA,
            ]);
            $evidence_type->description .= $this->generate_description(false, $description_file->get_filename());
            $evidence_type->save();
        }

        $this->evidence_types[] = $evidence_type;
        $this->evidence_types_count++;
        return $evidence_type;
    }

    /**
     * Create evidence type and return its data in array for behat
     *
     * @param array $record
     * @return array
     */
    public function create_evidence_type_for_behat(array $record = []): array {
        return $this->create_evidence_type_entity($record)->to_array();
    }

    /**
     * Create evidence type and return its model
     *
     * @param array $record
     * @return models\evidence_type
     */
    public function create_evidence_type(array $record = []) {
        $entity = $this->create_evidence_type_entity($record);
        (evidence_type_created::create_from_type($entity))->trigger();
        return models\evidence_type::load_by_id($entity->id);
    }

    /**
     * Create evidence types
     *
     * @param array[] $records Array of arrays of predefined attributes
     * @return models\evidence_type[]
     */
    public function create_evidence_types(array $records): array {
        $result = [];

        foreach ($records as $record) {
            $result[] = $this->create_evidence_type_for_behat($record);
        }

        return $result;
    }

    /**
     * Create an evidence item and return its entity
     *
     * @param array $record
     * @return evidence_item
     */
    public function create_evidence_item_entity(array $record = []): evidence_item {
        if (!$this->evidence_types) {
            $this->create_evidence_type_entity([
                'name' => 'Generic Evidence Type',
                'field_types' => ['textarea', 'file', 'datetime']
            ]);
        }

        if (isset($record['typeid'])) {
            $evidence_type = new evidence_type($record['typeid']);
        } else if (isset($record['type'])) {
            if ($record['type'] instanceof evidence_type) {
                $evidence_type = $record['type'];
            } else {
                $evidence_type = $this->get_type_from_name($record['type']);
            }
            unset($record['type']);
        } else {
            $evidence_type = $this->get_type();
        }

        $name = 'Evidence #' . $this->number_padding($this->evidence_items_count + 1, $this->max_evidence_items);
        $time_created = $this->get_date($this->evidence_items_count, $this->max_evidence_items);
        $time_modified = (random_int(0, 1) % 1) ? $time_created : $this->time_now;

        if (isset($record['user']) && !is_numeric($record['user'])) {
            $user = $user_created = $user_modified = $this->get_user_from_name($record['user'])->id;
            unset($record['user']);
        } else {
            $user = $this->evidence_users[$this->evidence_items_count % max($this->evidence_users_count - 1, 1)]->id;
            if (job_assignment::has_manager($user) && $this->evidence_items_count % 2) {
                $manager = job_assignment::get_all($user);
                $user_created = array_shift($manager)->managerid;
            } else {
                $user_created = $user;
            }
        }

        if (isset($record['created_by']) && !is_numeric($record['created_by'])) {
            $user_created = $user_modified = $this->get_user_from_name($record['created_by'])->id;
            unset($record['created_by']);
        }

        $status = models\evidence_item::STATUS_ACTIVE;

        $override_fields = [];
        if (isset($record['fields'])) {
            $override_fields = $record['fields'];
            unset($record['fields']);
        }

        $item_data = array_merge([
            'name'           => $name,
            'typeid'         => $evidence_type->id,
            'user_id'        => $user,
            'status'         => $status,
            'created_by'     => $user_created,
            'created_at'     => $time_created,
            'modified_by'    => $user_created,
            'modified_at'    => $time_modified,
            'imported'       => 0,
        ], $record);
        $item = (new class ($item_data) extends evidence_item {
            protected $set_updated_when_created = false;
        })->save();

        $fields = $evidence_type->fields;
        $this->create_evidence_field_data((object) $item->to_array(), $fields);

        // If field data has manually been specified, set it here
        foreach ($override_fields as $shortname => $data) {
            $field = $fields->find('shortname', $shortname);
            /** @var evidence_field_data $existing_data */
            $existing_data = evidence_field_data::repository()
                ->where('fieldid', $field->id)
                ->where('evidenceid', $item->id)
                ->one();
            $existing_data->data = $data;
            $existing_data->save();
        }

        $this->evidence_items[] = $item;
        $this->evidence_items_count++;
        return $item;
    }

    /**
     * Create evidence item and return its model
     *
     * @param array $record
     * @return models\evidence_item
     */
    public function create_evidence_item(array $record = []) {
        $entity = $this->create_evidence_item_entity($record);
        (evidence_item_created::create_from_item($entity))->trigger();
        return models\evidence_item::load_by_id($entity->id);
    }

    /**
     * Create evidence item and return its data in an array for behat
     *
     * @param array $record
     * @return array
     */
    public function create_evidence_item_for_behat(array $record = []): array {
        return $this->create_evidence_item_entity($record)->to_array();
    }

    /**
     * Create evidence relation and get its entity
     *
     * @param int|evidence_item|models\evidence_item $item
     * @param array $record
     * @return plan_evidence_relation
     */
    public function create_evidence_plan_relation($item, array $record = []): plan_evidence_relation {
        $evidence_id = $item;
        if ($evidence_id instanceof models\evidence_item) {
            $evidence_id = $evidence_id->get_id();
        } else if ($evidence_id instanceof evidence_item) {
            $evidence_id = $evidence_id->id;
        }

        $plan_id = 0;
        $item_id = 0;
        $component = '';

        if (isset($record['plan'])) {
            $plan_id = builder::table('dp_plan')->where('name', $record['plan'])->value('id');
        }

        if (isset($record['course'])) {
            $item_id = builder::table('course')->where('shortname', $record['course'])->value('id');
            $component = 'course';
        } else if (isset($record['competency'])) {
            $item_id = builder::table('competency')->where('shortname', $record['competency'])->value('id');
            $component = 'competency';
        } else if (isset($record['objective'])) {
            $item_id = builder::table('dp_plan_objective')->where('shortname', $record['objective'])->value('id');
            $component = 'objective';
        }

        $relation = new plan_evidence_relation(array_merge([
            'evidenceid' => $evidence_id,
            'planid' => $plan_id,
            'component' => $component,
            'itemid' => $item_id,
        ], $record));

        return $relation->save();
    }

    /**
     * Create evidence plan relation and return its data in an array for behat
     *
     * @param array $record
     * @return array
     */
    public function create_evidence_plan_relation_for_behat(array $record = []): array {
        return $this->create_evidence_plan_relation($this->get_item_from_name($record['evidence']))->to_array();
    }

    /**
     * Create a custom field
     *
     * @param array $record Predefined attributes
     *
     * @return evidence_type_field
     */
    public function create_evidence_field(array $record = []) {
        return (new evidence_type_field(
            array_merge([
                'hidden'      => '0',
                'locked'      => '0',
                'required'    => '0',
                'forceunique' => '0'
            ], $record)
        ))->save();
    }

    /**
     * Create evidence type fields for behat
     *
     * @param array $record
     */
    public function create_evidence_type_fields_for_behat(array $record = []): void {
        $evidence_type = $this->get_type_from_name($record['evidence_type']);
        unset($record['evidence_type']);
        $this->create_evidence_type_fields($evidence_type, [$record], false, []);
    }

    /**
     * Create custom fields for an evidence type
     *
     * @param evidence_type $evidence_type Evidence type
     * @param array|int $fields_or_count Array of fields to create or just a number of fields
     * @param bool $use_generic_fields Use simple, generic fields or not
     * @param array $use_field_types optional array of types to create
     */
    protected function create_evidence_type_fields(evidence_type $evidence_type, $fields_or_count,
                                                       bool $use_generic_fields, array $use_field_types = []): void {
        // Pick random evidence_fields for the evidence_type
        // or a specific number of specific field types
        $count = $fields_or_count;
        if (is_array($fields_or_count)) {
            $evidence_type_fields = $fields_or_count;
            $count = count($evidence_type_fields);
            $use_generic_fields = false;
        } else if (empty($use_field_types)) {
            $evidence_type_fields = $this->evidence_field_data;
            shuffle($evidence_type_fields); // Randomise evidence_field order
        } else {
            $evidence_type_fields = array_filter($this->evidence_field_data, function ($item) use ($use_field_types) {
                return in_array($item['datatype'], $use_field_types);
            });
        }

        // Randomly pick some evidence_fields to use for this evidence_type
        for ($j = 0; $j < $count; $j++) {
            if (empty($evidence_type_fields) || $use_generic_fields) {
                $field = [
                    'datatype'    => 'text',
                    'param1'      => '30',
                    'param2'      => '2048',
                    'required'    => '1',
                    'fullname'    => 'Custom Field #' . $this->number_padding($j + 1, $count),
                    'shortname'   => 'FIELD' . $this->number_padding($j + 1, $count),
                    'description' => 'Extra Custom Field Number ' . $this->number_padding($j + 1, $count)
                ];
            } else {
                $field = array_shift($evidence_type_fields);
            }

            $field = $this->create_evidence_field(array_merge([
                'typeid'    => $evidence_type->id,
                'sortorder' => $j
            ], $field));

            if ($this->is_creating_files && $field->datatype == 'textarea') {
                $file = $this->create_test_file([
                    'itemid' => $field->id,
                    'filearea' => 'textarea',
                ]);
                $field->defaultdata = $this->generate_description(false, $file->get_filename());
                $field->save();
            }
        }
    }

    /**
     * Create field data for a piece of evidence
     *
     * @param object $item Evidence item record
     * @param collection $fields Evidence fields
     * @param string $prefix Custom fields area prefix
     * @param string $tbl_prefix Custom fields table prefix
     */
    protected function create_evidence_field_data($item, $fields, $prefix = null, $tbl_prefix = null): void {
        $prefix = $prefix ?? customfields::get_prefix();
        $tbl_prefix = $tbl_prefix ?? customfields::get_base_table();

        /** @var \totara_customfield\testing\generator $cf_generator */
        $cf_generator = $this->datagenerator->get_plugin_generator(customfields::get_filearea_component());

        foreach ($fields as $field) {
            if ($field->datatype == 'datetime') {
                $time = $this->time_past - ($this->time_now - $this->time_past);
                $cf_generator->set_datetime($item, $field->id, $time, $prefix, $tbl_prefix);
            } else if ($field->datatype == 'multiselect') {
                $cf_generator->set_multiselect($item, $field->id, ['Bachelors'], $prefix, $tbl_prefix);
            } else if ($field->datatype == 'textarea') {
                if (!$this->is_creating_files) {
                    $cf_generator->set_textarea($item, $field->id, $this->generate_description(), $prefix, $tbl_prefix);
                } else {
                    $file_name = random_string() . '.png';
                    $text = $this->generate_description(true, $file_name);
                    $data = $cf_generator->set_textarea($item, $field->id, $text, $prefix, $tbl_prefix);
                    $this->create_test_file([
                        'itemid' => $data->id,
                        'filename' => $file_name,
                    ]);
                }
            } else if ($field->datatype == 'file') {
                $data_id = builder::table($tbl_prefix . '_info_data')->insert([
                    'fieldid' => $field->id,
                    'evidenceid' => $item->id,
                    'data' => 0,
                ]);
                if ($this->is_creating_files) {
                    $this->create_test_file([
                        'itemid' => $data_id,
                        'filearea' => customfields::get_fileareas()[1],
                    ]);
                    builder::table($tbl_prefix . '_info_data')->update_record(['id' => $data_id, 'data' => $data_id]);
                }
            } else {
                $value = "{$item->name} Dummy Data #{$field->sortorder}";
                $cf_generator->set_text($item, $field->id, $value, $prefix, $tbl_prefix);
            }
        }
    }

    /**
     * Create a file to be stored in the custom fields area for evidence
     *
     * @param array $record File record data. Must at least specify itemid
     *
     * @return \stored_file
     */
    public function create_test_file(array $record): \stored_file {
        global $CFG;
        return get_file_storage()->create_file_from_pathname(
            array_merge([
                'contextid' => \context_system::instance()->id,
                'component' => customfields::get_filearea_component(),
                'filearea' => customfields::get_fileareas()[0],
                'filepath' => '/',
                'filename' => random_string() . '.png',
            ], $record),
            "$CFG->dirroot/totara/evidence/tests/fixtures/pic1.png"
        );
    }

    /**
     * Clean-up the file area for evidence
     */
    public static function remove_evidence_files(): void {
        $file_storage = get_file_storage();
        $context = \context_system::instance()->id;
        $component = customfields::get_filearea_component();

        foreach (customfields::get_fileareas() as $filearea) {
            $file_storage->delete_area_files($context, $component, $filearea);
        }

        $fields = evidence_type_field::repository()->get()->all();
        foreach ($fields as $field) {
            /** @var evidence_type_field $field */
            $file_storage->delete_area_files($context, $component, 'textarea', $field->id);
        }
    }

    /**
     * Pick random text, images etc to create a description
     *
     * @param bool $with_latin Generate lorem ipsum text?
     * @param string|null $file_name Optional - Include an inline image
     * @return string Description HTML
     */
    protected function generate_description(bool $with_latin = true, string $file_name = null): string {
        $description = '';
        if ($file_name) {
            $description .= "<p><img src=\"@@PLUGINFILE@@/{$file_name}\"/></p>";
        }
        if ($with_latin) {
            $description .= $this->datagenerator->loremipsum;
        }
        return $description;
    }

    /**
     * Pads an integer with the appropriate amount of leading zeroes
     *
     * @param int $number The number to pad
     * @param int $max The largest number in the set
     * @return string The padded number
     */
    protected function number_padding(int $number, int $max): string {
        return str_pad($number, strlen((string) $max), '0', STR_PAD_LEFT);
    }

    /**
     * Pick a random date
     *
     * @param int $i
     * @param int $max
     * @return int The chosen date
     */
    protected function get_date(int $i, int $max): int {
        return $this->get_index_from_range($i, $max, $this->time_now, $this->time_past);
    }

    /**
     * Pick an evidence type at random
     *
     * @return evidence_type Evidence type
     */
    protected function get_type(): evidence_type {
        $upper = $this->evidence_types_count - ceil($this->evidence_types_count / 3);
        return $this->evidence_types[$this->get_index_from_range($this->evidence_items_count, $this->max_evidence_items, $upper)];
    }

    /**
     * Get the first evidence type with a given name
     *
     * @param string $name The name of the type
     * @return evidence_type
     */
    protected function get_type_from_name(string $name): evidence_type {
        return evidence_type::repository()
            ->where('name', $name)
            ->get()
            ->first();
    }

    /**
     * Get the first evidence item with a given name
     *
     * @param string $name The name of the item
     * @return evidence_item
     */
    protected function get_item_from_name(string $name): evidence_item {
        return evidence_item::repository()
            ->where('name', $name)
            ->get()
            ->first();
    }

    /**
     * Get user from username
     *
     * @param string $username The user's username
     * @return object
     */
    protected function get_user_from_name(string $username): object {
        return builder::table('user')->where('username', $username)->one();
    }

    /**
     * Get array index to use based on a range.
     *
     * @param int $current_index
     * @param int $array_size
     * @param int $max_range
     * @param int|null $min_range Defaults to zero
     * @return int
     */
    protected function get_index_from_range(int $current_index, int $array_size, int $max_range, int $min_range = 0): int {
        return (int) (max(0, $min_range) + (($current_index / max(1, $array_size)) * ($max_range - $min_range)));
    }

    /**
     * Create a user/manager relationship between two users
     *
     * @param int $staff_id Staff user ID
     * @param int $manager_id Staff manager ID
     */
    public function create_relationship($staff_id, $manager_id): void {
        $manager_ja = job_assignment::create([
            'userid' => $manager_id,
            'fullname' => 'Manager',
            'idnumber' => "MANAGER#{$staff_id}",
            'description' => 'evidence_user',
        ]);

        job_assignment::create([
            'userid' => $staff_id,
            'fullname' => 'Team Member',
            'idnumber' => "STAFF#{$manager_id}",
            'description' => 'evidence_user',
            'managerjaid' => $manager_ja->id,
        ]);
    }

    /**
     * Create random job assignments for evidence users
     *
     * @param int $users_per_manager
     * @return Generator array of managers => staff members
     */
    public function create_teams(int $users_per_manager): \Generator {
        $manager_count = count($this->evidence_users) / $users_per_manager;
        for ($i = 0; $i < $manager_count; $i++) {
            $manager = $this->evidence_users[$i];
            $staff = [];
            for ($j = 0; $j < $users_per_manager; $j++) {
                $user_index = (($i + 1) * $users_per_manager) + $j;
                if ($user_index < count($this->evidence_users)) {
                    $user = $this->evidence_users[$user_index];
                    $this->create_relationship($user->id, $manager->id);
                    $staff[] = $user;
                }
            }
            yield $manager => $staff;
        }
    }

    /**
     * Create course/certification completion history import tool CSV data
     *
     * @param bool $is_course
     * @return array CSV record data
     */
    public function create_evidence_completion_records(bool $is_course): array {
        $completion_type = [];
        for ($i = 1; $i < $this->evidence_types_count + 1; $i++) {
            $number = $this->number_padding($i, $this->evidence_types_count);
            if ($is_course) {
                $completion_type[] = [
                    'courseshortname' => "Course Completion Course #$number",
                    'courseidnumber' => "COURSE#$number",
                ];
            } else {
                $completion_type[] = [
                    'certificationshortname' => "Certification Completion Certificate #$number",
                    'certificationidnumber' => "CERTIFICATE#$number",
                ];
            }
        }

        $records = [];
        for ($i = 0; $i < $this->evidence_items_count; $i++) {
            $user = $this->evidence_users[$i % max($this->evidence_users_count, 1)];
            $username = builder::table('user')->find_or_fail($user->id)->username;
            $record = ['username' => $username];
            $record = array_merge($record, $completion_type[rand(0, $this->evidence_types_count - 1)]);
            $record['completiondate'] = date('Y-m-d', $this->get_date($i, $this->evidence_items_count));
            if ($is_course) {
                $record['grade'] = random_int(0, 100);
            } else {
                $record['duedate'] = date('Y-m-d', random_int($this->time_now, strtotime('+3 years', $this->time_now)));
            }
            $records[] = $record;
        }
        return $records;
    }

}
