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
  <Form class="tui-engageEditArticleContentForm">
    <!-- Loader is for preventing user from typing when the editor is being initialised -->
    <Loader :fullpage="true" :loading="!editorMounted" />
    <UnsavedChangesWarning
      v-if="!content.isEmpty && !submitting"
      :value="content"
    />
    <Weka
      v-if="!$apollo.loading"
      v-model="content"
      :usage-identifier="{
        component: 'engage_article',
        area: 'content',
        instanceId: resourceId,
      }"
      variant="full"
      :extra-extensions="['mention', 'hashtag']"
      :file-item-id="draft.file_item_id"
      class="tui-engageEditArticleContentForm__editor"
      @ready="editorMounted = true"
    />

    <DoneCancelGroup
      :loading="submitting"
      :disabled="content.isEmpty || submitting"
      class="tui-engageEditArticleContentForm__buttons"
      @done="submit"
      @cancel="$emit('cancel')"
    />
  </Form>
</template>

<script>
import Form from 'tui/components/form/Form';
import Loader from 'tui/components/loading/Loader';

import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';

import DoneCancelGroup from 'totara_engage/components/buttons/DoneCancelGroup';
import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';

// GraphQL queries
import getDraftItem from 'engage_article/graphql/draft_item';

export default {
  components: {
    DoneCancelGroup,
    Form,
    Loader,
    UnsavedChangesWarning,
    Weka,
  },

  props: {
    resourceId: {
      type: [String, Number],
      required: true,
    },

    submitting: Boolean,
  },

  apollo: {
    draft: {
      query: getDraftItem,
      fetchPolicy: 'network-only',

      variables() {
        return {
          resourceid: this.resourceId,
        };
      },

      result({
        data: {
          draft: { content },
        },
      }) {
        if (content) {
          this.content = WekaValue.fromDoc(JSON.parse(content));
        }
      },
    },
  },

  data() {
    return {
      draft: {},
      editorMounted: false,
      content: WekaValue.empty(),
    };
  },

  methods: {
    submit() {
      const params = {
        resourceId: this.resourceId,
        content: JSON.stringify(this.content.getDoc()),

        // This seems to be redundant, but lets keep it here, who know in the future, we
        format: this.draft.format,
        itemId: this.content.fileStorageItemId,
      };

      this.$emit('submit', params);
    },
  },
};
</script>

<style lang="scss">
.tui-engageEditArticleContentForm {
  &__buttons {
    margin-bottom: var(--gap-12);
  }
}
</style>
