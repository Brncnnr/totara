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

namespace totara_msteams\my\dispatcher;

use html_writer;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\builder;
use totara_msteams\botfw\dispatchable;
use totara_msteams\botfw\entity\user;
use totara_msteams\botfw\exception\auth_required_exception;
use totara_msteams\botfw\exception\botfw_exception;
use totara_msteams\my\helpers\catalog_helper;
use totara_msteams\my\helpers\notification_helper;
use totara_msteams\page_helper;

/**
 * A dispatcher that is indirectly triggered by MS Teams when a user interacts with the messaging extension.
 */
class messaging_extension implements dispatchable {
    /**
     * @inheritDoc
     */
    public function dispatch(bot $bot, activity $activity): void {
        try {
            $msuser = $bot->get_authoriser()->get_user($activity, $activity->from);
            $this->dispatch_worker($bot, $activity, $msuser);
        } catch (auth_required_exception $ex) {
            // https://docs.microsoft.com/en-us/microsoftteams/platform/messaging-extensions/how-to/add-authentication?view=msteams-client-js-latest
            // Here's our sign-in workflow.
            // 1. Send the sign-in link to a user
            // 2. When the user clicks the 'sign-in' link, the sign-in page is opened inside a popup window
            // 3. If single sign-on is disabled, the user will be prompted to enter their credential
            // 4. When the user successfully signs in, the popup window is redirected to the 'proceed sign-in' page
            // 5. Generate arbitrary security code
            // 6. Store it into user_state::verify_code
            // 7. Pass it through microsoftTeams.authentication.notifySuccess()
            // 8. The popup window is closed by MS Teams
            // 9. Compare activity->value->state with the stored security code
            // 10. If it's valid, proceed the user's request
            // 11. Otherwise, send a sign-in link again
            if (empty($activity->value->state)) {
                // Initiate sign-in workflow.
                $this->send_signin_card($bot, $activity);
            } else {
                // Proceed sign-in workflow.
                try {
                    $msuser = $bot->get_authoriser()->verify_login($activity, $activity->from, $activity->value->state);
                    if (!notification_helper::subscribe_and_reply($bot, $activity, $msuser)) {
                        $bot->get_logger()->error("user {$msuser->userid} has already subscribed");
                    }
                    $this->dispatch_worker($bot, $activity, $msuser);
                } catch (botfw_exception $ex) {
                    // Still failing?
                    $bot->get_logger()->debug("Sign-in failed\n".$ex->getMessage()."\n".$ex->getTraceAsString());
                    $this->send_signin_fails($bot, $activity);
                }
            }
        }
    }

    /**
     * @param array $parameters
     * @param string $name
     * @return string|null
     */
    private function get_param(array $parameters, string $name): ?string {
        foreach ($parameters as $parameter) {
            if ($parameter->name === $name) {
                return $parameter->value;
            }
        }
        return null;
    }

    /**
     * @param activity $activity
     * @return string|null|false
     */
    private function extract_search_query(activity $activity) {
        if (empty($activity->value) || $activity->value->commandId !== 'searchCommand' || empty($activity->value->parameters)) {
            // Unknown request.
            return false;
        }
        $parameters = $activity->value->parameters;
        if ($this->get_param($parameters, 'initialRun') === 'true') {
            return null;
        }
        $value = $this->get_param($parameters, 'search');
        if ($value === null) {
            return false;
        }
        if ($value === '') {
            return null;
        }
        return $value;
    }

    /**
     * Handle user's request.
     *
     * @param bot $bot
     * @param activity $activity
     * @param user $user
     */
    private function dispatch_worker(bot $bot, activity $activity, user $user): void {
        $query = $this->extract_search_query($activity);
        if ($query === false) {
            // Malicious request; do nothing.
            return;
        }

        // Set a user session.
        $bot->set_user($user->userid);

        if ($query === null) {
            $this->initial_run($bot);
            return;
        }

        $from = 0;
        $limit = 10; // defaults to 10 items
        if (!empty($activity->value->queryOptions)) {
            $from = $activity->value->queryOptions->skip ?? $from;
            $limit = $activity->value->queryOptions->count ?? $limit;
        }

        $this->do_search($bot, $query, $from, $limit);
    }

    /**
     * Return a message when a user does not enter a search keyword.
     *
     * @param bot $bot
     */
    private function initial_run(bot $bot): void {
        $message = builder::messaging_extension()
            ->type('message')
            ->text(get_string('botfw:mx_initialrun', 'totara_msteams'))
            ->build();
        $bot->reply_messaging_extension($message);
    }

    /**
     * Return a search result.
     *
     * @param bot $bot
     * @param string|null $query
     * @param integer $from
     * @param integer $limit
     */
    private function do_search(bot $bot, ?string $query, int $from, int $limit): void {
        global $CFG;
        $showimages = empty($CFG->forcelogin) || !empty($CFG->publishgridcatalogimage);
        $objects = catalog_helper::search($query, $from, $limit, $query !== null ? 'score' : 'featured');

        if (empty($objects)) {
            $message = builder::messaging_extension()
                ->type('message')
                ->text(get_string('botfw:mx_nomatches', 'totara_msteams'))
                ->build();
            $bot->reply_messaging_extension($message);
            return;
        }

        $builder = builder::messaging_extension()
            ->type('result')
            ->attachment_layout('list');
        foreach ($objects as $object) {
            $card = builder::hero_card()
                ->title(html_writer::tag('strong', s($object->name ?: '')));
            if ($object->summary !== '') {
                $card->text(html_writer::span($object->summary));
            }
            $card->subtitle(s($object->type));
            if (!empty($object->image) && $showimages) {
                $card->add_image($object->image->url, $object->image->alt);
            }
            if (!empty($object->link)) {
                $url = page_helper::create_deep_link($object->link->url, $object->name ?? null);
                $card->add_button(builder::action()
                    ->url($object->link->label, $url)
                    ->build());
            }
            $builder->add_attachment($card->build());
        }

        $message = $builder->build();
        $bot->reply_messaging_extension($message);
    }

    /**
     * Ask a user to sign in.
     *
     * @param bot $bot
     * @param activity $activity
     * @param boolean $retry
     */
    private function send_signin_card(bot $bot, activity $activity, bool $retry = false): void {
        $url = $bot->get_authoriser()->get_login_url($activity, $activity->from);
        if ($retry) {
            $text = get_string('botfw:mx_signinretry', 'totara_msteams');
        } else {
            $text = get_string('botfw:mx_signin', 'totara_msteams');
        }
        $message = builder::messaging_extension()
            ->type('auth')
            ->add_suggested_action(builder::action()
                ->type('openUrl')
                ->title($text)
                ->value($url->out(false))
                ->build())
            ->build();
        $bot->reply_messaging_extension($message);
    }

    /**
     * Return a message when verification fails.
     *
     * @param bot $bot
     */
    private function send_signin_fails(bot $bot, activity $activity): void {
        $this->send_signin_card($bot, $activity, true);
    }
}
