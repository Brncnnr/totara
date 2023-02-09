<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module weka_simple_multi_lang
-->
<template>
  <div class="tui-wekaMultiLangBlockCollection">
    <div ref="content" class="tui-wekaMultiLangBlockCollection__content">
      <!-- This is where all the multi lang block will be located -->
    </div>

    <div
      class="tui-wekaMultiLangBlockCollection__actions"
      :class="{
        'tui-wekaMultiLangBlockCollection__actions--spacing': isActionSpacingRequired,
      }"
    >
      <ButtonIcon
        v-if="!editorDisabled"
        :aria-label="$str('add_new', 'weka_simple_multi_lang')"
        :styleclass="{
          transparentNoPadding: true,
        }"
        @click="insertNewLangBlock"
      >
        <Add :size="300" />
      </ButtonIcon>
    </div>
  </div>
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Add from 'tui/components/icons/Add';

export default {
  components: {
    ButtonIcon,
    Add,
  },

  extends: BaseNode,

  computed: {
    isActionSpacingRequired() {
      return this.node.content.content.length > 2;
    },
  },

  methods: {
    insertNewLangBlock() {
      this.context.insertNewLangBlock(this.getRange);
    },
  },
};
</script>

<lang-strings>
{
  "weka_simple_multi_lang": [
    "add_new",
    "remove_collection_block"
  ]
}
</lang-strings>

<style lang="scss">
.tui-wekaMultiLangBlockCollection {
  display: flex;
  flex-direction: column;
  max-width: 100%;
  white-space: normal;

  &__content {
    .tui-wekaMultiLangBlock {
      &:first-child {
        .tui-wekaMultiLangBlock__wrapper {
          border-top-left-radius: var(--border-radius-normal);
          border-top-right-radius: var(--border-radius-normal);
        }
      }

      &:not(:first-child) {
        .tui-wekaMultiLangBlock__wrapper {
          border-top: 0;
        }
      }

      &:last-child {
        .tui-wekaMultiLangBlock__wrapper {
          border-bottom-right-radius: var(--border-radius-normal);
          border-bottom-left-radius: var(--border-radius-normal);
        }
      }
    }
  }

  &__actions {
    display: flex;
    justify-content: flex-end;

    &--spacing {
      padding-right: var(--gap-5);
    }
  }
}
</style>
