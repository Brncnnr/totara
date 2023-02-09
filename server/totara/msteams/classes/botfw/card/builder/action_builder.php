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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\card\builder;

use coding_exception;
use moodle_url;
use stdClass;
use totara_msteams\botfw\card\action;

/**
 * A builder class for an action.
 */
class action_builder {
    /** @var action */
    private $action;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->action = new action();
        $this->action->type = 'openUrl';
    }

    /**
     * @return action
     */
    public function build(): action {
        return $this->action;
    }

    /**
     * @param string $type
     * @return self
     */
    public function type(string $type): self {
        $this->action->type = $type;
        return $this;
    }

    /**
     * @param string|stdClass $value
     * @return self
     * @throws coding_exception
     */
    public function value($value): self {
        if (is_string($value)) {
            $this->action->value = $value;
        } else if (is_object($value)) {
            $this->action->value = json_encode($value, JSON_UNESCAPED_SLASHES);
        } else {
            throw new coding_exception('$value must be string or object');
        }
        return $this;
    }

    /**
     * @param string $title
     * @return self
     */
    public function title(string $title): self {
        $this->action->title = $title;
        return $this;
    }

    /**
     * @param string $text
     * @return self
     */
    public function text(string $text): self {
        $this->action->text = $text;
        return $this;
    }

    /**
     * @param string $displayText
     * @return self
     */
    public function display_text(string $text): self {
        $this->action->displayText = $text;
        return $this;
    }

    /**
     * Set the 'openUrl' action.
     *
     * @param string $text
     * @param string|moodle_url $url
     * @return self
     */
    public function url(string $text, $url): self {
        if ($url instanceof moodle_url) {
            $url = $url->out(false);
        } else {
            $url = (string)$url;
        }
        return $this->type('openUrl')
            ->title($text)
            ->text($text)
            ->value($url);
    }

    /**
     * Set the 'messageBack' action.
     *
     * @param string $text
     * @param string|stdClass $value
     * @return self
     */
    public function message_back(string $text, $value): self {
        $this->type('messageBack')->title($text);
        if (is_string($value)) {
            $this->text($value)->display_text($value);
        } else {
            $this->value($value);
        }
        return $this;
    }

    /**
     * Set the 'imBack' action.
     *
     * @param string $value
     * @return self
     */
    public function im_back(string $value): self {
        return $this->type('imBack')->value($value);
    }

    /**
     * Set the 'signin' action.
     *
     * @param string $text
     * @param string|moodle_url $url
     * @return self
     */
    public function signin(string $text, $url): self {
        if ($url instanceof moodle_url) {
            $url = $url->out(false);
        } else {
            $url = (string)$url;
        }
        return $this->type('signin')
            ->title($text)
            ->text($text)
            ->value($url);
    }
}
