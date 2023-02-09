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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_comment
 */

namespace totara_comment\totara_notification\placeholder;

use coding_exception;
use totara_comment\comment as comment_model;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

/**
 * A class to provide placeholders relating to a comment.
 */
class comment extends single_emptiable_placeholder {

    use placeholder_instance_cache;

    /**
     * @var comment_model|null
     */
    private $model;

    /**
     * comment constructor.
     * @param comment_model|null $model
     */
    public function __construct(?comment_model $model) {
        $this->model = $model;
    }

    /**
     * @param int $id
     *
     * @return self
     */
    public static function from_id(int $id): self {
        $instance = self::get_cached_instance($id);
        if (!$instance) {
            $model = comment_model::from_id($id);
            $instance = new static($model);
            self::add_instance_to_cache($id, $instance);
        }
        return $instance;
    }

    /**
     * @param comment_model $model
     *
     * @return self
     */
    public static function from_model(comment_model $model): self {
        // Use the new model, ignoring cache - the model might have been modified, and it costs almost nothing to create a new one.
        $instance = new static($model);
        self::add_instance_to_cache($model->get_id(), $instance);
        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create(
                'content_text',
                get_string('comment_placeholder_content_text', 'totara_comment')
            ),
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return null !== $this->model;
    }

    /**
     * @param string $key
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     */
    public function do_get(string $key): ?string {
        if (null === $this->model) {
            throw new coding_exception("The comment model is empty");
        }

        switch ($key) {
            case 'content_text':
                return $this->model->get_content_text();
        }

        throw new coding_exception("Invalid key '{$key}'");
    }
}