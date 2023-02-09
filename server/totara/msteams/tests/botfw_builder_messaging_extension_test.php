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

use totara_msteams\botfw\builder;

class totara_msteams_botfw_builder_messaging_extension_testcase extends advanced_testcase {
    public function test_build() {
        $message = builder::messaging_extension()
            ->type('message')
            ->text('Test messaging extension')
            ->build();
        $this->assertEquals('message', $message->type);
        $this->assertEquals('Test messaging extension', $message->text);

        $message = builder::messaging_extension()
            ->type('result')
            ->attachment_layout('list')
            ->build();
        $this->assertEquals('result', $message->type);
        $this->assertEquals('list', $message->attachmentLayout);

        $message = builder::messaging_extension()
            ->type('result')
            ->attachment_layout('grid')
            ->add_attachment(builder::thumbnail_card()
                ->title('Culnerary art')
                ->subtitle('Course')
                ->text('<b>Learn how to cook</b>')
                ->add_image('https://example.com/assets/fruitsandvegs.jpg')
                ->add_button(builder::action()
                    ->url('More info', 'https://totara.example.com/course/view.php?id=314')
                    ->build())
                ->build())
            ->build();
        $this->assertEquals('result', $message->type);
        $this->assertEquals('grid', $message->attachmentLayout);
        $this->assertEquals('application/vnd.microsoft.card.thumbnail', $message->attachments[0]->contentType);
        $this->assertEquals('Culnerary art', $message->attachments[0]->content->title);
        $this->assertEquals('Course', $message->attachments[0]->content->subtitle);
        $this->assertEquals('<b>Learn how to cook</b>', $message->attachments[0]->content->text);
        $this->assertEquals('https://example.com/assets/fruitsandvegs.jpg', $message->attachments[0]->content->images[0]->url);
        $this->assertEquals('openUrl', $message->attachments[0]->content->buttons[0]->type);
        $this->assertEquals('More info', $message->attachments[0]->content->buttons[0]->title);
        $this->assertEquals('More info', $message->attachments[0]->content->buttons[0]->text);
        $this->assertEquals('https://totara.example.com/course/view.php?id=314', $message->attachments[0]->content->buttons[0]->value);

        $message = builder::messaging_extension()
            ->type('auth')
            ->add_suggested_action(builder::action()
                ->type('openUrl')
                ->title('Test sign in')
                ->value('http://example.com/test')
                ->build())
            ->build();
        $this->assertEquals('auth', $message->type);
        $this->assertEquals('openUrl', $message->suggestedActions->actions[0]->type);
        $this->assertEquals('Test sign in', $message->suggestedActions->actions[0]->title);
        $this->assertEquals('http://example.com/test', $message->suggestedActions->actions[0]->value);
    }
}
