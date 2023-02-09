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

use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\account\conversation_account;
use totara_msteams\botfw\card\attachment;
use totara_msteams\botfw\message;

/**
 * A builder class for a message.
 */
class message_builder {
    /** @var message */
    private $message;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->message = new message();
        $this->message->type = 'message';
    }

    /**
     * @return message
     */
    public function build(): message {
        return $this->message;
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
     * @param conversation_account $account
     * @return self
     */
    public function conversation(conversation_account $account): self {
        $this->message->conversation = $account;
        return $this;
    }

    /**
     * @param channel_account $account
     * @return self
     */
    public function from(channel_account $account): self {
        $this->message->from = $account;
        return $this;
    }

    /**
     * @param channel_account $account
     * @return self
     */
    public function recipient(channel_account $account): self {
        $this->message->recipient = $account;
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
     * @param string $summary
     * @return self
     */
    public function summary(string $summary): self {
        $this->message->summary = $summary;
        return $this;
    }
}
