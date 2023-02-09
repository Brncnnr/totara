<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-treeNode"
    :class="{
      'tui-treeNode--top': topLevel,
      'tui-treeNode--separator': separator && topLevel,
      'tui-treeNode--noPadding': noPadding,
    }"
  >
    <div
      class="tui-treeNode__trigger"
      :class="{
        'tui-treeNode__trigger--top': topLevel,
        'tui-treeNode__trigger--spacing': !hasChildren,
      }"
    >
      <!-- Node expand trigger -->
      <ButtonIcon
        v-if="hasContent || hasChildren"
        ref="trigger"
        class="tui-treeNode__trigger-btn"
        :styleclass="{ transparent: true }"
        :aria-expanded="open.toString()"
        :aria-controls="regionId"
        :aria-describedby="regionDescriptionId"
        :aria-label="label"
        data-tree-trigger="true"
        @click="toggleExpand()"
      >
        <CollapseIcon v-if="open" />
        <ExpandIcon v-else />
      </ButtonIcon>
    </div>
    <div class="tui-treeNode__content">
      <!-- Node bar -->
      <div class="tui-treeNode__bar">
        <!-- Node label (Can be text, button or link) -->

        <template v-if="$scopedSlots['custom-label']">
          <slot
            name="custom-label"
            :label="label"
            :link-url="linkUrl"
            :top-level="topLevel"
            :has-children="hasChildren"
          />
        </template>

        <template v-else>
          <Button
            v-if="labelType === 'button'"
            :id="nodeLabelId"
            class="tui-treeNode__bar-btn"
            :styleclass="{ primary: true, transparent: true }"
            :text="label"
            @click="$emit('label-click', nodeId)"
          />

          <a
            v-else-if="labelType === 'link' && linkUrl"
            :id="nodeLabelId"
            class="tui-treeNode__bar-link"
            :href="linkUrl"
          >
            {{ label }}
          </a>

          <component
            :is="headerTag"
            v-else
            :id="nodeLabelId"
            class="tui-treeNode__bar-label"
          >
            {{ label }}
          </component>
        </template>

        <div class="tui-treeNode__bar-side">
          <slot v-if="sideContent" name="side" :sideContent="sideContent" />
        </div>
      </div>

      <span :id="regionDescriptionId" class="sr-only">
        {{ regionAccessibleLabel }}
      </span>
      <div v-show="open" :id="regionId" role="region" :aria-label="label">
        <!-- Sub-nodes -->
        <template v-if="depth !== depthLimit">
          <div
            v-for="(child, index) in children"
            :key="child.id"
            class="tui-treeNode__child"
            :class="{ 'tui-treeNode__child--noPadding': noPadding }"
          >
            <TreeNode
              :node-id="child.id"
              :children="child.children"
              :content="child.content"
              :depth="depth + 1"
              :depth-limit="depthLimit"
              :header-level="headerLevel"
              :label="child.label"
              :label-type="labelType"
              :link-url="child.linkUrl"
              :no-padding="noPadding"
              :open-list="openList"
              :position="index + 1"
              :siblings="children.length"
              :side-content="child.sideContent"
              @expanded="$emit('expanded', $event)"
              @label-click="$emit('label-click', $event)"
            >
              <template v-slot:content="{ content, label, labelledBy }">
                <slot
                  name="content"
                  :content="content"
                  :label="label"
                  :labelledBy="labelledBy"
                />
              </template>

              <template
                v-if="$scopedSlots['custom-label']"
                v-slot:custom-label="{ label, linkUrl, hasChildren }"
              >
                <slot
                  name="custom-label"
                  :label="label"
                  :link-url="linkUrl"
                  :has-children="hasChildren"
                />
              </template>

              <template v-slot:side="{ sideContent }">
                <slot name="side" :sideContent="sideContent" />
              </template>
            </TreeNode>
          </div>
        </template>

        <!-- Node leaves -->
        <div class="tui-treeNode__leaf">
          <slot
            name="content"
            :content="getOutputContent()"
            :label="label"
            :labelledBy="nodeLabelId"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import CollapseIcon from 'tui/components/icons/Collapse';
import ExpandIcon from 'tui/components/icons/Expand';

