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

use totara_core\http\client;
use totara_core\http\clients\simple_mock_client;
use totara_core\http\request;
use totara_core\http\response;
use totara_core\util\base64url;
use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\auth\authoriser;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\context;
use totara_msteams\botfw\dispatchable;
use totara_msteams\botfw\entity\user;
use totara_msteams\botfw\exception\auth_required_exception;
use totara_msteams\botfw\exception\not_implemented_exception;
use totara_msteams\botfw\hook\hook;
use totara_msteams\botfw\logger\logger;
use totara_msteams\botfw\notification\notification;
use totara_msteams\botfw\notification\subscription;
use totara_msteams\botfw\resolver\resolver;
use totara_msteams\botfw\router\route;
use totara_msteams\botfw\router\router;
use totara_msteams\botfw\storage\memory_storage;
use totara_msteams\botfw\storage\storage;
use totara_msteams\botfw\validator\validator;


class mock_authoriser implements authoriser {
    /** @var user|null */
    public $user = null;

    public function initialise(context $context): void {
    }

    public function get_user(activity $activity, channel_account $account, bool $verified = true): user {
        if (!$this->user) {
            throw new auth_required_exception();
        }
        return $this->user;
    }

    public function delete_user(user $user): void {
        $this->user = null;
    }

    public function get_login_url(activity $activity, channel_account $account): moodle_url {
        return new moodle_url('https://localhost:999999/login.php');
    }

    public function verify_login(activity $activity, channel_account $account): user {
        if (!$this->user) {
            throw new auth_required_exception();
        }
        return $this->user;
    }
}

class mock_resolver implements resolver {
    public function start_converstaion_url(string $serviceurl): string {
        if (substr($serviceurl, -1) !== '/') {
            $serviceurl .= '/';
        }
        return $serviceurl.'conversation';
    }

    public function conversation_url(string $serviceurl, string $conversationid, string $route, string $subroute = null): string {
        return $this->start_converstaion_url($serviceurl).'/'.rawurlencode($conversationid).'/'.$route.($subroute ? ('/'.rawurlencode($subroute)) : '');
    }
}

class mock_client extends simple_mock_client {
}

class mock_router implements router, dispatchable {
    /** @var boolean */
    private $silent = false;

    /** @var activity|null */
    private $activity = null;

    public function get_routes(): array {
        $route = new route($this, $this->silent ? route::QUIET : 0);
        return [$route];
    }

    public function find_best_match(activity $activity): ?route {
        return $this->get_routes()[0];
    }

    public function dispatch(bot $bot, activity $activity): void {
        $this->activity = $activity;
    }

    /**
     * @param boolean $silent
     */
    public function silent(bool $silent = true): void {
        $this->silent = $silent;
    }

    /**
     * @return activity|null
     */
    public function last_activity(): ?activity {
        $activity = $this->activity;
        $this->activity = null;
        return $activity;
    }
}

class mock_logger implements logger {
    /** @var string[] */
    public $errors = [];

    /** @var string[] */
    public $warns = [];

    /** @var string[] */
    public $infos = [];

    /** @var string[] */
    public $logs = [];

    /** @var string[] */
    public $debugs = [];

    public function reset(): void {
        $this->errors = [];
        $this->warns = [];
        $this->infos = [];
        $this->logs = [];
        $this->debugs = [];
    }

    public function any(): bool {
        return !empty($this->errors)
            || !empty($this->warns)
            || !empty($this->infos)
            || !empty($this->logs)
            || !empty($this->debugs);
    }

    public function error(string $message): void {
        $this->errors[] = $message;
    }

    public function warn(string $message): void {
        $this->warns[] = $message;
    }

    public function info(string $message): void {
        $this->infos[] = $message;
    }

    public function log(string $message): void {
        $this->logs[] = $message;
    }

    public function debug(string $message): void {
        $this->debugs[] = $message;
    }
}

class mock_notification implements notification {
    /** @var integer */
    private $id = 0;

    /** @var subscription[] */
    private $subscriptions = [];

    /**
     * @param string $botid
     * @param string $serviceurl
     * @param string $conversationid
     * @param string $channelid
     * @param string $tenantid
     * @param string $teamsid
     * @param integer $userid
     */
    public function mock_subscription(string $botid, string $serviceurl, string $conversationid, string $channelid, string $tenantid, string $teamsid, int $userid): void {
        ++$this->id;
        $record = (object)[
            'id' => $this->id,
            'conversation_id' => $conversationid,
            'userid' => $userid,
            'lang' => 'en',
            'teams_id' => $teamsid,
            'channel_id' => $channelid,
            'tenant_id' => $tenantid,
            'bot_id' => $botid,
            'bot_name' => 'dontcare',
            'service_url' => $serviceurl,
        ];
        $this->subscriptions[] = subscription::from_record($record);
    }

