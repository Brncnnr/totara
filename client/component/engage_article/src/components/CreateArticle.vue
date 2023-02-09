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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module engage_article
-->

<template>
  <div class="tui-engageCreateArticle">
    <ArticleForm
      v-show="stage === 0"
      :article-name="article.name"
      :article-content="article.content"
      @next="next"
      @cancel="$emit('cancel')"
      @unsaved-changes="$emit('unsaved-changes')"
    />
    <AccessForm
      v-show="stage === 1"
      item-id="0"
      component="engage_article"
      :show-back="true"
      :submitting="submitting"
      :selected-access="containerValues.access || defaultAccess"
      :private-disabled="privateDisabled"
      :restricted-disabled="restrictedDisabled"
      :container="container"
      :enable-time-view="true"
      :can-share="canShare"
      @done="done"
      @back="back"
      @cancel="$emit('cancel')"
    />
  </div>
</template>

<script>
import ArticleForm from 'engage_article/components/form/ArticleForm';
import AccessForm from 'totara_engage/components/form/AccessForm';
import { notify } from 'tui/notifications';

// Graphql queries
import createArticle from 'engage_article/graphql/create_article';
import { AccessManager } from 'totara_engage/index';

// Mixins
import ContainerMixin from 'totara_engage/mixins/container_mixin';

export default {
  components: {
    ArticleForm,
    AccessForm,
  },

  mixins: [ContainerMixin],

  props: {
    showNotification: {
      type: Boolean,
    },
    canShare: Boolean,
  },

  data() {
    return {
      stage: 0,
      maxStage: 1,
      article: {
        name: '',
        content: null,
        itemId: null,
      },
      submitting: false,
      defaultAccess: 'PRIVATE',
    };
  },

  computed: {
    privateDisabled() {
      return this.containerValues.access
        ? !AccessManager.isPrivate(this.containerValues.access)
        : false;
    },
    restrictedDisabled() {
      return this.containerValues.access
        ? AccessManager.isPublic(this.containerValues.access)
        : false;
    },
  },

  methods: {
    /**
     * @param {String} content
     * @param {String} name
     * @param {String|Number} itemId
     */
    next({ content, name, itemId }) {
      if (this.stage < this.maxStage) {
        this.stage += 1;
      }

      this.article.content = content;
      this.article.name = name;
      this.article.itemId = itemId;

      this.$emit('change-title', this.stage);
    },
    back() {
      if (this.stage > 0) {
        this.stage -= 1;
      }

      this.$emit('change-title', this.stage);
    },

    /**
     * @param {String} access
     * @param {Array} topics
     * @param {Array} shares
     * @param {String|null} timeView
     */
    done({ access, topics, timeView, shares }) {
      this.submitting = true;
      let params = {
        content: this.article.content,
        name: this.article.name,
        access: access,
        topics: topics.map(topic => topic.id),
        shares: shares,
        draft_id: this.article.itemId,
      };

      if (timeView) {
        params.timeview = timeView;
      }

      this.$apollo
        .mutate({
          mutation: createArticle,
          refetchQueries: [
            'totara_engage_contribution_cards',
            'container_workspace_contribution_cards',
            'container_workspace_shared_cards',
          ],
          variables: params,
          update: (
            cache,
            {
              data: {
                article: {
                  resource: { id },
                },
              },
            }
          ) => {
            this.$emit('done', { resourceId: id });
          },
        })
        .then(({ data: { article } }) => {
          if (article && this.showNotification && !this.container) {
            notify({
              message: this.$str('created', 'engage_article'),
              type: 'success',
            });
          }
          this.$emit('cancel');
        })

        .finally(() => {
          this.submitting = false;
        });
    },
  },
};
</script>

<lang-strings>
{
  "engage_article": [
    "created"
  ]
}
</lang-strings>

<style lang="scss">
.tui-engageCreateArticle {
  display: flex;
  flex: 1;
  flex-direction: column;

  width: 100%;
  height: 100%;
}
</style>