export default {
  name: 'TreeNode',

  components: {
    Button,
    ButtonIcon,
    CollapseIcon,
    ExpandIcon,
  },

  props: {
    nodeId: String,
    children: Array,
    content: [Array, Boolean, Number, Object, String],
    depth: Number,
    depthLimit: Number,
    headerLevel: {
      type: Number,
      default: 3,
      validator: level => [1, 2, 3, 4, 5, 6].includes(level),
    },
    label: String,
    labelType: String,
    linkUrl: String,
    openList: Array,
    position: Number,
    separator: Boolean,
    siblings: Number,
    sideContent: Object,
    topLevel: Boolean,
    noPadding: Boolean,
  },

  data() {
    return {
      open: false,
    };
  },

  computed: {
    /**
     * Provide label ID for accessibility tags
     *
     * @return {String}
     */
    nodeLabelId() {
      return this.$id('label');
    },

    /**
     * Provide the correct header tag for node label
     *
     */
    headerTag() {
      return 'h' + this.headerLevel;
    },

    /**
     * Check if there is custom content
     *
     * @return {Boolean}
     */
    hasContent() {
      return this.content != null;
    },

    /**
     * Check if there is at least one child tree node.
     *
     * @return {Boolean}
     */
    hasChildren() {
      return this.children != null && this.children.length > 0;
    },

    /**
     * Provide region description ID for accessibility tags
     *
     * @return {String}
     */
    regionDescriptionId() {
      return this.$id('regionDesc');
    },

    /**
     * Provide region ID for accessibility tags
     *
     * @return {String}
     */
    regionId() {
      return this.$id('region');
    },

    /**
     * Provide accessibility label for region
     *
     * @return {String}
     */
    regionAccessibleLabel() {
      return this.$str('a11y_tree_region_summary', 'totara_core', {
        depth: this.depth,
        label: this.label,
        position: this.position,
        siblings: this.siblings,
      });
    },
  },

  watch: {
    /**
     * Check if this node should be expanded
     *
     */
    openList(list) {
      this.setOpenState(list);
    },
  },

  mounted() {
    this.setOpenState(this.openList);
  },

  methods: {
    /**
     * set the open (expanded) state of the node
     *
     * @param {Array} list
     */
    setOpenState(list) {
      this.open = list.includes(this.nodeId);
    },

    /**
     * Propagate expanded value change to parent
     *
     */
    toggleExpand() {
      this.$emit('expanded', {
        key: this.nodeId,
        expanded: !this.open,
      });
    },

    /**
     * Get content for the slot
     *
     * @return {Object}
     */
    getOutputContent() {
      let content = this.content;

      // If no content & no depth limit
      if (!this.hasContent && !this.depthLimit) {
        return null;
      }

      content.subContent = [];
      // Get content data from removed children
      if (this.depth === this.depthLimit && this.children.length) {
        this.children.forEach((subNode, index) => {
          content.subContent[index] = this.getSubContent(subNode);
        });
      }

      return content;
    },

    /**
     * Get the content from nodes removed by the depth limit
     *
     * @param {Object} node
     * @return {Object}
     */
    getSubContent(node) {
      if (!node.children.length) {
        return node.content;
      }

      node.content.subContent = [];
      node.children.forEach((subNode, index) => {
        node.content.subContent[index] = this.getSubContent(subNode);
      });
      return node.content;
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "a11y_tree_region_summary"
  ]
}
</lang-strings>

<style lang="scss">
.tui-treeNode {
  display: flex;
  align-items: baseline;
  width: 100%;
  padding: 1px 0;

  &--noPadding {
    padding: 0;
  }

  &--top {
    position: relative;
    padding: var(--gap-2) 0;
  }

  &--separator {
    &:after {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      border-bottom: var(--border-width-thin) solid var(--color-neutral-5);
      content: '';
    }
  }

  &__content {
    width: 100%;
    min-width: 0;
    padding-left: calc(var(--gap-1) / 2);
  }

  &__trigger {
    position: relative;
    top: calc(var(--gap-1) / 2);
    z-index: 1;
    height: 1em;

    &-btn {
      left: calc(var(--gap-1) / 2 * -1);
      padding: 0 var(--gap-1);
    }

    &--top {
      .tui-treeNode__trigger-btn {
        left: 0;
      }
    }

    &--spacing {
      padding-left: var(--gap-6);
    }
  }

  &__bar {
    display: flex;
    width: 100%;
    min-width: 0;

    & > * + * {
      margin-left: var(--gap-2);
    }

    &-btn {
      flex-grow: 0;
      flex-shrink: 1;
      line-height: 1.2;
      text-align: left;
      -ms-word-break: break-all;
      word-break: break-word;
    }

    &-label,
    &-link {
      @include tui-font-heading-label();
      margin: 0;
      -ms-word-break: break-all;
      word-break: break-word;
      hyphens: none;
    }

    &-side {
      flex-shrink: 0;
      margin-left: auto;
    }
  }

  &__child {
    margin: 0;
    padding-top: var(--gap-3);
    list-style: none;

    &--noPadding {
      padding-top: 0;
    }
  }
}
</style>
