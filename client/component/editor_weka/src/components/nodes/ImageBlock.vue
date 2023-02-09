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
  @module editor_weka
-->

<template>
  <div
    class="tui-wekaImageBlock"
    :class="[
      'tui-wekaImageBlock--toolbarPosition-' + toolbarPosition,
      displaySize && [
        'tui-wekaImageBlock--displaySize',
        'tui-wekaImageBlock--displaySize-' + displaySize,
      ],
    ]"
    contenteditable="false"
  >
    <div ref="inner" class="tui-wekaImageBlock__inner">
      <template>
        <div class="tui-wekaImageBlock__imageWrap">
          <ComponentLoading v-if="loading" />
          <ResponsiveImage
            v-else
            class="tui-wekaImageBlock__image"
            :src="file.url"
            :alt="altText"
            :grow="grow"
          />
        </div>

        <div
          ref="toolbar"
          :class="[
            'tui-wekaImageBlock__toolbar',
            'tui-wekaImageBlock__toolbar--' + toolbarPosition,
          ]"
        >
          <NodeMenu v-if="!editorDisabled" context-mode="uncontained">
            <NodeMenuGroup>
              <NodeMenuActionDropdown
                position="bottom-left"
                :actions="sizeActions"
                :text="currentSizeName || $str('image_size', 'editor_weka')"
                :title="$str('image_size', 'editor_weka')"
                :aria-label="
                  currentSizeName
                    ? $str('image_size_x', 'editor_weka', currentSizeName)
                    : $str('image_size', 'editor_weka')
                "
              >
                <template v-slot:icon>
                  <ImageIcon />
                </template>
              </NodeMenuActionDropdown>
            </NodeMenuGroup>
            <NodeMenuGroup>
              <NodeMenuButton
                :text="$str('alt_text', 'editor_weka')"
                @click="editAltText"
              />
            </NodeMenuGroup>
            <NodeMenuGroup>
              <NodeMenuButton
                :text="$str('caption', 'editor_weka')"
                @click="focusCaption"
              />
            </NodeMenuGroup>
            <NodeMenuGroup>
              <NodeMenuMoreDropdown :actions="actions" />
            </NodeMenuGroup>
          </NodeMenu>
        </div>
      </template>
    </div>

    <ModalPresenter :open="altTextModalOpen" @request-close="hideAltTextModal">
      <EditImageAltTextModal :value="altText" @change="updateAltText" />
    </ModalPresenter>
  </div>
</template>

<script>
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import ImageIcon from 'tui/components/icons/Image';
import ResponsiveImage from 'tui/components/images/ResponsiveImage';
import ComponentLoading from 'tui/components/loading/ComponentLoading';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import {
  NodeMenu,
  NodeMenuActionDropdown,
  NodeMenuButton,
  NodeMenuGroup,
  NodeMenuMoreDropdown,
} from 'editor_weka/components/node_menu';
import EditImageAltTextModal from 'editor_weka/components/editing/EditImageAltTextModal';
import BaseNode from 'editor_weka/components/nodes/BaseNode';

import getDraftFile from 'editor_weka/graphql/get_draft_file';

