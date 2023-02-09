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
  <div ref="tree" class="tui-tree">
    <TreeNode
      v-for="(tree, index) in mutableTreeData"
      :key="tree.id"
      :node-id="tree.id"
      :children="tree.children"
      :content="tree.content"
      :depth="1"
      :depth-limit="depthLimit"
      :header-level="headerLevel"
      :label="tree.label"
      :label-type="labelType"
      :link-url="tree.linkUrl"
      :open-list="value"
      :position="index + 1"
      :separator="separator"
      :siblings="treeData.length"
      :top-level="true"
      :no-padding="noPadding"
      @expanded="updateExpanded"
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

      <template v-slot:side="{ sideContent }">
        <slot name="side" :sideContent="sideContent" />
      </template>

      <template
        v-if="$scopedSlots['custom-label']"
        v-slot:custom-label="{ label, linkUrl, topLevel, hasChildren }"
      >
        <slot
          name="custom-label"
          :label="label"
          :link-url="linkUrl"
          :top-level="topLevel"
          :has-children="hasChildren"
        />
      </template>
    </TreeNode>
  </div>
</template>

<script>
import { getTabbableElements } from 'tui/dom/focus';
import { isRtl } from 'tui/i18n';
import TreeNode from 'tui/components/tree/TreeNode';

export default {
  components: {
    TreeNode,
  },

  props: {
    // Limit the depth of nodes in the tree
    depthLimit: Number,
    // Number for header tag level
    headerLevel: Number,
    // String (null), link or button for node label
    labelType: String,
    // Visually display a separator between top level nodes
    separator: Boolean,
    /*
    Tree data structure
      [{
        // String, Must be unique within the tree
        id: 'continents',
        // String, displayed label
        label: 'Continents',
        // Data which will be provided to the slot (can any type, e.g. array or string, not just object)
        content: {},
        // Node data, must follow same structure as parent
        children: [],
      }]
    */
    treeData: Array,
    // List of expanded node ID's
    value: {
      required: true,
      type: Array,
    },
    noPadding: Boolean,
  },

  computed: {
    /**
     * Get a mutable version of the tree data.
     */
    mutableTreeData() {
      if (this.treeData != null && !Object.isExtensible(this.treeData)) {
        // This is necessary in order to be able to use tree data that has come from Apollo.
        // Apollo returns non-extensible data, but we need to be able to modify the structure when rendering the tree.
        return JSON.parse(JSON.stringify(this.treeData));
      }
      return this.treeData;
    },
  },

  mounted() {
    document.addEventListener('keydown', this.$_keyPress);
  },

  beforeDestroy() {
    document.removeEventListener('keydown', this.$_keyPress);
  },

  methods: {
    /**
     * expand or collapse provided node
     *
     * @param {Array} node
     */
    updateExpanded(node) {
      let openList = this.value;
      if (node.expanded) {
        // Add to list if not already included
        if (!openList.includes(node.key)) {
          openList.push(node.key);
        }
      } else {
        // Remove from list
        openList = openList.filter(x => x !== node.key);
      }

      this.$emit('input', openList);
    },

    /**
     * Get list of tabbable chevrons
     *
     *  @return {Array}
     */
    getTabbableChevrons() {
      let tabbable = getTabbableElements(this.$refs.tree);
      tabbable = tabbable.filter(x => x.dataset.treeTrigger);
      return tabbable;
    },

    /**
     * Keypress event that is bound to the document.
     * Listening for a arrow keypresses while focused
     * on a chevron button
     */
    $_keyPress(event) {
      const chevronList = this.getTabbableChevrons();

      // Check if focus is on a chevron
      if (!chevronList.includes(document.activeElement)) {
        return;
      }

      const chevronListCount = chevronList.length;
      const screenDirectionRTL = isRtl();
      let activeChevronIndex = chevronList.indexOf(document.activeElement);
      const expanded = JSON.parse(
        chevronList[activeChevronIndex].getAttribute('aria-expanded')
      );

      switch (event.key) {
        case 'ArrowDown':
        case 'Down':
          event.preventDefault();
          // Set focus to next chevron if there is one
          if (activeChevronIndex === chevronListCount - 1) break;
          activeChevronIndex += 1;
          if (chevronListCount > 0) {
            chevronList[activeChevronIndex].focus();
          }

          break;
        case 'ArrowUp':
        case 'Up':
          event.preventDefault();
          // Set focus to previous chevron if there is one
          if (activeChevronIndex === 0) break;
          activeChevronIndex -= 1;
          chevronList[activeChevronIndex].focus();
          break;

        case 'ArrowLeft':
        case 'Left':
          event.preventDefault();
          if (!screenDirectionRTL && expanded) {
            chevronList[activeChevronIndex].click();
          } else if (screenDirectionRTL && !expanded) {
            chevronList[activeChevronIndex].click();
          }

          break;
        case 'ArrowRight':
        case 'Right':
          event.preventDefault();
          if (!screenDirectionRTL && !expanded) {
            chevronList[activeChevronIndex].click();
          } else if (screenDirectionRTL && expanded) {
            chevronList[activeChevronIndex].click();
          }

          break;
      }
    },
  },
};
</script>

<style lang="scss">
.tui-tree {
  list-style: none;
}
</style>
