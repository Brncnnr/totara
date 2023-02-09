<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\card;

use core_user\totara_engage\share\recipient\user;
use moodle_url;
use theme_config;
use totara_engage\entity\engage_bookmark;
use totara_engage\entity\share;
use totara_engage\entity\share_recipient;
use totara_engage\interactor\interactor;
use totara_engage\interactor\interactor_factory;
use totara_engage\link\builder;
use totara_engage\query\provider\helper;
use totara_engage\repository\bookmark_repository;
use totara_engage\repository\share_recipient_repository;
use totara_engage\repository\share_repository;
use totara_engage\share\recipient\recipient;
use totara_tui\output\component;

/**
 * Extending this class if you want to include the card of ur component instance into
 * engage contribution page.
 */
abstract class card {
    /**
     * This variable $instanceid has different meaning depending on the type of the card:
     * + for playlist, it is the playlist's id.
     * + for resource, it is the resource's id - within table {engage_resource} - field id.
     *
     * @var int
     */
    protected $instanceid;

    /**
     * @var null|string
     */
    private $name;

    /**
     * @var null|string
     */
    private $summary;

    /**
     * @var int
     */
    private $userid;

    /**
     * @var int
     */
    private $access;

    /**
     * @var null|int
     */
    private $timecreated;

    /**
     * @var null|int
     */
    private $timemodified;

    /**
     * @var null|string
     */
    protected $extra;

    /**
     * @var string
     */
    protected $component;

    /**
     * @var null|interactor
     */
    protected $interactor = null;

    /**
     * Preventing the complicated construction.
     * card_content constructor.
     *
     * @param int    $instanceid
     * @param string $component
     * @param int    $userid
     * @param int    $access
     */
    final public function __construct(int $instanceid, string $component, int $userid, int $access) {
        $this->instanceid = $instanceid;
        $this->component = $component;
        $this->access = $access;
        $this->userid = $userid;

        $this->name = null;
        $this->summary = null;
        $this->extra = null;
        $this->timemodified = null;
        $this->timecreated = null;
    }

