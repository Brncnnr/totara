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
  @module engage_article
-->

<template>
  <EngageSidePanel
    v-if="!$apollo.loading"
    class="tui-engageArticleSidePanel"
    :show-related="featureRecommenders"
  >
    <MiniProfileCard
      slot="author-profile"
      :display="user.card_display"
      :no-border="true"
      :no-padding="true"
    >
      <template v-slot:drop-down-items>
        <DropdownItem
          v-if="article.owned || article.updateable"
          @click="openModalFromAction = true"
        >
          {{ $str('delete', 'core') }}
        </DropdownItem>
        <DropdownItem v-if="!article.owned" @click="reportResource">
          {{ $str('reportresource', 'engage_article') }}
        </DropdownItem>
      </template>
    </MiniProfileCard>

    <template v-slot:modal>
      <ConfirmationModal
        :open="openModalFromAction"
        :loading="deleting"
        :title="$str('deletewarningtitle', 'engage_article')"
        :confirm-button-text="$str('delete', 'core')"
        @confirm="handleDelete"
        @cancel="openModalFromAction = false"
      >
        <p>{{ $str('delete_resource_confirm_1', 'engage_article') }}</p>
        <p>{{ $str('delete_resource_confirm_2', 'engage_article') }}</p>
      </ConfirmationModal>
    </template>

    <template v-slot:overview>
      <Loader :fullpage="true" :loading="submitting" />
      <p class="tui-engageArticleSidePanel__timeDescription">
        {{ article.timedescription }}
      </p>
      <AccessSetting
        v-if="article.owned || article.updateable"
        :item-id="resourceId"
        component="engage_article"
        :access-value="article.resource.access"
        :topics="article.topics"
        :submitting="false"
        :open-access-modal="openModalFromButtonLabel"
        :selected-time-view="article.timeview"
        :enable-time-view="true"
        :show-share-button="interactor.show_share_button"
        @access-update="updateAccess"
        @close-modal="openModalFromButtonLabel = false"
      />
      <AccessDisplay
        v-else
        :access-value="article.resource.access"
        :time-view="article.timeview"
        :topics="article.topics"
        :show-button="false"
      />

      <MediaSetting
        :owned="article.owned"
        :access-value="article.resource.access"
        :instance-id="resourceId"
        :share-button-aria-label="shareButtonLabel"
        :shared-by-count="article.sharedbycount"
        :like-button-aria-label="likeButtonLabel"
        :liked="article.reacted"
        :show-like-button="interactor.can_react"
        :show-share-button="interactor.can_share"
        component-name="engage_article"
        @access-update="updateAccess"
        @access-modal="openModalFromButtonLabel = true"
        @update-like-status="updateLikeStatus"
      />

      <ArticlePlaylistBox
        :resource-id="resourceId"
        class="tui-engageArticleSidePanel__playlistBox"
      />
    </template>

    <template v-slot:comments>
      <SidePanelCommentBox
        component="engage_article"
        area="comment"
        editor-variant="basic"
        :extra-extensions="['mention', 'hashtag']"
        :instance-id="resourceId"
        :editor-context-id="article.resource.context_id"
        :show-comment="interactor.can_comment"
      />
    </template>

    <template v-if="featureRecommenders" v-slot:related>
      <Related
        component="engage_article"
        area="related"
        :resource-id="resourceId"
      />
    </template>
  </EngageSidePanel>
</template>

<script>
import apolloClient from 'tui/apollo_client';
import Loader from 'tui/components/loading/Loader';
import SidePanelCommentBox from 'totara_comment/components/box/SidePanelCommentBox';
import AccessDisplay from 'totara_engage/components/sidepanel/access/AccessDisplay';
import AccessSetting from 'totara_engage/components/sidepanel/access/AccessSetting';
import EngageSidePanel from 'totara_engage/components/sidepanel/EngageSidePanel';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import MediaSetting from 'totara_engage/components/sidepanel/media/MediaSetting';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import ArticlePlaylistBox from 'engage_article/components/sidepanel/content/ArticlePlaylistBox';
import Related from 'engage_article/components/sidepanel/Related';
import { notify } from 'tui/notifications';
import { AccessManager } from 'totara_engage/index';

// GraphQL queries
import getArticle from 'engage_article/graphql/get_article';
import updateArticle from 'engage_article/graphql/update_article';
import deleteArticle from 'engage_article/graphql/delete_article';
import engageAdvancedFeatures from 'totara_engage/graphql/advanced_features';
import createReview from 'totara_reportedcontent/graphql/create_review';