    public function reset() {
        $this->subscriptions = [];
    }

    public function subscribe(user $msuser, string $tenant_id, channel_account $bot_account): bool {
        throw new not_implemented_exception('do not call me');
    }

    public function unsubscribe(user $msuser, string $tenant_id): bool {
        throw new not_implemented_exception('do not call me');
    }

    public function get_subscription(user $msuser, string $tenant_id, string $channel_id, string $bot_id): ?subscription {
        throw new not_implemented_exception('do not call me');
    }

    public function get_subscriptions(int $userid): array {
        return $this->subscriptions;
    }
}

class mock_validator implements validator {
    /** @var boolean */
    public $activity_result = true;

    /** @var boolean */
    public $header_result = true;

    /**
     * @inheritDoc
     */
    public function validate_activity(context $context, activity $activity): bool {
        return $this->activity_result;
    }

    /**
     * @inheritDoc
     */
    public function validate_header(context $context, array $headers): bool {
        return $this->header_result;
    }
}

class mock_hook implements hook {
    /** @var integer */
    public $opens = 0;
    /** @var integer */
    public $closes = 0;
    /** @var integer[] */
    public $setusers = [];

    public function reset(): void {
        $this->opens = 0;
        $this->closes = 0;
        $this->setusers = [];
    }

    public function open(string $language): void {
        $this->opens++;
    }

    public function close(): void {
        $this->closes++;
        if ($this->closes !== $this->opens) {
            throw new Exception('hook::close() does not match hook::open()');
        }
    }

    public function set_user(int $userid): void {
        $this->setusers[] = $userid;
    }
}

class mock_context implements context {
    /** @var string */
    public $bot_app_id;
    /** @var string */
    public $bot_app_secret;
    /** @var string */
    public $bot_id;
    /** @var string */
    public $service_url;
    /** @var mock_client */
    public $client;
    /** @var memory_storage */
    public $storage;
    /** @var mock_logger */
    public $logger;

    /**
     * @param string|null $bot_app_id
     * @param string|null $bot_app_secret
     * @param string|null $bot_id
     * @param string|null $service_url
     */
    public function __construct(string $bot_app_id = null, string $bot_app_secret = null, string $bot_id = null, string $service_url = null) {
        $this->bot_app_id = $bot_app_id ?? \core\uuid::generate();
        $this->bot_app_secret = $bot_app_secret ?? random_string();
        $this->bot_id = $bot_id ?? ('28:'.$this->bot_app_id);
        $this->service_url = $service_url ?? ('https://api'.random_string(5).'.example.com/v'.random_string());
        $this->client = new mock_client();
        $this->storage = new memory_storage($this->bot_app_id, $this->bot_app_secret);
        $this->logger = new mock_logger();
    }

    public function get_client(): client {
        return $this->client;
    }

    public function get_storage(): storage {
        return $this->storage;
    }

    public function get_logger(): logger {
        return $this->logger;
    }

    public function get_bot_id(): string {
        return $this->bot_id;
    }

    public function get_service_url(): string {
        return $this->service_url;
    }
}


/**
 * Mock the response of external web services.
 */
class mock_botframework_client implements client {
    /** @var response[] */
    private $responses = [];

    /** @var request[] */
    private $requests = [];

    /**
     * @param string $urlmatch
     * @param string $body
     * @param integer $code
     * @param string[] $headers
     */
    public function mock(string $urlmatch, string $body, int $code = 200, array $headers = []): void {
        $this->responses[$urlmatch] = new response($body, $code, $headers);
    }

    /**
     * @return request[]
     */
    public function get_requests(): array {
        return $this->requests;
    }

    /**
     * @param string $url
     * @return request
     */
    public function get_request(string $url): request {
        if (!isset($this->requests[$url])) {
            throw new Exception('Request not found: '.$url);
        }
        return $this->requests[$url];
    }

    public function reset(): void {
        $this->requests = [];
    }

    public function set_connect_timeout(int $timeout): client {
        return $this;
    }

    public function set_timeout(int $timeout): client {
        return $this;
    }

    public function execute(request $request): response {
        $url = $request->get_url();
        foreach ($this->responses as $urlmatch => $response) {
            if ($urlmatch === $url) {
                $this->requests[$url] = $request;
                return $response;
            }
        }
        throw new Exception('No response matches the request '.$url);
    }
}


abstract class botfw_bot_base_testcase extends advanced_testcase {
    /** @var string */
    protected $valid_jwt;

    /** @var mock_authoriser */
    protected $authoriser;

    /** @var mock_client */
    protected $client;

    /** @var mock_resolver */
    protected $resolver;

