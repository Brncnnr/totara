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

namespace totara_msteams\botfw;

use dml_exception;
use totara_core\http\client;
use totara_core\http\exception\bad_format_exception;
use totara_core\http\exception\http_exception;
use totara_core\http\request;
use totara_core\http\response;
use totara_core\util\language;
use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\account\conversation_account;
use totara_msteams\botfw\auth\authoriser;
use totara_msteams\botfw\auth\token\bot_token;
use totara_msteams\botfw\entity\bot as bot_entity;
use totara_msteams\botfw\exception\bot_unavailable_exception;
use totara_msteams\botfw\exception\botfw_exception;
use totara_msteams\botfw\hook\hook;
use totara_msteams\botfw\hook\null_hook;
use totara_msteams\botfw\logger\logger;
use totara_msteams\botfw\notification\notification;
use totara_msteams\botfw\notification\subscription;
use totara_msteams\botfw\resolver\resolver;
use totara_msteams\botfw\router\route;
use totara_msteams\botfw\router\router;
use totara_msteams\botfw\storage\storage;
use totara_msteams\botfw\validator\validator;

/**
 * A bot class.
 */
class bot implements context {
    /** @var router */
    private $router;

    /** @var authoriser */
    private $authoriser;

    /** @var client */
    private $client;

    /** @var resolver */
    private $resolver;

    /** @var notification */
    private $notification;

    /** @var storage */
    private $storage;

    /** @var validator */
    private $validator;

    /** @var logger */
    private $logger;

    /** @var bot_entity|null */
    private $bot_entity = null;

    /** @var boolean */
    private $in_process = false;

    /** @var hook|null */
    private $hook = null;

    /**
     * Constructor.
     *
     * @param router $router
     * @param authoriser $authoriser
     * @param client $client
     * @param resolver $resolver
     * @param notification $notification
     * @param storage $storage
     * @param validator $validator
     * @param logger $logger
     */
    public function __construct(router $router, authoriser $authoriser, client $client, resolver $resolver, notification $notification, storage $storage, validator $validator, logger $logger) {
        $this->router = $router;
        $this->authoriser = $authoriser;
        $this->client = $client;
        $this->resolver = $resolver;
        $this->notification = $notification;
        $this->storage = $storage;
        $this->validator = $validator;
        $this->logger = $logger;
        $authoriser->initialise($this);
        $storage->initialise($this);
    }

    /**
     * Get the associated router interface.
     *
     * @return router
     */
    public function get_router(): router {
        return $this->router;
    }

    /**
     * Get the associated authentication interface.
     *
     * @return authoriser
     */
    public function get_authoriser(): authoriser {
        return $this->authoriser;
    }

    /**
     * @inheritDoc
     */
    public function get_client(): client {
        return $this->client;
    }

    /**
     * Get the associated URL resolver interface.
     *
     * @return resolver
     */
    public function get_resolver(): resolver {
        return $this->resolver;
    }

    /**
     * Get the associated notification interface.
     *
     * @return notification
     */
    public function get_notification(): notification {
        return $this->notification;
    }

    /**
     * @inheritDoc
     */
    public function get_storage(): storage {
        return $this->storage;
    }

    /**
     * @inheritDoc
     */
    public function get_logger(): logger {
        return $this->logger;
    }

    /**
     * @inheritDoc
     */
    public function get_bot_id(): string {
        if ($this->bot_entity === null) {
            throw new bot_unavailable_exception();
        }
        return $this->bot_entity->bot_id;
    }

    /**
     * @inheritDoc
     */
    public function get_service_url(): string {
        if ($this->bot_entity === null) {
            throw new bot_unavailable_exception();
        }
        return $this->bot_entity->service_url;
    }

    /**
     * Return a hook instance, or a null_hook instance if none is registered.
     *
     * @return hook
     */
    protected function get_hook(): hook {
        if ($this->hook !== null) {
            return $this->hook;
        }

        static $null = null;
        if ($null === null) {
            $null = new null_hook();
        }
        return $null;
    }

