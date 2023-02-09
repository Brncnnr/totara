<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\course;

use coding_exception;
use completion_criteria_activity;
use container_course\course;
use container_course\course_helper;
use container_course\module\course_module;
use context_course;
use context_coursecat;
use core\orm\query\builder;
use core_container\module\module;
use core_course\hook\edit_form_save_changes;
use coursecat;
use stdClass;
use Throwable as throwable;
use totara_contentmarketplace\completion_constants;
use totara_contentmarketplace\exception\cannot_resolve_default_course_category;
use totara_contentmarketplace\interactor\abstraction\create_course_interactor;
use totara_contentmarketplace\learning_object\abstraction\metadata\detailed_model;
use totara_contentmarketplace\learning_object\abstraction\metadata\model;
use totara_contentmarketplace\learning_object\factory;
use totara_contentmarketplace\model\course_module_source;

/**
 * The course_builder class is designed to create a course for one learning object.
 * If you would want to create a new course out of different object, please instantiate
 * a new instance of this builder to do such thing.
 */
class course_builder {
    /**
     * @var model
     */
    private $learning_object;

    /**
     * @var int|null
     */
    private $category_id;

    /**
     * The interactor class that help to provide the permission check
     * when the creation course is invoked. Note that it is also a source
     * of the user actor.
     *
     * @var create_course_interactor
     */
    private $course_interactor;

    /**
     * Defaults to 'singleactivity'
     * @var string
     */
    private $course_format;

    /**
     * The default section number that we would want to add the course module into.
     * Default to section zero.
     *
     * @var int
     */
    private $default_section_number;

    /**
     * This variable can only accept either:
     * @see COMPLETION_TRACKING_AUTOMATIC
     * @see COMPLETION_TRACKING_MANUAL
     * @see COMPLETION_TRACKING_NONE
     *
     * @var int
     */
    private $module_completion_tracking;

    /**
     * This variable can only accept:
     * @see completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE
     *
     * @var int|null
     */
    private $module_completion_condition;

    /**
     * A flag to say whether the course completion is enabled or not.
     * By default, it will be true.
     *
     * @var bool
     */
    private $enable_course_completion;