    /** @var memory_storage */
    protected $storage;

    /** @var mock_validator */
    protected $validator;

    /** @var mock_logger */
    protected $logger;

    /** @var mock_hook */
    protected $hook;

    /** @var bot */
    protected $bot;

    abstract protected function create_bot(): bot;

    public function setUp(): void {
        set_config('bot_app_id', '31622776-6016-8379-3319-988935444327', 'totara_msteams');
        set_config('bot_app_secret', 's33krit', 'totara_msteams');
        // Note that this is not a valid JWT but it is valid enough for testing.
        $this->valid_jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.e30.BMI_HUYbnGhqzQJclgarZIi-AvtIdZVwNWJFj6tJ9nc';
        $this->authoriser = new mock_authoriser();
        $this->client = new mock_client();
        $this->resolver = new mock_resolver();
        $this->storage = new memory_storage();
        $this->validator = new mock_validator();
        $this->logger = new mock_logger();
        $this->hook = new mock_hook();
        $this->bot = $this->create_bot();
        $this->bot->set_hook($this->hook);
    }

    public function tearDown(): void {
        $this->valid_jwt = null;
        $this->authoriser = null;
        $this->client = null;
        $this->resolver = null;
        $this->storage = null;
        $this->validator = null;
        $this->logger = null;
        $this->hook = null;
        $this->bot = null;
    }

    protected function mock_response(string $body = '', int $code = 200): void {
        $this->client->mock_queue(new response($body, $code, []));
    }

    protected function mock_token(): void {
        $json = json_encode([
            'access_token' => $this->valid_jwt,
            'token_type' => 'Bearer',
            'expires_in' => 3598
        ]);
        $this->mock_response($json);
    }

    protected function mock_activity(string $text = 'kia ora koutou!'): activity {
        $data = $this->mock_data_template();
        $data->type = 'message';
        $data->textFormat = 'plain';
        $data->text = $text;
        return activity::from_object($data);
    }

    protected function mock_messaging_extension(string $text = ''): activity {
        $data = $this->mock_data_template();
        $data->name = 'composeExtension/query';
        $data->type = 'invoke';
        $data->channelData->source = (object)['name' => 'compose'];
        $data->value = (object)[
            'commandId' => 'searchCommand',
            'queryOptions' => (object)[
                'skip' => 0,
                'count' => 25
            ],
        ];
        if (empty($text)) {
            $data->value->parameters = [
                (object)[
                    'name' => 'initialRun',
                    'value' => 'true'
                ]
            ];
        } else {
            $data->value->parameters = [
                (object)[
                    'name' => 'search',
                    'value' => $text
                ]
            ];
        }
        return activity::from_object($data);
    }

    private function mock_data_template(): stdClass {
        $time = new DateTime();
        return (object)[
            'timestamp' => $time->format('Y-m-d\TH:i:s.vZ'),
            'localTimestamp' => $time->format('Y-m-d\TH:i:s.vP'),
            'id' => rand(),
            'channelId' => '19:kIa0RAkoUt0u',
            'serviceUrl' => 'https://api.example.com/bot/',
            'locale' => 'en-GB',
            'channelData' => (object)[
                'tenant' => (object)[
                    'id' => '31415926-5358-9793-2384-626433832795',
                ],
            ],
            'conversation' => (object)[
                'conversationType' => 'personal',
                'tenantId' => '31415926-5358-9793-2384-626433832795',
                'id' => 'a:k1a0RA-_-koUT0u',
            ],
            'from' => (object)[
                'id' => '29:K1aKahAN3wzEa1ANd',
                'name' => 'Bob',
                'aadObjectId' => '27182818-2845-9045-2353-602874713526',
            ],
            'recipient' => (object)[
                'id' => '28:1aMAb0t',
                'name' => 'mybot',
            ],
            'entities' => [
                (object)[
                    'locale' => 'en-GB',
                    'country' => 'GB',
                    'platform' => 'Windows',
                    'type' => 'clientInfo',
                ]
            ],
        ];
    }
}

/**
 * PHPUnit test case that sets up RSA keys to generate an asymmetric signature for JWK.
 */
abstract class botfw_jwks_base_testcase extends advanced_testcase {
    /** @var resource[] */
    protected $resources;
    /** @var string[] */
    protected $privatekeys;
    /** @var string[] */
    protected $kids;
    /** @var string */
    protected $jwks;
    /** @var mock_context */
    protected $context;