    /**
     * Install a hook.
     *
     * @param hook $hook
     * @throws botfw_exception thrown when a hook is already installed.
     */
    public function set_hook(hook $hook): void {
        if ($this->hook !== null && $this->hook !== $hook) {
            throw new botfw_exception('Cannot override the existing hook once it is set.');
        }
        $this->hook = $hook;
    }

    /**
     * Start a new user session.
     * This function just calls hook::set_user().
     *
     * @param integer $userid Totara user id
     */
    public function set_user(int $userid): void {
        $this->get_hook()->set_user($userid);
    }

    /**
     * Get the bot account.
     *
     * @return channel_account
     */
    public function get_account(): channel_account {
        if ($this->bot_entity === null) {
            throw new bot_unavailable_exception();
        }
        return $this->bot_entity->to_account();
    }

    /**
     * Get the OAuth2 access token for the bot.
     *
     * @return string
     */
    protected function get_access_token(): string {
        $token = bot_token::try_load_cache($this);
        if ($token === null) {
            $token = bot_token::refresh($this);
        }
        return $token->get();
    }

    /**
     * Prepare HTTP headers for the Bot Framework API.
     *
     * @return array
     */
    private function http_headers(): array {
        $token = $this->get_access_token();
        $headers = [
            'Authorization' => 'Bearer '.$token,
            // NOTE: "User-Agent: TotaraBot/1.0" will be added if curl_client is used.
        ];
        return $headers;
    }

    /**
     * Preparation.
     *
     * @param activity $input
     */
    private function startup(activity $input): void {
        global $CFG;
        if (!isset($input->serviceUrl) || !isset($input->recipient->id)) {
            throw new botfw_exception('Invalid activity');
        }

        $bot_id = $input->recipient->id;
        $bot_name = $input->recipient->name ?? null;
        $bot = bot_entity::repository()->find_by_id($input->recipient->id);
        if ($bot) {
            if ($bot->bot_name !== $bot_name) {
                $bot->bot_name = $bot_name;
                $bot->save();
            }
        } else {
            $bot = new bot_entity();
            $bot->bot_id = $bot_id;
            $bot->bot_name = $bot_name;
            // Set an invalid URL.
            $bot->service_url = $CFG->wwwroot . '/totara/msteams/classes/botfw/invalid/';
            $bot->save();
        }
        // service_url will be saved to the database later.
        $service_url = $input->serviceUrl;
        if ($bot->service_url !== $service_url) {
            $bot->service_url = $service_url;
        }
        $this->bot_entity = $bot;
    }

    /**
     * Handle incoming request.
     *
     * @param activity $input
     * @param string[] $headers the HTTP header
     * @return boolean false if the HTTP header is not valid
     * @throws botfw_exception
     * @throws http_exception
     */
    public function process(activity $input, array $headers): bool {
        return $this->process_callback($input, function(activity $input, validator $validator) use ($headers) {
            if (!$validator->validate_header($this, $headers)) {
                return false;
            }
            $route = $this->router->find_best_match($input);
            if ($route) {
                if (!$route->has(route::QUIET)) {
                    $this->typing($input);
                }
                $route->dispatch($this, $input);
            }
            return true;
        });
    }

    /**
     * Handle incoming request with callback.
     * Note that the caller must call validator::validate_header() before processing any sensitive data.
     *
     * @param activity $input
     * @param callable $callback a callback function that takes (activity, validator)
     *                           the bot record will be updated if the function returns true
     * @return boolean the value returned by the callback function
     */
    public function process_callback(activity $input, callable $callback): bool {
        if ($this->in_process) {
            throw new bot_unavailable_exception('Cannot call process() within a callback function.');
        }
        $lang = '';
        if (isset($input->locale)) {
            $lang = language::convert_to_totara_format($input->locale, false);
        }
        $this->in_process = true;
        $this->get_hook()->open($lang);
        $this->startup($input);
        $result = false;
        try {
            $result = $callback($input, $this->validator);
        } finally {
            if ($result) {
                // Save the service_url.
                $this->bot_entity->save();
            }
            $this->bot_entity = null;
            $this->get_hook()->close();
            $this->in_process = false;
        }
        return $result;
    }

