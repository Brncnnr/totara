<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_playlist
-->
<template>
  <EngageSidePanel
    v-if="!$apollo.loading"
    class="tui-playlistSidePanel"
    :show-related="featureRecommenders"
  >
    <template v-slot:author-profile>
      <MiniProfileCard
        :display="user.card_display"
        :no-border="true"
        :no-padding="true"
        class="tui-playlistSidePanel__profile"
      >
        <template v-if="canManage" v-slot:drop-down-items>
          <DropdownItem @click="modal.confirm = true">
            {{ $str('delete', 'core') }}
          </DropdownItem>
        </template>
      </MiniProfileCard>
    </template>

    <template v-slot:modal>
      <ConfirmationModal
        :open="modal.confirm"
        :title="$str('deletewarningtitle', 'totara_playlist')"
        :confirm-button-text="$str('delete', 'core')"
        :loading="deleting"
        @confirm="handleDelete"
        @cancel="modal.confirm = false"
      >
        <p>{{ $str('delete_playlist_confirm_1', 'totara_playlist') }}</p>
        <p>{{ $str('delete_playlist_confirm_2', 'totara_playlist') }}</p>
      </ConfirmationModal>
    </template>

    <template v-slot:overview>
      <div class="tui-playlistSidePanel__overview">
        <PageLoader :fullpage="true" :loading="submitting" />
        <p class="tui-playlistSidePanel__timeDescription">
          {{ playlist.timedescription }}
        </p>

        <AccessSetting
          v-if="canManage"
          :item-id="playlistId"
          :has-non-public-resources="playlist.hasnonpublicresources"
          component="totara_playlist"
          :access-value="playlist.access"
          :topics="playlist.topics"
          :submitting="submitting"
          :open-access-modal="openModalFromButtonLabel"
          :show-share-button="interactor.show_share_button"
          @access-update="updateAccess"
          @close-modal="openModalFromButtonLabel = false"
        />
        <AccessDisplay
          v-else
          :access-value="playlist.access"
          :topics="playlist.topics"
          :show-button="false"
        />

        <PlaylistSummary
          :update-able="canUpdate"
          :instance-id="playlist.id"
          :summary="playlist.summary"
          class="tui-playlistSidePanel__summary"
        />

        <div class="tui-playlistSidePanel__setting">
          <PlaylistStarRating
            v-if="interactor.can_rate"
            :owned="playlist.owned"
            :count="playlist.rating.count"
            :rating="playlist.rating.rating"
            :rated="playlist.rating.rated"
            @rating="createRating"
          />

          <MediaSetting
            :owned="canManage"
            :access-value="playlist.access"
            :instance-id="playlistId"
            :share-button-aria-label="shareButtonLabel"
            :shared-by-count="playlist.sharedbycount"
            :show-like-button="interactor.can_react"
            :show-share-button="interactor.can_share"
            component-name="totara_playlist"
            class="tui-playlistSidePanel__media"
            @access-update="updateAccess"
            @access-modal="openModalFromButtonLabel = true"
          />
        </div>
      </div>
    </template>

    <template v-slot:comments>
      <SidePanelCommentBox
        component="totara_playlist"
        area="comment"
        editor-variant="basic"
        :extra-extensions="['hashtag', 'mention']"
        :instance-id="playlist.id"
        :editor-context-id="playlist.contextid"
        class="tui-playlistSidePanel__commentBox"
        :show-comment="interactor.can_comment"
      />
    </template>
    <template v-if="featureRecommenders" v-slot:related>
      <Related
        component="totara_playlist"
        area="related"
        :playlist-id="playlist.id"
      />
    </template>
  </EngageSidePanel>
</template>

<script>
import EngageSidePanel from 'totara_engage/components/sidepanel/EngageSidePanel';
import SidePanelCommentBox from 'totara_comment/components/box/SidePanelCommentBox';
import AccessSetting from 'totara_engage/components/sidepanel/access/AccessSetting';
import AccessDisplay from 'totara_engage/components/sidepanel/access/AccessDisplay';
import PageLoader from 'tui/components/loading/Loader';
import PlaylistSummary from 'totara_playlist/components/sidepanel/PlaylistSummary';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import MediaSetting from 'totara_engage/components/sidepanel/media/MediaSetting';
import PlaylistStarRating from 'totara_playlist/components/sidepanel/PlaylistStarRating';
import Related from 'totara_playlist/components/sidepanel/Related';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import { AccessManager } from 'totara_engage/index';

