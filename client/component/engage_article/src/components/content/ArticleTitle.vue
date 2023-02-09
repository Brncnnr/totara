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
  <div class="tui-engageArticleTitle">
    <div class="tui-engageArticleTitle__head">
      <InlineEditing
        v-show="!editing"
        :button-aria-label="$str('editarticletitle', 'engage_article', title)"
        :update-able="updateAble"
        :full-width="true"
        @click="editing = true"
      >
        <h3 slot="content" class="tui-engageArticleTitle__title">
          {{ title }}
        </h3>
      </InlineEditing>
      <EditArticleTitleForm
        v-if="editing"
        :title="title"
        :submitting="submitting"
        @cancel="editing = false"
        @submit="updateTitle"
      />

      <BookmarkButton
        v-if="showBookmarkButton"
        :primary="false"
        :circle="true"
        :bookmarked="bookmarked"
        size="300"
        @click="$emit('bookmark', $event)"
      />
    </div>
  </div>
</template>

<script>
import InlineEditing from 'totara_engage/components/form/InlineEditing';
import BookmarkButton from 'totara_engage/components/buttons/BookmarkButton';
import EditArticleTitleForm from 'engage_article/components/form/EditArticleTitleForm';

// GraphQL queries
import updateArticle from 'engage_article/graphql/update_article';
import getArticle from 'engage_article/graphql/get_article';

export default {
  components: {
    EditArticleTitleForm,
    InlineEditing,
    BookmarkButton,
  },

  props: {
    title: {
      type: String,
      required: true,
    },

    updateAble: {
      type: Boolean,
      required: true,
    },

    bookmarked: {
      type: Boolean,
      required: true,
    },

    resourceId: {
      type: [Number, String],
      required: true,
    },

    owned: {
      type: Boolean,
      required: true,
    },

    showBookmarkButton: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      editing: false,
      submitting: false,
    };
  },

  watch: {
    editing: {
      handler() {
        if (this.editing) {
          window.addEventListener('beforeunload', this.$_unloadHandler);
        } else {
          window.removeEventListener('beforeunload', this.$_unloadHandler);
        }
      },
    },
  },

  methods: {
    $_unloadHandler(event) {
      // Cancel the event as stated by the standard.
      event.preventDefault();

      // For older browsers that still show custom message.
      const discardUnsavedChanges = this.$str(
        'unsaved_changes_warning',
        'totara_engage'
      );

      // Chrome requires returnValue to be set.
      event.returnValue = discardUnsavedChanges;

      return discardUnsavedChanges;
    },

    /**
     *
     * @param {String} title
     */
    updateTitle(title) {
      this.submitting = true;

      this.$apollo
        .mutate({
          mutation: updateArticle,
          refetchAll: false,
          variables: {
            resourceid: this.resourceId,
            name: title,
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
        })
        .finally(() => {
          this.submitting = false;
          this.editing = false;
        });
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "unsaved_changes_warning"
    ],
    "engage_article": [
      "editarticletitle"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageArticleTitle {
  display: flex;
  flex-direction: column;

  &__head {
    display: flex;
    align-items: flex-start;
  }

  &__title {
    @include tui-font-heading-medium;
    width: 93%;
    margin: 0;
    margin-top: calc(var(--gap-2) / -1);

    @include tui-wordbreak--hyphens;
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-engageArticleTitle {
    &__title {
      @include tui-font-heading-large;
    }
  }
}
</style>