    public function setUp(): void {
        $keys = [];
        $dn = [
            'countryName' => 'NZ',
            'stateOrProvinceName' => 'Wellington',
            'localityName' => 'Te Aro',
            'organizationName' => 'Totara Learning Solutions',
            'organizationalUnitName' => 'IT',
            'commonName' => 'Mr Totara',
            'emailAddress' => 'mrtotara@example.com'
        ];
        foreach ([256, 384, 512] as $bits) {
            // Generate a private key.
            $alg = "sha{$bits}";
            $res = openssl_pkey_new([
                'digest_alg' => $alg,
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            if ($res === false) {
                if (PHP_OS_FAMILY === 'Windows') {
                    // Windows does not have OpenSSL setup out of the box.
                    $this->markTestSkipped('See https://www.php.net/manual/en/openssl.installation.php to set up OpenSSL.');
                }
                $this->fail();
            }
            if (openssl_pkey_export($res, $privatekey) === false) {
                $this->dump_errors();
                $this->markTestSkipped("openssl_pkey_export() failed for SHA-{$bits}");
            }
            $details = openssl_pkey_get_details($res);
            // Generate a certificate for the x5c parameter. (X.509 certificate chain)
            $csr = openssl_csr_new($dn, $privatekey, ['digest_alg' => $alg]);
            if ($csr === false) {
                $this->dump_errors();
                $this->markTestSkipped("openssl_csr_new() failed for SHA-{$bits}");
            }
            $x509 = openssl_csr_sign($csr, null, $privatekey, 7, ['digest_alg' => $alg]);
            if ($x509 === false) {
                $this->dump_errors();
                $this->markTestSkipped("openssl_csr_sign() failed for SHA-{$bits}");
            }
            if (openssl_x509_export($x509, $cert, true) === false) {
                $this->dump_errors();
                $this->markTestSkipped("openssl_x509_export() failed for SHA-{$bits}");
            }
            // Remove the beginning and ending tags as well as line breaks.
            $cert = preg_replace('/-----BEGIN CERTIFICATE-----(.*)-----END CERTIFICATE-----/ms', '$1', $cert);
            $cert = preg_replace('/[\r\n]/m', '', $cert);

            $kid = random_string(27);
            $this->resources[$bits] = $res;
            $this->privatekeys[$bits] = $privatekey;
            $this->kids[$bits] = $kid;

            $keys[] = [
                'kty' => 'RSA',
                'use' => 'sig',
                'kid' => $kid,
                'x5t' => $kid,
                'n' => base64url::encode($details['rsa']['n']),
                'e' => base64url::encode($details['rsa']['e']),
                'x5c' => [$cert],
                'endorsements' => [
                    'skype',
                    'msteams'
                ]
            ];
        }
        $this->jwks = json_encode(['keys' => $keys], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->context = new mock_context();
    }

    public function tearDown(): void {
        foreach ($this->resources ?? [] as $resource) {
            if (version_compare(PHP_VERSION, '8.0', '<')) {
                openssl_pkey_free($resource);
            }
        }
        $this->resources = null;
        $this->privatekeys = null;
        $this->kids = null;
        $this->jwks = null;
        $this->context = null;
    }

    /**
     * Output OpenSSL errors.
     */
    private function dump_errors() {
        while (($msg = openssl_error_string()) !== false) {
            echo "{$msg}\n";
        }
    }

    /**
     * Create a self-signed JWT.
     *
     * @param integer $bits SSH bits - 256, 384 or 512
     * @param array $payload the payload part
     * @return string JWT as string
     */
    protected function create_signed_jwt(int $bits, array $payload): string {
        $openssl_algos = [
            256 => OPENSSL_ALGO_SHA256,
            384 => OPENSSL_ALGO_SHA384,
            512 => OPENSSL_ALGO_SHA512,
        ];
        $header = [
            'alg' => "RS{$bits}",
            'kid' => $this->kids[$bits],
            'typ' => 'JWT',
            'x5t' => $this->kids[$bits],
        ];
        $data = base64url::encode(json_encode($header, JSON_UNESCAPED_SLASHES)) . '.' . base64url::encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        openssl_sign($data, $signature, $this->resources[$bits], $openssl_algos[$bits]);
        return $data . '.' . base64url::encode($signature);
    }

    /**
     * @param string $body
     * @param integer $code
     */
    protected function mock_response(string $body = '', int $code = 200): void {
        $this->context->client->mock_queue(new response($body, $code, []));
    }

    /**
     * Mock openid configuration and jwks requests.
     */
    protected function mock_jwks() {
        $oidc = json_encode([
            'issuer' => 'https://api.botframework.com',
            'authorization_endpoint' => 'https://invalid.example.com',
            'jwks_uri' => 'https://login.example.com/.well-known/keys',
            'id_token_signing_alg_values_supported' => ['RS256', 'RS384', 'RS512'],
            'token_endpoint_auth_methods_supported' => ['private_key_jwt']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->mock_response($oidc);
        $this->mock_response($this->jwks);
    }
}