// GraphQL queries
import getPlaylist from 'totara_playlist/graphql/get_playlist';
import updatePlaylist from 'totara_playlist/graphql/update_playlist';
import deletePlaylist from 'totara_playlist/graphql/delete_playlist';
import addRating from 'totara_playlist/graphql/add_rating';
import engageAdvancedFeatures from 'totara_engage/graphql/advanced_features';

export default {
  components: {
    EngageSidePanel,
    SidePanelCommentBox,
    AccessSetting,
    AccessDisplay,
    PlaylistSummary,
    PlaylistStarRating,
    PageLoader,
    ConfirmationModal,
    MediaSetting,
    Related,
    MiniProfileCard,
    DropdownItem,
  },

  props: {
    playlistId: {
      type: [Number, String],
      required: true,
    },
    interactor: {
      type: Object,
      default: () => ({
        user_id: 0,
        can_bookmark: false,
        can_comment: false,
        can_rate: false,
        can_react: false,
        can_share: false,
        show_share_button: false,
      }),
    },
  },

  apollo: {
    playlist: {
      query: getPlaylist,
      variables() {
        return {
          id: this.playlistId,
        };
      },
    },

    features: {
      query: engageAdvancedFeatures,
    },
  },

  data() {
    return {
      playlist: {},
      submitting: false,
      deleting: false,
      modal: {
        confirm: false,
      },
      openModalFromButtonLabel: false,
    };
  },

  computed: {
    user() {
      if (!this.playlist.user) {
        return {};
      }

      return this.playlist.user;
    },

    featureRecommenders() {
      return this.features && this.features.recommenders;
    },

    canManage() {
      return this.playlist.owned || this.playlist.manageable;
    },

    canUpdate() {
      return this.playlist.updatable || this.canManage;
    },

    isPrivate() {
      return AccessManager.isPrivate(this.playlist.access);
    },

    shareButtonLabel() {
      if (this.playlist.owned) {
        return this.$str(
          'shareplaylist',
          'totara_playlist',
          this.playlist.name
        );
      }

      return this.$str(
        'reshareplaylist',
        'totara_playlist',
        this.playlist.name
      );
    },
  },

  methods: {
    /**
     *
     * @param {String} access
     * @param {Array|Object} topics
     * @param {Array} shares
     */
    updateAccess({ access, topics, shares }) {
      topics = Array.prototype.slice.call(topics);
      this.submitting = true;

      this.$apollo
        .mutate({
          mutation: updatePlaylist,
          refetchQueries: [
            'totara_playlist_cards',
            'totara_playlist_get_playlist',
          ],
          variables: {
            id: this.playlistId,
            access: access,
            topics: topics.map(({ id }) => id),
            shares: shares,
          },

          update: (proxy, { data }) => {
            proxy.writeQuery({
              query: getPlaylist,
              variables: { id: this.playlistId },
              data,
            });
          },
        })
        .finally(() => {
          this.submitting = false;
          this.$emit('playlist-updated');
        });
    },

    handleDelete() {
      this.deleting = true;
      this.$apollo
        .mutate({
          mutation: deletePlaylist,
          variables: {
            id: this.playlistId,
          },
          refetchAll: false,
        })
        .then(({ data }) => {
          if (data.result) {
            this.$children.openModal = false;
            window.location.href = this.$url(
              '/totara/engage/your_resources.php'
            );
          }
        });
    },

    /**
     *
     * @param {Number} innerValue
     */
    createRating(innerValue) {
      if (!this.submitting) {
        this.submitting = true;
      }

      this.$apollo
        .mutate({
          mutation: addRating,
          refetchQueries: [
            {
              query: getPlaylist,
              variables: {
                id: this.playlistId,
              },
            },
          ],
          variables: {
            playlistid: this.playlistId,
            rating: Math.ceil(innerValue),
            ratingarea: 'playlist',
          },
        })
        .then(({ data }) => {
          if (data.result) {
            this.submitting = false;
          }
        });
    },
  },
};
</script>

<lang-strings>
  {
    "totara_playlist": [
      "reshareplaylist",
      "shareplaylist",
      "delete_playlist_confirm_1",
      "delete_playlist_confirm_2",
      "deletewarningtitle"
    ],

    "core": [
      "delete"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-playlistSidePanel {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  height: 100%;
  padding: var(--gap-8);

  &__timeDescription {
    @include tui-font-body-small();
  }

  &__setting {
    display: flex;
    flex-direction: row;
  }

  &__media {
    margin-top: 0;
    margin-left: var(--gap-6);
  }
}
</style>
