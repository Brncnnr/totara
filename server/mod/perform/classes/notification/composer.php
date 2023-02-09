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

namespace mod_perform\notification;

use coding_exception;
use core\message\message;
use lang_string;
use totara_core\relationship\relationship;

/**
 * The composer class. This is nothing with the PHP package manager.
 */
class composer {
    /** @var string */
    private $class_key;

    /** @var string|null */
    private $lang_key_prefix;

    /**
     * Constructor. *Do not instantiate this class directly. Use the factory class.*
     *
     * @param string $class_key
     * @internal
     */
    public function __construct(string $class_key) {
        $this->class_key = $class_key;
    }

    /**
     * Set the relationship.
     *
     * @param relationship $relationship
     * @return boolean
     */
    public function set_relationship(relationship $relationship): bool {
        $resolver_idnumber = $relationship->idnumber;
        if (empty($resolver_idnumber)) {
            $this->lang_key_prefix = null;
            return false;
        }
        $this->lang_key_prefix = 'template_' .  $this->class_key . '_' . $resolver_idnumber . '_';
        return true;
    }

    /**
     * Get the class key of the current instance.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function get_class_key(): string {
        return $this->class_key;
    }

    /**
     * Get the language string id of the message subject.
     *
     * @return string
     * @throws coding_exception thrown when a valid relationship is not set
     */
    public function get_subject_lang_key(): string {
        if (!$this->lang_key_prefix) {
            throw new coding_exception('relationship is not set');
        }
        return $this->lang_key_prefix . 'subject';
    }

    /**
     * Get the language string id of the message body.
     *
     * @return string
     * @throws coding_exception thrown when a valid relationship is not set
     */
    public function get_body_lang_key(): string {
        if (!$this->lang_key_prefix) {
            throw new coding_exception('relationship is not set');
        }
        return $this->lang_key_prefix . 'body';
    }

    /**
     * Get the language string of the message subject.
     *
     * @param placeholder $placeholders
     * @return lang_string
     * @throws coding_exception
     */
    public function get_subject_lang_string(placeholder $placeholders): lang_string {
        // Make sure we have a record which can be used in lang strings
        $placeholders = $placeholders->to_record();

        return new lang_string($this->get_subject_lang_key(), 'mod_perform', $placeholders);
    }

    /**
     * Get the language string of the message body.
     *
     * @param placeholder $placeholders
     * @return lang_string
     * @throws coding_exception
     */
    public function get_body_lang_string(placeholder $placeholders): lang_string {
        // Make sure we have a record which can be used in lang strings
        $placeholders = $placeholders->to_record();

        return new lang_string($this->get_body_lang_key(), 'mod_perform', $placeholders);
    }

    /**
     * Return whether the current instance is a reminder.
     *
     * @return bool
     * @throws coding_exception
     */
    public function is_reminder(): bool {
        return factory::create_loader()->is_reminder($this->class_key);
    }

    /**
     * Generate a message.
     *
     * @param placeholder $placeholders
     * @return message
     * @throws coding_exception thrown when a valid relationship is not set
     */
    public function compose(placeholder $placeholders): message {
        $subject = $this->get_subject_lang_string($placeholders)->out();
        $html = text_to_html($this->get_body_lang_string($placeholders)->out());
        $text = format_text_email($html, FORMAT_HTML);
        $message = new message();
        $message->subject = $subject;
        $message->fullmessage = $text;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $html;
        $message->smallmessage = $text;
        return $message;
    }
}
