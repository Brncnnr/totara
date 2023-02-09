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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module editor_weka
-->

<template>
  <div
    class="tui-wekaLinkMedia"
    :class="{
      'tui-wekaLinkMedia--intrinsic-width': !attrs.loading && !iframeUrl,
    }"
    :data-url="attrs.url"
  >
    <div class="tui-wekaLinkMedia__inner">
      <div v-if="attrs.loading" class="tui-wekaLinkMedia__loading">
        <Loading :alt="$str('loading', 'core')" />
      </div>
      <div v-else-if="iframeUrl" class="tui-wekaLinkMedia__embed">
        <ResponsiveEmbedIframe
          :src="iframeUrl"
          :resolution="attrs.resolution"
          :title="attrs.title || attrs.url"
        />
      </div>
      <div v-else-if="pluginKey == 'audio'">
        <audio controls :src="attrs.url" />
      </div>
      <div v-else-if="pluginKey == 'image'">
        <ResponsiveImage :src="attrs.url" />
      </div>
      <div v-else>
        <a :href="attrs.url">{{ attrs.url }}</a>
      </div>
      <NodeBar
        v-if="!editorDisabled"
        :actions="actions"
        :aria-label="$str('actions_menu_for', 'editor_weka', summaryText)"
      />
    </div>
  </div>
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import NodeBar from 'editor_weka/components/toolbar/NodeBar';
import ResponsiveEmbedIframe from 'tui/components/embeds/ResponsiveEmbedIframe';
import ResponsiveImage from 'tui/components/images/ResponsiveImage';
import Loading from 'tui/components/icons/Loading';

export default {
  components: {
    NodeBar,
    ResponsiveEmbedIframe,
    ResponsiveImage,
    Loading,
  },

  extends: BaseNode,

  computed: {
    pluginMatch() {
      return (
        this.context.urlPlugin({ type: 'media', url: this.attrs.url }) || {}
      );
    },

    pluginKey() {
      return this.pluginMatch.plugin && this.pluginMatch.plugin.key;
    },

    pluginName() {
      return this.pluginMatch.plugin && this.pluginMatch.plugin.name;
    },

    details() {
      return this.pluginMatch.details || {};
    },

    actions() {
      return [
        {
          label: this.$str('go_to_link_label', 'editor_weka'),
          action: () => this.open(),
        },
        { label: this.$str('edit', 'core'), action: () => this.edit() },
        {
          label: this.$str('display_as_text', 'editor_weka'),
          action: () => this.toLink(),
        },
        {
          label: this.$str('remove', 'core'),
          action: () => this.$emit('remove'),
        },
      ];
    },

    iframeUrl() {
      const { details } = this;
      let url = null;

      switch (this.pluginKey) {
        case 'youtube':
          url = 'https://www.youtube.com/embed/' + details.id + '?rel=0';
          break;
        case 'vimeo':
          url = 'https://player.vimeo.com/video/' + details.id + '?portrait=0';
          break;
        case 'vimeo-private':
          url =
            'https://player.vimeo.com/video/' +
            details.id +
            '?h=' +
            details.privateString +
            '&portrait=0';
          break;
      }

      return url;
    },

    summaryText() {
      return this.pluginName + ' - ' + (this.attrs.title || this.attrs.url);
    },
  },

  methods: {
    open() {
      window.open(this.attrs.url);
    },

    edit() {
      this.context.editCard(this.getRange);
    },

    toLink() {
      const url = this.attrs.url;
      this.context.replaceWithTextLink(this.getRange, { url });
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "edit",
    "remove",
    "loading"
  ],
  "editor_weka": [
    "actions_menu_for",
    "display_as_text",
    "go_to_link_label"
  ]
}
</lang-strings>

<style lang="scss">
.tui-wekaLinkMedia {
  display: flex;
  flex-direction: column;
  margin-bottom: var(--paragraph-gap);
  white-space: normal;

  &--intrinsic-width {
    align-items: flex-start;
  }

  &.ProseMirror-selectednode {
    outline: none;
  }
  &.ProseMirror-selectednode > &__inner {
    outline: var(--border-width-normal) solid var(--color-secondary);
  }

  &__inner {
    max-width: 700px;

    & > .tui-wekaNodeBar {
      margin-top: var(--gap-2);
    }
  }

  &__loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--gap-6);
    color: var(--color-neutral-6);
  }
}
</style>
