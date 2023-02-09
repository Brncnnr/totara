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
  <div class="tui-wekaLinkBlock" :data-url="attrs.url">
    <Dropdown :disabled="editorDisabled">
      <template v-slot:trigger="{ toggle, isOpen }">
        <div @click.capture.prevent="toggle">
          <LinkBlockInner :attrs="attrs" />
        </div>
        <div class="tui-wekaLinkBlock__btn-wrapper">
          <ButtonIcon
            v-if="!editorDisabled"
            :styleclass="{ small: true, transparent: true }"
            class="tui-wekaLinkBlock__btn"
            :aria-expanded="isOpen.toString()"
            :aria-label="
              $str('actions_menu_for', 'editor_weka', attrs.title || attrs.url)
            "
            @click="toggle"
          >
            <More />
          </ButtonIcon>
        </div>
      </template>
      <DropdownButton @click="open">
        {{ $str('go_to_link_label', 'editor_weka') }}
      </DropdownButton>
      <DropdownButton @click="edit">
        {{ $str('edit', 'core') }}
      </DropdownButton>
      <DropdownButton @click="toLink">
        {{ $str('display_as_text', 'editor_weka') }}
      </DropdownButton>
      <DropdownButton @click="remove">
        {{ $str('remove', 'core') }}
      </DropdownButton>
    </Dropdown>
  </div>
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownButton from 'tui/components/dropdown/DropdownButton';
import LinkBlockInner from 'tui/components/json_editor/nodes/LinkBlock';
import More from 'tui/components/icons/More';

export default {
  components: {
    ButtonIcon,
    Dropdown,
    DropdownButton,
    LinkBlockInner,
    More,
  },

  extends: BaseNode,

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

    remove() {
      this.$emit('remove');
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "edit",
    "remove"
  ],
  "editor_weka": [
    "actions_menu_for",
    "display_as_text",
    "go_to_link_label"
  ]
}
</lang-strings>

<style lang="scss">
.tui-wekaLinkBlock {
  max-width: 28.6rem;
  margin-bottom: var(--paragraph-gap);
  white-space: normal;

  .tui-dropdown__menu {
    width: auto;
  }

  &__btn:not(:focus) {
    @include sr-only();
  }

  &__btn-wrapper {
    display: flex;
    justify-content: flex-end;
  }
}
</style>