    /**
     * @param array $record
     * @return card
     */
    public static function create(array $record): card {
        $keys = [
            'instanceid',
            'component',
            'userid',
            'access'
        ];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $record)) {
                throw new \coding_exception("No key '{$key}' found in the record array");
            }
        }

        $card = new static(
            $record['instanceid'],
            $record['component'],
            $record['userid'],
            $record['access']
        );

        if (array_key_exists('name', $record)) {
            $card->set_name($record['name']);
        }

        if (array_key_exists('summary', $record)) {
            $card->set_summary($record['summary']);
        }

        if (array_key_exists('extra', $record)) {
            $card->set_extra($record['extra']);
        }

        if (array_key_exists('timecreated', $record)) {
            $card->set_timecreated($record['timecreated']);
        }

        if (array_key_exists('timemodified', $record)) {
            $card->set_timemodified($record['timemodified']);
        }

        // Set interactor.
        $card->set_interactor(interactor_factory::create($record['component'], $record));

        return $card;
    }

    /**
     * @param string|null $name
     * @return void
     */
    public function set_name(?string $name): void {
        $this->name = $name;
    }

    /**
     * @param string|null $summary
     * @return void
     */
    public function set_summary(?string $summary): void {
        $this->summary = $summary;
    }

    /**
     * @param int $timecreated
     * @return void
     */
    public function set_timecreated(int $timecreated): void {
        $this->timecreated = $timecreated;
    }

    /**
     * @param int|null $timemodified
     * @return void
     */
    public function set_timemodified(?int $timemodified): void {
        $this->timemodified = $timemodified;
    }

    /**
     * @param array|null|\JsonSerializable|string $extra
     * @return void
     */
    public function set_extra($extra): void {
        if (is_string($extra) && "" !== $extra) {
            json_decode($extra);

            if (JSON_ERROR_NONE !== json_last_error()) {
                // It is just a string, not a json_encoded string.
                $this->extra = json_encode($extra);
            } else {
                $this->extra = $extra;
            }

            return;
        } else if (null == $extra) {
            $this->extra = null;
            return;
        }

        if (($extra instanceof \JsonSerializable) || is_array($extra)) {
            $this->extra = json_encode($extra);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \coding_exception("Cannot json encode the parameter due to: " . json_last_error_msg());
            }

            return;
        }

        debugging("Invalid type of parameter \$extra, the property will be set to null", DEBUG_DEVELOPER);
        $this->extra = null;
    }

    /**
     * @param interactor $interactor
     */
    public function set_interactor(interactor $interactor) {
        $this->interactor = $interactor;
    }

    /**
     * @return int
     */
    public function get_instanceid(): int {
        return $this->instanceid;
    }

    /**
     * @return string|null
     */
    public function get_name(): ?string {
        return $this->name;
    }

    /**
     * Returning the owner's id of this resource's card.
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Returning the owner's record of this resource's card.
     * @return \stdClass
     */
    public function get_user(): \stdClass {
        return \core_user::get_user($this->userid, '*', MUST_EXIST);
    }

    /**
     * @return int
     */
    public function get_access(): int {
        return $this->access;
    }

    /**
     * @return int|null
     */
    public function get_timecreated(): ?int {
        return $this->timecreated;
    }

    /**
     * @return int|null
     */
    public function get_timemodified(): ?int {
        return $this->timemodified;
    }

    /**
     * Returning a decoded json string extra, which could be helpful for extra data needed at front-end.
     * @return array
     */
    final protected function get_json_decoded_extra(): array {
        if (empty($this->extra)) {
            return [];
        }

        $extra = json_decode($this->extra, true);

        if (JSON_ERROR_NONE !== json_last_error() || null == $extra) {
            $msg = json_last_error_msg();
            debugging("Cannot parse the json content due to: {$msg}", DEBUG_DEVELOPER);

            return [];
        }

        return $extra;
    }

    /**
     * A function to provide the extra data that is needed at front-end. Override this function, if you want
     * to inject extra data into the front-end for rendering.
     *
     * @since Totara 13.6 added parameter $theme_config
     *
     * @param theme_config|null $theme_config
     * @return array
     */
    public function get_extra_data(?theme_config $theme_config = null): array {
        return [];
    }

    /**
     * @return string|null
     */
    public function get_summary(): ?string {
        return $this->summary;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->component;
    }

    /**
     * @return array
     */
    public function get_topics(): array {
        return [];
    }

    /**
     * Children card should implement this functionality to show the number of reactions.
     * Otherwise it can be leave as zero if the card does not implementing it.
     *
     * @return int
     */
    public function get_total_reactions(): int {
        return 0;
    }

    /**
     * @return int
     */
    public function get_sharedbycount(): int {
        /** @var share_repository $repo */
        $repo = share::repository();
        return $repo->get_total_sharers($this->get_instanceid(), $this->get_component());
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function is_bookmarked(int $userid): bool {
        /** @var bookmark_repository $repo */
        $repo = engage_bookmark::repository();
        return $repo->is_bookmarked($userid, $this->get_instanceid(), $this->get_component());
    }

    /**
     * Get any footnote information that we want to display.
     *
     * @param array $args
     * @return array
     */
    public function get_footnotes(array $args): array {
        $footnotes = [];

        // What type of footnotes are we looking for.
        if (!empty($args['type'])) {
            // Search results card footnotes.
            if ($args['type'] === 'search') {
                $footnotes[] = [
                    'component' => 'CardFoundInFootnote',
                    'tuicomponent' => 'totara_engage/components/card/footnote/FoundInFootnote',
                    'props' => json_encode([
                        'containers' => $this->get_containers()
                    ]),
                ];
            }

            // Shared with you card footnotes.
            elseif ($args['type'] === 'shared') {
                $info = $this->get_share_info($args['item_id'], $args['area'], $args['component']);
                if (!empty($info)) {
                    list($sharer, $recipient) = $info;

                    if ($recipient->area !== user::AREA) {
                        /** @var recipient $share_recipient */
                        $share_recipient = \totara_engage\share\recipient\helper::get_recipient_class(
                            $recipient->component,
                            $recipient->area
                        );
                        $share_recipient = new $share_recipient($recipient->instanceid);
                        $has_capability = $share_recipient->can_unshare_resources();
                    }

                    // This is a temporary fix for the encoding problem for a user's
                    // fullname. This section needs to be replaced by the solution
                    // from TL-30744. However note the data returned here goes the UI
                    // as a *json string* - which makes formatting stuff at the GraphQL
                    // layer even more complicated.
                    $sharer->fullname = html_to_text($sharer->fullname, 0, false);

                    $component = $this->get_component();
                    $footnotes[] = [
                        'component' => 'CardSharedByFootnote',
                        'tuicomponent' => 'totara_engage/components/card/footnote/SharedByFootnote',
                        'props' => json_encode([
                            'instanceId' => $this->get_instanceid(),
                            'component' => $component,
                            'sharer' => $sharer,
                            'recipientId' => (int)$recipient->id ?? null,
                            'area' => $recipient->area ?? null,
                            'showButton' => $has_capability ?? true,
                            'name' => $this->get_name()
                        ]),
                    ];
                }
            } else if ($args['type'] === 'playlist') {
                // Playlist owner card footnotes.
                $footnotes[] = [
                    'component' => 'PlaylistFootnote',
                    'tuicomponent' => 'totara_playlist/components/card/PlaylistFootnote',
                    'props' => json_encode([
                        'instanceId' => $this->get_instanceid(),
                        'playlistId' => $args['item_id'],
                    ]),
                ];
            }
        }

        return $footnotes;
    }

    /**
     * @param array $args
     * @return string
     */
    public function get_url(array $args): string {
        $url = builder::to($this->get_component(), ['id' => $this->get_instanceid()])->url();
        // If we're provided with a source, attach it
        if (!empty($args['source'])) {
            $url->param('source', $args['source']);
        }

        // If the search term is provided, then also attach it (as we'll probably need it)
        if (!empty($args['search'])) {
            $url->param('search', $args['search']);
        }

        return $url->out(false);
    }

    /**
     * Some cards might be in a container so lets get the container information.
     *
     * @return array
     */
    private function get_containers(): array {
        $providers = helper::get_resource_providers(true);

        $all = [];
        foreach($providers as $provider) {
            $records =  $provider->get_container_details(
                $this->get_instanceid(),
                $this->get_component()
            );
            if (!empty($records)) {
                foreach ($records as $record) {
                    $all[] = $record;
                }
            }
        }

        return $all;
    }

    /**
     * Get sharer information.
     *
     * @param int|null $item_id
     * @param string|null $area
     * @param string|null $component
     * @return array|null
     */
    private function get_share_info(?int $item_id, ?string $area, ?string $component): ?array {
        global $USER;

        // Default user recipient item.
        if (!$item_id) {
            $item_id = $USER->id;
            $area = user::AREA;
            $component = \totara_engage\local\helper::get_component_name(user::class);
        }

        /** @var share_repository $repo */
        $repo = share::repository();
        $share = $repo->get_share($this->get_instanceid(), $this->get_component());

        if ($share) {
            /** @var share_recipient_repository $repo */
            $repo = share_recipient::repository();

            // Check if the item is a recipient of this share.
            $recipient = $repo->get_recipient_by_visibility(
                $share->id, $item_id, $area, $component
            );

            // If the item is a recipient then return details about who shared it.
            if ($recipient) {
                $sharer = \core_user::get_user($recipient->sharerid,
                    'id, firstname, middlename, lastname, firstnamephonetic, lastnamephonetic, alternatename'
                );
                $sharer->fullname = fullname($sharer);

                $url = new moodle_url("/user/profile.php", ['id' => $recipient->sharerid]);
                $sharer->url = $url->out();

                return [$sharer, $recipient];
            }
        }

        return null;
    }

    /**
     * Child card need to override this function in order to find out the
     * total comments on its instance.
     *
     * @return int
     */
    public function get_total_comments(): int {
        return 0;
    }

    /**
     * Get user capabilities for user interacting with this resource.
     *
     * @return interactor
     */
    public function get_interactor(): interactor {
        if (empty($this->interactor)) {
            $this->interactor = interactor_factory::create($this->component, [
                'access' => $this->access,
                'userid' => $this->userid,
            ]);
        }
        return $this->interactor;
    }

    /**
     * @return component
     */
    abstract public function get_tui_component(): component;

    /**
     * Return the URL of the image for this card.
     * Return null if there is no valid image involved.
     *
     * @since Totara 13.6 added parameter $theme_config
     *
     * @param string|null $preview_mode
     * @param theme_config|null $theme_config
     * @return moodle_url|null
     */
    abstract public function get_card_image(?string $preview_mode = null, ?theme_config $theme_config = null): ?moodle_url;

    /**
     * Return the component used to render the card image
     *
     * @return component
     */
    abstract public function get_card_image_component(): component;
}