export default {
  components: {
    ImageIcon,
    ResponsiveImage,
    ComponentLoading,
    ModalPresenter,
    NodeMenu,
    NodeMenuActionDropdown,
    NodeMenuButton,
    NodeMenuGroup,
    NodeMenuMoreDropdown,
    EditImageAltTextModal,
  },

  extends: BaseNode,

  apollo: {
    file: {
      query: getDraftFile,
      variables() {
        return {
          filename: this.filename,
          item_id: this.itemId,
        };
      },
      batch: true,
    },
  },

  data() {
    return {
      file: null,
      altTextModalOpen: false,
      toolbarPosition: 'normal',
    };
  },

  computed: {
    loading() {
      return this.file == null;
    },

    hasAttachmentNode() {
      return this.context.hasAttachmentNode();
    },

    actions() {
      return [
        this.hasAttachmentNode && {
          label: this.$str('display_as_attachment', 'editor_weka'),
          action: this.$_toAttachment,
        },
        {
          label: this.$str('remove', 'core'),
          action: this.$_removeNode,
        },
        {
          label: this.$str('download', 'core'),
          action: this.$_download,
        },
      ].filter(Boolean);
    },

    altText() {
      // Return null for showing addAltButton at the init stage. The value comes from media.js
      // Return empty string for hiding the addAltButton which represents user didn't want to put in an alt text. Plus user already acknowledged and dismissed the setting modal
      // Return the real value after getting user input. It also hides the addAltButton since it's not a null
      return this.attrs.alttext;
    },

    filename() {
      return this.attrs.filename || null;
    },

    itemId() {
      return this.context.getItemId();
    },

    displaySize() {
      return this.attrs.display_size;
    },

    grow() {
      return this.displaySize != null;
    },

    sizeOptions() {
      return [
        {
          label: this.$str('size_original', 'editor_weka'),
          size: undefined,
        },
        {
          label: this.$str('size_large', 'editor_weka'),
          size: 'large',
        },
        {
          label: this.$str('size_medium', 'editor_weka'),
          size: 'medium',
        },
        {
          label: this.$str('size_small', 'editor_weka'),
          size: 'small',
        },
      ];
    },

    sizeActions() {
      return this.sizeOptions.map(x => ({
        label: x.label,
        action: () => this.$_setSize(x.size),
      }));
    },

    currentSizeName() {
      if (this.displaySize == null) {
        return this.$str('size_original', 'editor_weka');
      }
      const currentSize = this.sizeOptions.find(
        x => x.size === this.displaySize
      );
      return currentSize ? currentSize.label : null;
    },

    toolbarDeps() {
      return { position: this.toolbarPosition, selected: this.selected };
    },
  },

  watch: {
    toolbarDeps({ position, selected }) {
      if (!this.trackedToolbar) return;
      const floating = position === 'floating';
      this.trackedToolbar.setCaptured(floating);
      if (floating) {
        this.trackedToolbar.setVisible(selected);
      }
    },
  },

  mounted() {
    this.resizeObserver = new ResizeObserver(this.$_handleInnerResize);
    this.resizeObserver.observe(this.$refs.inner);
    this.$_handleInnerResize();

    this.trackedToolbar = this.trackInExtras(this.$refs.toolbar, {
      trackedEl: this.$el,
      trackedElAnchor: 'top-start',
      positionedElAnchor: 'top-start',
    });
    if (this.toolbarPosition === 'floating') {
      this.trackedToolbar.captureNode();
    }
  },

  beforeDestroy() {
    this.resizeObserver.disconnect();
    this.trackedToolbar.destroy();
  },

  methods: {
    $_setSize(size) {
      this.context.updateImage(this.getRange, { display_size: size });
    },

    $_toAttachment() {
      if (!this.context.replaceWithAttachment) {
        // Error should be thrown here
        return;
      }

      const params = {
        filename: this.filename,
        alttext: this.altText,
        size: this.file.file_size,
      };

      this.context.replaceWithAttachment(this.getRange, params);
    },

    editAltText() {
      this.altTextModalOpen = true;
    },

    /**
     *
     * @param {String} newValue
     */
    updateAltText(newValue) {
      this.altTextModalOpen = false;

      this.context.updateImage(this.getRange, { alttext: newValue });
    },

    $_removeNode() {
      return this.context.removeNode(this.getRange);
    },

    async $_download() {
      window.open(await this.context.getDownloadUrl(this.filename));
    },

    hideAltTextModal() {
      // Save as empty string when the user didn't want to put in an alt text. It triggered once user acknowledged and dismissed the setting modal
      if (this.altText === null) {
        this.updateAltText('');
      } else {
        this.altTextModalOpen = false;
      }
    },

    focusCaption() {
      const params = {
        filename: this.filename,
        alttext: this.altText,
        size: this.file.file_size,
      };

      this.context.replaceWithFigure(this.getRange, params);
    },

    $_handleInnerResize() {
      const { inner } = this.$refs;
      this.toolbarPosition =
        inner.offsetWidth >= 300 && inner.offsetHeight >= 70
          ? 'normal'
          : 'floating';
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "loadinghelp",
    "remove",
    "download"
  ],

  "editor_weka": [
    "actions_menu_for",
    "alt_text",
    "caption",
    "display_as_attachment",
    "image_size_x",
    "image_size",
    "size_large",
    "size_medium",
    "size_original",
    "size_small"
  ]
}
</lang-strings>

<style lang="scss">
.tui-wekaImageBlock {
  $block: #{&};
  $outline-size: 2px;
  $outline-gap: 1px;
  position: relative;

  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin: 0 0 var(--paragraph-gap) 0;
  white-space: normal;

  @each $name, $size in $tui-media-named-sizes {
    &--displaySize-#{$name} &__inner {
      // IE11 does not support the responsive sizes, so specify a fixed fallback
      width: map-get($size, 'fixed');
      width: map-get($size, 'responsive');
    }
  }

  &.ProseMirror-selectednode {
    outline: none;
  }

  &.ProseMirror-selectednode &__imageWrap {
    // Set the outline for the picture only.
    outline: $outline-size solid var(--color-secondary);
    outline-offset: $outline-gap;
  }

  &__inner {
    display: flex;
    // IE11: Work around issues with empty space below ImageBlock
    // https://github.com/philipwalton/flexbugs/issues/75
    min-height: 1px;
  }

  &--toolbarPosition-normal &__inner {
    position: relative;
  }

  &--displaySize &__imageWrap {
    width: 100%;
  }

  .ProseMirror-hideselection &__image {
    user-select: none;
  }

  &__toolbar {
    position: absolute;
    display: none;

    &--normal {
      top: var(--gap-2);
      right: 0;
      left: 0;
      justify-content: center;
    }

    &--floating {
      display: flex;
      margin: var(--gap-2) 0 0 var(--gap-2);
    }
  }

  &.ProseMirror-selectednode &__toolbar {
    display: flex;
  }
}
</style>
