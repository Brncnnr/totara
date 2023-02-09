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

namespace totara_msteams\botfw\builder;

use totara_msteams\botfw\card\action;
use totara_msteams\botfw\card\attachment;
use totara_msteams\botfw\card\suggested_actions;
use totara_msteams\botfw\messaging_extension;

/**
 * A builder class for a messaging extension reply.
 */
class messaging_extension_builder {
    /** @var messaging_extension */
    private $message;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->message = new messaging_extension();
        $this->message->type = 'result';
    }

    /**
     * @return messaging_extension
     */
    public function build(): messaging_extension {
        return $this->message;
    }

    /**
     * @param string $type
     * @return self
     */
    public function type(string $type): self {
        $this->message->type = $type;
        return $this;
    }

    /**
     * @param string $text
     * @return self
     */
    public function text(string $text): self {
        $this->message->text = $text;
        return $this;
    }

    /**
     * @param attachment $attachment
     * @return self
     */
    public function add_attachment(attachment $attachment): self {
        $attachments = isset($this->message->attachments) ? $this->message->attachments : [];
        $attachments[] = $attachment;
        $this->message->set_attachments($attachments);
        return $this;
    }

    /**
     * @param string $layout
     * @return self
     */
    public function attachment_layout(string $layout): self {
        $this->message->attachmentLayout = $layout;
        return $this;
    }

    /**
     * @param action $action
     * @return self
     */
    public function add_suggested_action(action $action): self {
        $suggested_actions = $this->message->suggestedActions ?? new suggested_actions();
        $actions = isset($suggested_actions->actions) ? $suggested_actions->actions : [];
        $actions[] = $action;
        $suggested_actions->set_actions($actions);
        $this->message->set_suggestedActions($suggested_actions);
        return $this;
    }
}