export default {
  components: {
    AccessDisplay,
    AccessSetting,
    ArticlePlaylistBox,
    EngageSidePanel,
    ConfirmationModal,
    Loader,
    MediaSetting,
    Related,
    SidePanelCommentBox,
    MiniProfileCard,
    DropdownItem,
  },

  props: {
    resourceId: {
      type: [Number, String],
      required: true,
    },
    interactor: {
      type: Object,
      default: () => ({
        user_id: 0,
        can_bookmark: false,
        can_comment: false,
        can_react: false,
        can_share: false,
        show_share_button: false,
      }),
    },
  },

  apollo: {
    article: {
      query: getArticle,
      variables() {
        return {
          id: this.resourceId,
        };
      },
    },

    features: {
      query: engageAdvancedFeatures,
    },
  },

  data() {
    return {
      article: {},
      deleting: false,
      submitting: false,
      openModalFromButtonLabel: false,
      openModalFromAction: false,
      features: {},
    };
  },

  computed: {
    user() {
      if (!this.article.resource || !this.article.resource.user) {
        return {};
      }

      return this.article.resource.user;
    },

    shareButtonLabel() {
      if (this.article.owned) {
        return this.$str(
          'shareresource',
          'engage_article',
          this.article.resource.name
        );
      }

      return this.$str(
        'reshareresource',
        'engage_article',
        this.article.resource.name
      );
    },

    likeButtonLabel() {
      if (this.article.reacted) {
        return this.$str(
          'removelikearticle',
          'engage_article',
          this.article.resource.name
        );
      }

      return this.$str(
        'likearticle',
        'engage_article',
        this.article.resource.name
      );
    },

    featureRecommenders() {
      return this.features && this.features.recommenders;
    },

    isPrivateResource() {
      return AccessManager.isPrivate(this.article.resource.access);
    },
  },

  methods: {
    /**
     * Updates Access for this article
     *
     * @param {String} access The access level of the article
     * @param {Array} topics Topics that this article should be shared with
     * @param {Array} shares An array of group id's that this article is shared with
     */
    updateAccess({ access, topics, shares, timeView }) {
      this.submitting = true;
      this.$apollo
        .mutate({
          mutation: updateArticle,
          refetchAll: false,
          variables: {
            resourceid: this.resourceId,
            access: access,
            topics: topics.map(({ id }) => id),
            shares: shares,
            timeview: timeView,
          },

          update: (proxy, { data }) => {
            proxy.writeQuery({
              query: getArticle,
              variables: { id: this.resourceId },
              data,
            });
          },
        })
        .finally(() => {
          this.submitting = false;
        });
    },

    handleDelete() {
      this.deleting = true;
      this.$apollo
        .mutate({
          mutation: deleteArticle,
          variables: {
            resourceid: this.resourceId,
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
     * @param {Boolean} status
     */
    updateLikeStatus(status) {
      let { article } = apolloClient.readQuery({
        query: getArticle,
        variables: {
          id: this.resourceId,
        },
      });

      article = Object.assign({}, article);
      article.reacted = status;

      apolloClient.writeQuery({
        query: getArticle,
        variables: { id: this.resourceId },
        data: { article: article },
      });
    },

    /**
     * Report the attached resource
     * @returns {Promise<void>}
     */
    async reportResource() {
      if (this.submitting) {
        return;
      }
      this.submitting = true;
      try {
        let response = await this.$apollo
          .mutate({
            mutation: createReview,
            refetchAll: false,
            variables: {
              component: 'engage_article',
              area: '',
              item_id: this.resourceId,
              url: window.location.href,
            },
          })
          .then(response => response.data.review);

        if (response.success) {
          await notify({
            message: this.$str('reported', 'totara_reportedcontent'),
            type: 'success',
          });
        } else {
          await notify({
            message: this.$str('reported_failed', 'totara_reportedcontent'),
            type: 'error',
          });
        }
      } catch (e) {
        await notify({
          message: this.$str('error:reportresource', 'engage_article'),
          type: 'error',
        });
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>
<lang-strings>
  {
    "engage_article": [
      "delete_resource_confirm_1",
      "delete_resource_confirm_2",
      "deletewarningtitle",
      "reshareresource",
      "shareresource",
      "timelessthanfive",
      "timefivetoten",
      "timemorethanten",
      "likearticle",
      "removelikearticle",
      "reportresource",
      "error:reportresource"
    ],
    "core": [
      "delete"
    ],
    "totara_reportedcontent": [
      "reported",
      "reported_failed"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageArticleSidePanel {
  &__timeDescription {
    @include tui-font-body-small();
  }

  &__playlistBox {
    margin-top: var(--gap-8);
  }
}
</style>