    /**
     * Send a typing indicator.
     *
     * @param activity $from
     * @throws bot_unavailable_exception
     */
    protected function typing(activity $from): void {
        $this->validator->validate_activity($this, $from);
        // The return value of validate_activity() is ignored.
        $typing = new activity();
        $typing->type = 'typing';
        // If possible, change this request to asynchronous for better throughput.
        $this->reply_to($from, $typing);
    }

    /**
     * Reply for the messaging extension.
     *
     * @param messaging_extension $data
     * @throws bot_unavailable_exception
     */
    public function reply_messaging_extension(messaging_extension $data): void {
        if ($this->bot_entity === null) {
            throw new bot_unavailable_exception();
        }
        $obj = $data->to_object();
        $body = json_encode(['composeExtension' => $obj]);
        echo $body;
        flush();
    }

    /**
     * Reply to the sender.
     *
     * @param activity $from
     * @param activity $to
     * @param boolean $quiet Set true to swallow an exception when the request is not fulfilled.
     * @throws bot_unavailable_exception
     */
    public function reply_to(activity $from, activity $to, bool $quiet = false): void {
        $this->validator->validate_activity($this, $from);
        // The return value of validate_activity() is ignored.
        $obj = $to->to_object();
        if (empty($obj->replyToId)) {
            $obj->replyToId = $from->id;
        }

        $headers = $this->http_headers();
        $url = $this->resolver->conversation_url($from->serviceUrl, $from->conversation->id, 'activities');
        $request = request::post($url, $obj, $headers);
        // $this->logger->debug(json_encode(['url' => $url, 'body' => $obj, 'headers' => $headers], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $response = $this->client->execute($request);
        // $this->logger->debug(json_encode(['code' => $response->get_http_code(), 'body' => $response->get_body()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (!$quiet) {
            $response->throw_if_error();
        }
    }

    /**
     * Reply a plain text message to the sender.
     *
     * @param activity $from
     * @param string $text
     * @param boolean $quiet Set true to swallow an exception when the request is not fulfilled.
     * @throws bot_unavailable_exception
     */
    public function reply_text_to(activity $from, string $text, bool $quiet = false): void {
        $this->validator->validate_activity($this, $from);
        // The return value of validate_activity() is ignored.
        $message = builder::message()
            ->conversation($from->conversation)
            ->from($this->get_account())
            ->recipient($from->from)
            ->text($text)
            ->build();
        $this->reply_to($from, $message, $quiet);
    }

    /**
     * Carry out the Bot Framework REST API on a conversation.
     * For more information about the API, see the following documentation.
     * https://docs.microsoft.com/en-us/azure/bot-service/rest-api/bot-framework-rest-connector-api-reference
     *
     * @param string $conversation_id
     * @param string $where
     * @param string $param
     * @return response
     * @throws bot_unavailable_exception
     */
    public function invoke_rest_on_conversation(string $conversation_id, string $where, string $param = null): response {
        if ($this->bot_entity === null) {
            throw new bot_unavailable_exception();
        }
        $headers = $this->http_headers();
        $url = $this->resolver->conversation_url($this->bot_entity->service_url, $conversation_id, $where, $param);
        $request = request::get($url, $headers);
        return $this->client->execute($request);
    }

    /**
     * Send a notification message to a user.
     *
     * @param integer $userid Totara user id
     * @param message $message do not set conversation, from and recipient fields
     * @param boolean $alert Set true to insert the notification message into the activity feed
     * @return boolean false if no subscriptons
     * @throws http_exception
     * @throws dml_exception
     */
    public function send_notification(int $userid, message $message, bool $alert = false): bool {
        $subscriptions = $this->notification->get_subscriptions($userid);
        if (empty($subscriptions)) {
            return false;
        }
        foreach ($subscriptions as $subscription) {
            $this->send_notification_message($subscription, $message, $alert);
        }
        return true;
    }

    /**
     * Send a notification message.
     *
     * @param subscription $subscription
     * @param message $message do not set conversation, from and recipient fields
     * @param boolean $alert Set true to insert the notification message into the activity feed
     * @param boolean $start Set true to always start a new conversation
     * @throws http_exception
     * @throws dml_exception
     */
    public function send_notification_message(subscription $subscription, message $message, bool $alert = false, bool $start = false): void {
        $old_entity = null;
        if (!$this->in_process) {
            $this->get_hook()->open($subscription->get_lang());
            // Temporarily override the bot instance.
            $old_entity = $this->bot_entity;
            $this->bot_entity = $subscription->get_bot_record();
        }

        try {
            $conversation_id = $this->initiate_conversation($subscription, $start);

            $message = clone $message;
            $message->conversation = new conversation_account();
            $message->conversation->id = $conversation_id;
            $message->from = new channel_account();
            $message->from->id = $subscription->get_bot_id();
            $message->recipient = new channel_account();
            $message->recipient->id = $subscription->get_teams_id();

            if ($alert) {
                if (!isset($message->channelData)) {
                    $message->channelData = (object)[];
                }
                if (!isset($message->channelData->notification)) {
                    $message->channelData->notification = (object)[];
                }
                $message->channelData->notification->alert = true;
            }

            $obj = $message->to_object();
            $headers = $this->http_headers();
            $url = $this->resolver->conversation_url($this->get_service_url(), $conversation_id, 'activities');

            $request = request::post($url, $obj, $headers);
            $response = $this->client->execute($request);

            // We may as well retry with a new conversation if the response contains an error.
            // if (!$response->is_ok() && !$start) {
            //     $this->send_notification_message($subscription, $message, $alert, true);
            //     return;
            // }
            $response->throw_if_error();
        } finally {
            // Restore previous state.
            if ($old_entity) {
                $this->bot_entity = $old_entity;
            }
            if (!$this->in_process) {
                $this->get_hook()->close();
            }
        }
    }

    /**
     * Start a conversation.
     * For more information, see the following documentation.
     * https://docs.microsoft.com/en-us/azure/bot-service/rest-api/bot-framework-rest-connector-send-and-receive-messages#start-a-conversation
     *
     * @param subscription $subscription
     * @param boolean $start Set true to always start a new conversation
     * @return string
     * @throws dml_exception
     */
    protected function initiate_conversation(subscription $subscription, bool $start = false): ?string {
        if (!$start) {
            // Try the last conversation.
            $conversationid = $subscription->get_conversation_id();
            if (!empty($conversationid)) {
                return $conversationid;
            }
        }

        $headers = $this->http_headers();

        $data = [
            'bot' => ['id' => $subscription->get_bot_id()],
            'isGroup' => false,
            'members' => [['id' => $subscription->get_teams_id()]],
            'channelData' => ['tenant' => ['id' => $subscription->get_tenant_id()]]
        ];

        $headers = $this->http_headers();
        $service_url = $this->in_process ? $this->get_service_url() : $subscription->get_service_url();
        $url = $this->resolver->start_converstaion_url($service_url);
        $request = request::post($url, $data, $headers);
        $response = $this->client->execute($request);
        // $this->logger->debug("response: [{$response->get_http_code()}] {$response->get_body()}");
        $response->throw_if_error();

        $body = $response->get_body_as_json(false, true);
        if (empty($body->id)) {
            throw new bad_format_exception('Invalid response');
        }
        $conversationid = $body->id;
        $subscription->update_conversation_id($conversationid);
        return $conversationid;
    }
}