    /**
     * course_helper constructor.
     */
    public function __construct(model $learning_object, ?int $category_id, create_course_interactor $interactor) {
        $this->learning_object = $learning_object;
        $this->category_id = $category_id;
        $this->course_interactor = $interactor;
        $this->course_format = 'singleactivity';
        $this->default_section_number = 0;
        $this->module_completion_tracking = COMPLETION_TRACKING_AUTOMATIC;
        $this->module_completion_condition = completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE;

        $this->enable_course_completion = true;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function set_enable_course_completion(bool $value = true): self {
        $this->enable_course_completion = $value;

        if (!$this->enable_course_completion) {
            // Set to completion tracking NONE when course completion is not enabled.
            $this->set_module_completion_tracking(COMPLETION_TRACKING_NONE);
            return $this;
        }

        // Otherwise, set it back to default content marketplace condition.
        $this->set_module_completion_tracking(COMPLETION_TRACKING_AUTOMATIC);
        $this->set_module_completion_condition(completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE);

        return $this;
    }

    /**
     * Set the completion tracking flag for the module. Please note that when
     * the completion is set to manual or none, the completion condition will be reset back
     * to null. If it is set to automatic and the completion condition is not set, then it
     * is default into completion on launch.
     *
     * @param int $completion_tracking
     * @return $this
     */
    public function set_module_completion_tracking(int $completion_tracking): self {
        $available = [
            COMPLETION_TRACKING_MANUAL,
            COMPLETION_TRACKING_NONE,
            COMPLETION_TRACKING_AUTOMATIC
        ];

        if (!in_array($completion_tracking, $available)) {
            throw new coding_exception(
                "Invalid completion tracking value {$completion_tracking}"
            );
        }

        $this->module_completion_tracking = $completion_tracking;
        if (COMPLETION_TRACKING_AUTOMATIC === $this->module_completion_tracking && null !== $this->module_completion_condition) {
            // Tracking automatic, hence we default it to on launch.
            $this->module_completion_condition = completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE;
        } else {
            $this->module_completion_condition = null;
        }

        return $this;
    }

    /**
     * The value can only accept:
     * + @see completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE
     *
     * @param int|null $completion_condition
     * @return $this
     */
    public function set_module_completion_condition(?int $completion_condition): self {
        if (null === $completion_condition && COMPLETION_TRACKING_AUTOMATIC === $this->module_completion_tracking) {
            // Reset it to manual if the completion condition is set to null - not set.
            $this->module_completion_tracking = COMPLETION_TRACKING_MANUAL;
        } else if (null !== $completion_condition) {
            // Otherwise, we process with the business logics.
            $available = [
                completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE,
            ];

            if (!in_array($completion_condition, $available)) {
                throw new coding_exception(
                    "Invalid completion condition {$completion_condition}"
                );
            }

            // Only completion tracking automatic can have the completion condition.
            $this->module_completion_tracking = COMPLETION_TRACKING_AUTOMATIC;
        }

        $this->module_completion_condition = $completion_condition;

        return $this;
    }

    /**
     * @param string                   $marketplace_component
     * @param int                      $learning_object_id
     * @param create_course_interactor $interactor
     * @param int|null                 $category_id
     *
     * @return course_builder
     */
    public static function create_with_learning_object(
        string $marketplace_component,
        int $learning_object_id,
        create_course_interactor $interactor,
        ?int $category_id = null
    ): course_builder {
        global $CFG;

        $resolver = factory::get_resolver($marketplace_component);
        $learning_object = $resolver->find($learning_object_id, true);

        if (empty($category_id)) {
            require_once("{$CFG->dirroot}/totara/core/lib.php");

            $actor_id = $interactor->get_actor_id();
            $category_id = totara_get_categoryid_with_capability('totara/contentmarketplace:add', $actor_id);

            if (!$category_id) {
                throw new cannot_resolve_default_course_category($actor_id);
            }
        } else {
            if ((coursecat::get($category_id))->issystem == 1) {
                throw new coding_exception("Category {$category_id} is not supported.");
            }
        }

        return new static($learning_object, $category_id, $interactor);
    }

    /**
     * Create course builder without category id. This is used for adding a learning object
     * as a module to an existing course
     *
     * @param string $marketplace_component
     * @param int $learning_object_id
     * @param create_course_interactor $interactor
     * @return course_builder
     */
    public static function create_with_learning_object_without_category(
        string $marketplace_component,
        int $learning_object_id,
        create_course_interactor $interactor
    ): course_builder {
        $resolver = factory::get_resolver($marketplace_component);
        $learning_object = $resolver->find($learning_object_id, true);

        return new static($learning_object, null, $interactor);
    }

    /**
     * Set default section number
     *
     * @param int $section_number
     * @return $this
     */
    public function set_default_section_number(int $section_number): self {
        $this->default_section_number = $section_number;
        return $this;
    }

    /**
     * @param string $course_format
     * @return $this
     */
    public function set_course_format(string $course_format): self {
        $this->course_format = $course_format;
        return $this;
    }

    /**
     * Please note that this function is about the process of creation only, and the database transaction should be
     * instantiated at prior to this function call.
     *
     * @return course
     */
    private function do_create_course(): course {
        $name = $this->learning_object->get_name();
        $record = new stdClass();
        $record->shortname = self::get_short_name($name);
        $record->fullname = $name;
        $record->category = $this->category_id;
        $record->visible = 1;
        $record->visibleold = 1;
        $record->format = $this->course_format;
        $record->showgrades = 0;
        $record->containertype = course::get_type();
        $record->enablecompletion = $this->enable_course_completion;

        if ('singleactivity' === $this->course_format) {
            $record->activitytype = 'contentmarketplace';
        }

        if ($this->learning_object instanceof detailed_model) {
            $summary = $this->learning_object->get_description();
            if (null !== $summary) {
                $record->summary = $summary->get_raw_value();
                $record->summaryformat = $summary->get_format();
            }
        }

        $course = course_helper::create_course($record);

        // update record to make totara mobile compatible
        $this->make_mobile_compatible($course->id);

        $image_url = $this->learning_object->get_image_url();
        if (!empty($image_url)) {
            // Download image and store it.
            (new course_image_downloader($course->id, $image_url))->download_image_for_course();
        }

        $manager = new enrol_manager($course);
        $actor_id = $this->course_interactor->get_actor_id();
        $manager->enrol_course_creator($actor_id);

        return $course;
    }

    /**
     * @param course $course
     * @return module
     */
    private function do_add_module(course $course, ?int $section = null): module {
        $module_info = new stdClass();
        $module_info->modulename = 'contentmarketplace';
        $module_info->visible = 1;
        $module_info->learning_object_marketplace_component = $this->learning_object::get_marketplace_component();
        $module_info->learning_object_id = $this->learning_object->get_id();
        $module_info->section = $section ?? $this->default_section_number;
        $module_info->completion = $this->module_completion_tracking;
        $module_info->completion_condition = $this->module_completion_condition;

        $actor_id = $this->course_interactor->get_actor_id();
        if (!course_helper::is_module_addable('contentmarketplace', $course, $actor_id)) {
            throw new coding_exception(
                "Cannot add module 'contentmarketplace' to course '{$course->fullname}'"
            );
        }

        /** @var course_module $module */
        $module = $course->add_module($module_info);

        // Create course source.
        course_module_source::create($module, $this->learning_object);

        return $module;
    }

    /**
     * Note that this function does not put the creation in any transaction.
     * Use {@see course_builder::create_course_in_transaction()} if you would want
     * to have rollback the whole process when something went wrong.
     *
     * @return result
     */
    public function create_course(): result {
        $context_category = context_coursecat::instance($this->category_id);
        if (!$this->course_interactor->can_add_course_to_category($context_category)) {
            return result::create(
                null,
                result::ERROR_ON_COURSE_CREATION,
                get_string(
                    'error:cannot_add_course_to_category',
                    'totara_contentmarketplace',
                    $context_category->get_context_name(false)
                )
            );
        }

        // Create course.
        try {
            $course = $this->do_create_course();
        } catch (throwable $e) {
            return result::create(
                null,
                result::ERROR_ON_COURSE_CREATION,
                get_string('error:cannot_create_course', 'totara_contentmarketplace'),
                $e
            );
        }

        // Create course's module.
        try {
            $module = $this->do_add_module($course);
        } catch (throwable $e) {
            return result::create(
                null,
                result::ERROR_ON_MODULE_CREATION,
                get_string('error:cannot_add_module_to_course', 'totara_contentmarketplace', $course->fullname),
                $e
            );
        }

        if ($course->enablecompletion && $module->get_completion() !== COMPLETION_TRACKING_NONE) {
            // Yep course completion is enabled as well as activity completion, hence we are going
            // to update the course completion criteria.

            // Enable course completion criteria.
            try {
                $this->do_create_completion_criteria($course, $module);
            } catch (throwable $e) {
                return result::create(
                    null,
                    result::ERROR_ON_COURSE_SETTINGS,
                    get_string("error:cannot_configure_course", "totara_contentmarketplace", $course->fullname),
                    $e
                );
            }
        }

        // Everything runs successfully.
        return result::create($course->get_id());
    }

    /**
     * @param course $course
     * @param module $module
     *
     * @return void
     */
    private function do_create_completion_criteria(course $course, module $module): void {
        global $CFG;
        require_once("{$CFG->dirroot}/completion/criteria/completion_criteria_activity.php");

        // Create a mock data form.
        $form_data = new stdClass();
        $form_data->id = $course->id;
        $form_data->unlockdelete = 0;
        $form_data->unlockonly = 0;
        $form_data->overall_aggregation = COMPLETION_AGGREGATION_ALL;
        $form_data->criteria_activity_value = [
            $module->get_id() => true
        ];

        $criterion = new completion_criteria_activity();
        $criterion->update_config($form_data);
    }

    /**
     * Create a course within database transaction, if the whole process
     * is error, then the transaction will be rollback.
     *
     * @return result
     */
    public function create_course_in_transaction(): result {
        $db = builder::get_db();
        $transaction = $db->start_delegated_transaction();

        $result = $this->create_course();
        if (!$result->is_error()) {
            $transaction->allow_commit();
            return $result;
        }

        if (!$transaction->is_disposed()) {
            // Note: please do not pass exception to the rollback function,
            // as we do not want to yield any error yet, not until
            // the result is returned.
            $transaction->rollback();
        }

        return $result;
    }

    /**
     * @param course $course
     * @param int $section_id
     * @return module_result
     * @throws \dml_transaction_exception
     * @throws coding_exception
     * @throws throwable
     */
    public function add_activity_to_course(course $course, int $section_id): module_result {
        $course_context = context_course::instance($course->id);
        if (!$this->course_interactor->can_add_activity_to_course($course_context)) {
            return module_result::create(
                null,
                module_result::ERROR_ON_MODULE_CREATION,
                get_string(
                    'error:cannot_add_module_to_course',
                    'totara_contentmarketplace',
                    $course->fullname
                )
            );
        }
        $db = builder::get_db();
        $transaction = $db->start_delegated_transaction();

        // Create course's module.
        try {
            $module = $this->do_add_module($course, $section_id);
            $result = module_result::create($module->get_id());
        } catch (throwable $e) {
            $result = module_result::create(
                null,
                result::ERROR_ON_MODULE_CREATION,
                get_string('error:cannot_add_module_to_course', 'totara_contentmarketplace', $course->fullname),
                $e
            );
        }

        if (isset($result) && !$result->is_error()) {
            $transaction->allow_commit();
            return $result;
        }

        if (!$transaction->is_disposed()) {
            $transaction->rollback();
        }

        return $result;
    }

    /**
     * Get a unique course shortname by appending a numerical suffix to the name if a course with the shortname already exists.
     *
     * @param string $name Learning object full name, which will be used to converted into shortname.
     * @return string
     */
    private static function get_short_name(string $name): string {
        $db = builder::get_db();
        if (!$db->record_exists('course', ['shortname' => $name])) {
            // The course name hasn't been used yet, so we don't need to change anything.
            return $name;
        }

        $suffix = 1;
        $search_shortname = "$name ($suffix)";
        while ($db->record_exists('course', ['shortname' => $search_shortname])) {
            $suffix++;
            $search_shortname = "$name ($suffix)";
        }

        return $search_shortname;
    }

    /**
     * Make contentmarketplace course compatible with totara mobile.
     *
     * @param int $course_id
     */
    private function make_mobile_compatible(int $course_id): void {
        $form_data = new stdClass();
        $form_data->totara_mobile_coursecompat = empty(get_config('totara_mobile', 'coursecompat')) ? "" : "1";
        $form_data->totara_catalog_searchmetadata = '';

        $hook = new edit_form_save_changes(true, $course_id, $form_data);
        $hook->execute();
    }
}
