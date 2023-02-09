<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package mod_perform
 */

namespace mod_perform\notification\internals;

use core\collection;
use mod_perform\models\activity\notification_recipient as notification_recipient_model;
use mod_perform\notification\composer;
use totara_core\relationship\relationship as relationship_model;

/**
 * The notification message sink for testing.
 *
 * @codeCoverageIgnore
 */
final class sink {
    /** @var collection<message> */
    private $messages;

    /**
     * Constructor.
     *
     * @internal Do not instantiate this class in production!!
     */
    public function __construct() {
        $this->clear();
    }

    /**
     * Add a message record.
     *
     * @param notification_recipient_model $recipient
     * @param composer $composer
     * @param integer $time
     * @internal
     */
    public function push(notification_recipient_model $recipient, composer $composer, int $time): void {
        $this->messages->append(new message($recipient, $composer->get_class_key(), $time));
    }

    /**
     * Get all entries.
     *
     * @return collection<message>
     * @internal
     */
    public function get_all(): collection {
        return $this->messages;
    }

    /**
     * Get entries filtered by a relationship.
     *
     * @param string $idnumber
     * @return collection<message>
     * @internal
     */
    public function get_by_relationship(string $idnumber): collection {
        $relationship = relationship_model::load_by_idnumber($idnumber)->id;
        return $this->messages->filter('relationship_id', $relationship);
    }

    /**
     * Get entries filtered by a class key.
     *
     * @param string $class_key
     * @return collection<message>
     * @internal
     */
    public function get_by_class_key(string $class_key): collection {
        return $this->messages->filter('class_key', $class_key);
    }

    /**
     * Remove all entries.
     *
     * @internal
     */
    public function clear(): void {
        $this->messages = new collection();
    }
}
