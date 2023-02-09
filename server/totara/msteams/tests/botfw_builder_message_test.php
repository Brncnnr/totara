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

use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\account\conversation_account;
use totara_msteams\botfw\builder;

class totara_msteams_botfw_builder_message_testcase extends advanced_testcase {
    public function test_build() {
        $conversation = new conversation_account();
        $conversation->id = 'a:k1a0RA-_-koUT0u';
        $conversation->tenantId = '31415926-5358-9793-2384-626433832795';
        $bot = new channel_account();
        $bot->id = '28:tH1siSAb0t';
        $bot->name = 'mybot';
        $user = new channel_account();
        $user->id = '29:IaMtH3c00Le5tUs3r';
        $user->name = 'Bob';

        $message = builder::message()
            ->conversation($conversation)
            ->from($bot)
            ->recipient($user)
            ->text('Test message')
            ->build();
        $this->assertEquals('a:k1a0RA-_-koUT0u', $message->conversation->id);
        $this->assertEquals('31415926-5358-9793-2384-626433832795', $message->conversation->tenantId);
        $this->assertEquals('28:tH1siSAb0t', $message->from->id);
        $this->assertEquals('mybot', $message->from->name);
        $this->assertEquals('29:IaMtH3c00Le5tUs3r', $message->recipient->id);
        $this->assertEquals('Bob', $message->recipient->name);
        $this->assertEquals('Test message', $message->text);

        $message = builder::message()
            ->conversation($conversation)
            ->from($bot)
            ->recipient($user)
            ->attachment_layout('carousel')
            ->add_attachment(builder::hero_card()
                ->title('Kia ora')
                ->text('Test message')
                ->add_image('https://example.com/static/assets/test.jpg', 'Test image')
                ->add_button(builder::action()
                    ->url('Go to event', 'https://example.com/mod/facetoface/eventinfo.php?s=1')
                    ->build())
                ->add_button(builder::action()
                    ->message_back('Sign up', 'signup')
                    ->build())
               ->build())
            ->summary('Test summary')
            ->build();
        $this->assertEquals('a:k1a0RA-_-koUT0u', $message->conversation->id);
        $this->assertEquals('31415926-5358-9793-2384-626433832795', $message->conversation->tenantId);
        $this->assertEquals('28:tH1siSAb0t', $message->from->id);
        $this->assertEquals('mybot', $message->from->name);
        $this->assertEquals('29:IaMtH3c00Le5tUs3r', $message->recipient->id);
        $this->assertEquals('Bob', $message->recipient->name);
        $this->assertEquals('carousel', $message->attachmentLayout);
        $this->assertCount(1, $message->attachments);
        $this->assertEquals('application/vnd.microsoft.card.hero', $message->attachments[0]->contentType);
        $this->assertEquals('Kia ora', $message->attachments[0]->content->title);
        $this->assertEquals('Test message', $message->attachments[0]->content->text);
        $this->assertCount(1, $message->attachments[0]->content->images);
        $this->assertEquals('Test image', $message->attachments[0]->content->images[0]->alt);
        $this->assertEquals('https://example.com/static/assets/test.jpg', $message->attachments[0]->content->images[0]->url);
        $this->assertCount(2, $message->attachments[0]->content->buttons);
        $this->assertEquals('openUrl', $message->attachments[0]->content->buttons[0]->type);
        $this->assertEquals('Go to event', $message->attachments[0]->content->buttons[0]->title);
        $this->assertEquals('Go to event', $message->attachments[0]->content->buttons[0]->text);
        $this->assertEquals('https://example.com/mod/facetoface/eventinfo.php?s=1', $message->attachments[0]->content->buttons[0]->value);
        $this->assertEquals('messageBack', $message->attachments[0]->content->buttons[1]->type);
        $this->assertEquals('Sign up', $message->attachments[0]->content->buttons[1]->title);
        $this->assertEquals('signup', $message->attachments[0]->content->buttons[1]->text);
        $this->assertEquals('signup', $message->attachments[0]->content->buttons[1]->displayText);
        $this->assertEquals('Test summary', $message->summary);

        $message = builder::message()
            ->conversation($conversation)
            ->from($bot)
            ->recipient($user)
            ->attachment_layout('list')
            ->add_attachment(builder::list_card()
                ->title('My favourites')
                ->add_item(builder::list_item()
                    ->type('section')
                    ->title('Favourites')
                    ->build())
                ->add_item(builder::list_item()
                    ->type('resultItem')
                    ->title('Culinary arts')
                    ->subtitle('Courses')
                    ->tap(builder::action()
                        ->url('Go to course', 'https://example.com/course/view.php?id=2')
                        ->build())
                    ->build())
                ->build())
            ->build();
        $this->assertEquals('a:k1a0RA-_-koUT0u', $message->conversation->id);
        $this->assertEquals('31415926-5358-9793-2384-626433832795', $message->conversation->tenantId);
        $this->assertEquals('28:tH1siSAb0t', $message->from->id);
        $this->assertEquals('mybot', $message->from->name);
        $this->assertEquals('29:IaMtH3c00Le5tUs3r', $message->recipient->id);
        $this->assertEquals('Bob', $message->recipient->name);
        $this->assertEquals('list', $message->attachmentLayout);
        $this->assertCount(1, $message->attachments);
        $this->assertEquals('application/vnd.microsoft.teams.card.list', $message->attachments[0]->contentType);
        $this->assertEquals('My favourites', $message->attachments[0]->content->title);
        $this->assertCount(2, $message->attachments[0]->content->items);
        $this->assertEquals('section', $message->attachments[0]->content->items[0]->type);
        $this->assertEquals('Favourites', $message->attachments[0]->content->items[0]->title);
        $this->assertEquals('resultItem', $message->attachments[0]->content->items[1]->type);
        $this->assertEquals('Culinary arts', $message->attachments[0]->content->items[1]->title);
        $this->assertEquals('Courses', $message->attachments[0]->content->items[1]->subtitle);
        $this->assertEquals('openUrl', $message->attachments[0]->content->items[1]->tap->type);
        $this->assertEquals('Go to course', $message->attachments[0]->content->items[1]->tap->title);
        $this->assertEquals('Go to course', $message->attachments[0]->content->items[1]->tap->text);
        $this->assertEquals('https://example.com/course/view.php?id=2', $message->attachments[0]->content->items[1]->tap->value);
    }
}
