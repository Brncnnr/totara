<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module samples
-->

<template>
  <div class="tui-sample-weka">
    <Weka
      v-if="draftId && showEditor"
      v-model="content"
      :disabled="editorDisabled"
      :sticky-toolbar="editorStickyToolbar"
      :usage-identifier="{
        component: 'editor_weka',
        area: 'default',
      }"
      variant="standard"
      :file-item-id="draftId"
      :extra-extensions="[
        {
          name: 'weka_simple_multi_lang_extension',
        },
        {
          name: 'weka_notification_placeholder_extension',
          options: {
            resolver_class_name:
              'totara_comment\\totara_notification\\resolver\\comment_created',
          },
        },
      ]"
      aria-label="Sample Weka editor"
    />
    <hr />
    <Button text="Reset" @click="reset" />
    <Button text="Toggle editor" @click="showEditor = !showEditor" />
    <Button text="Apply formatters" @click="applyFormatter" />
    <Button
      :text="editorDisableText"
      @click="editorDisabled = !editorDisabled"
    />
    <Button
      :text="editorStickyToolbarText"
      @click="editorStickyToolbar = !editorStickyToolbar"
    />
    <br />
    <div class="tui-sample-weka__json" v-text="json" />
  </div>
</template>

<script>
import Weka from 'editor_weka/components/Weka';
import Button from 'tui/components/buttons/Button';

// GraphQL queries
import getFileUnusedDraftId from 'core/graphql/file_unused_draft_item_id';

export default {
  components: {
    Weka,
    Button,
  },

  data() {
    return {
      showEditor: true,
      content: null,
      json: '',
      draftId: null,
      editorDisabled: false,
      editorStickyToolbar: true,
    };
  },

  computed: {
    editorDisableText() {
      return (this.editorDisabled ? 'Enable' : 'Disable') + ' editor';
    },
    editorStickyToolbarText() {
      return (
        (!this.editorStickyToolbar ? 'Enable' : 'Disable') + ' sticky toolbar'
      );
    },
  },

  watch: {
    content(value) {
      this.json = value && value.getDoc(false);
    },
  },

  async mounted() {
    const {
      data: { item_id },
    } = await this.$apollo.mutate({
      mutation: getFileUnusedDraftId,
    });
    this.draftId = item_id;
  },

  methods: {
    reset() {
      this.content = null;
    },

    applyFormatter() {
      if (!this.content) {
        return;
      }

      this.content.getDoc(true);
    },
  },
};
</script>

<style lang="scss">
.tui-sample-weka {
  &__json {
    white-space: pre;
  }
}
</style>
