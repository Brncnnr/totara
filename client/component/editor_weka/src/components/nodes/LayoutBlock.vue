<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

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
    v-focus-within
    class="tui-editor_weka-layoutBlock"
    :class="{ 'tui-editor_weka-layoutBlock--cursorInside': cursorInside }"
  >
    <div
      ref="toolbar"
      class="tui-editor_weka-layoutBlock__toolbarWrap"
      contenteditable="false"
    >
      <div v-weka-node-ui class="tui-editor_weka-layoutBlock__toolbar">
        <NodeMenu context-mode="contained">
          <NodeMenuActionDropdown
            position="bottom-right"
            :actions="layoutVariantActions"
            :text="
              currentVariant
                ? currentVariant.label
                : $str('layout', 'editor_weka')
            "
            :title="$str('layout', 'editor_weka')"
            :aria-label="
              currentVariant
                ? $str('layout_x', 'editor_weka', currentVariant.label)
                : $str('layout', 'editor_weka')
            "
          >
            <template v-slot:icon>
              <component
                :is="currentVariant.iconComponent"
                v-if="currentVariant && currentVariant.iconComponent"
              />
              <LayoutIcon v-else />
            </template>
          </NodeMenuActionDropdown>
          <NodeMenuMoreDropdown :actions="actions" />
        </NodeMenu>
      </div>
    </div>
    <div ref="content" class="tui-editor_weka-layoutBlock__content" />
  </div>
</template>

<script>
import LayoutIcon from 'tui/components/icons/Layout';
import LayoutCol1 from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol1';
import LayoutCol2 from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol2';
import LayoutCol2NarrowLeft from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol2NarrowLeft';
import LayoutCol2NarrowRight from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol2NarrowRight';
import LayoutCol3 from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol3';
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import {
  NodeMenu,
  NodeMenuActionDropdown,
  NodeMenuButton,
  NodeMenuGroup,
  NodeMenuMoreDropdown,
} from 'editor_weka/components/node_menu';

import { isRtl } from 'tui/i18n';

export default {
  components: {
    LayoutIcon,
    NodeMenu,
    NodeMenuActionDropdown,
    NodeMenuButton,
    NodeMenuGroup,
    NodeMenuMoreDropdown,
  },

  extends: BaseNode,

  computed: {
    currentVariant() {
      const details = this.context.getLayoutBlockDetails(this.node);
      return this.layoutVariantOptions.find(
        variant =>
          variant.columns.length === details.columns.length &&
          variant.columns.every((col, i) =>
            this.columnMatches(details.columns[i], col)
          )
      );
    },

    layoutVariantOptions() {
      return [
        {
          label: this.$str('layout_one_column', 'editor_weka'),
          iconComponent: LayoutCol1,
          columns: [{}],
        },
        {
          label: this.$str('layout_two_column', 'editor_weka'),
          columns: [{}, {}],
          iconComponent: LayoutCol2,
        },
        {
          label: this.$str('layout_narrow_left_column', 'editor_weka'),
          columns: isRtl()
            ? [{}, { type: 'sidebar' }]
            : [{ type: 'sidebar' }, {}],
          iconComponent: LayoutCol2NarrowLeft,
        },
        {
          label: this.$str('layout_narrow_right_column', 'editor_weka'),
          columns: isRtl()
            ? [{ type: 'sidebar' }, {}]
            : [{}, { type: 'sidebar' }],
          iconComponent: LayoutCol2NarrowRight,
        },
        {
          label: this.$str('layout_three_column', 'editor_weka'),
          columns: [{}, {}, {}],
          iconComponent: LayoutCol3,
        },
      ];
    },

    layoutVariantActions() {
      return this.layoutVariantOptions.map(x => ({
        label: x.label,
        iconComponent: x.iconComponent,
        action: () => this.updateLayout({ columns: x.columns }),
      }));
    },

    actions() {
      return [
        {
          label: this.$str('remove', 'core'),
          action: () => this.deleteLayout(),
        },
      ];
    },

    toolbarVisible() {
      return !this.editorDisabled && this.cursorInside;
    },
  },

  watch: {
    toolbarVisible(value) {
      this.trackedToolbar.setVisible(value);
    },
  },

  mounted() {
    this.trackedToolbar = this.trackInExtras(this.$refs.toolbar, {
      trackedEl: this.$el,
      trackedElAnchor: 'top-end',
      positionedElAnchor: 'bottom-end',
    });
    this.trackedToolbar.captureNode();
  },

  beforeDestroy() {
    this.trackedToolbar.destroy();
  },

  methods: {
    updateLayout(update) {
      this.context.updateLayout(this.getRange, update);
    },

    deleteLayout() {
      this.context.deleteLayout(this.getRange);
    },

    columnMatches(column, test) {
      return column.type == test.type;
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "remove"
  ],
  "editor_weka": [
    "layout",
    "layout_narrow_left_column",
    "layout_narrow_right_column",
    "layout_one_column",
    "layout_settings",
    "layout_three_column",
    "layout_two_column",
    "layout_x"
  ]
}
</lang-strings>

<style lang="scss">
.tui-editor_weka-layoutBlock {
  margin-bottom: var(--paragraph-gap);
  white-space: normal;

  &__content {
    display: flex;
    flex-wrap: wrap;
    @include tui-weka-whitespace();
  }

  &__toolbarWrap {
    position: absolute;
    z-index: var(--zindex-floating-ui);
  }

  &__toolbar {
    margin: calc(var(--gap-2) * -1) var(--gap-2);
  }
}
</style>
