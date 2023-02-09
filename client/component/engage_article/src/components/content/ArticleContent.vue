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
  <div class="tui-engageArticleContent" @keydown.esc="disableEditing">
    <div
      v-show="!editing"
      slot="content"
      ref="content"
      class="tui-engageArticleContent__content"
      v-html="content"
    />
    <EditArticleForm
      v-if="editing"
      :resource-id="resourceId"
      :submitting="submitting"
      @submit="updateArticle"
      @cancel="disableEditing"
    />
  </div>
</template>

<script>
import EditArticleForm from 'engage_article/components/form/EditArticleContentForm';
import tui from 'tui/tui';

// GraphQL queries
import updateArticle from 'engage_article/graphql/update_article';
import getArticle from 'engage_article/graphql/get_article';

export default {
  components: {
    EditArticleForm,
  },

  props: {
    /**
     * For fetching the draft content of article.
     */
    resourceId: {
      type: [String, Number],
      required: true,
    },

    editing: {
      type: Boolean,
      required: true,
    },

    title: {
      type: String,
      required: true,
    },

    content: {
      type: String,
      required: true,
    },

    updateAble: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      submitting: false,
    };
  },
  mounted() {
    this.$_scan();
  },

  updated() {
    this.$_scan();
  },

  methods: {
    $_scan() {
      this.$nextTick().then(() => {
        let content = this.$refs.content;
        if (!content) {
          return;
        }

        tui.scan(content);
      });
    },

    disableEditing() {
      this.$emit('disable-editing');
    },

    /**
     *
     * @param {String} content
     * @param {Number} format
     */
    async updateArticle({ content, format, itemId }) {
      this.submitting = true;

      try {
        await this.$apollo.mutate({
          mutation: updateArticle,
          variables: {
            resourceid: this.resourceId,
            content: content,
            format: format,
            draft_id: itemId,
          },

          /**
           *
           * @param {DataProxy} proxy
           * @param {Object} data
           */
          updateQuery: (proxy, data) => {
            proxy.writeQuery({
              query: getArticle,
              variables: {
                resourceid: this.resourceId,
              },

              data: data,
            });
          },
        });
        this.disableEditing();
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
      "editarticlecontent"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageArticleContent {
  &__content {
    flex-grow: 1;
    width: 100%;
    .tui-rendered > p {
      @include tui-wordbreak--hard;
    }
  }
}
</style>
