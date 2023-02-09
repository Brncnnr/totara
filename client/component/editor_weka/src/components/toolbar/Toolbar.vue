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
    class="tui-wekaToolbar"
    :class="{ 'tui-wekaToolbar--sticky': sticky }"
    role="toolbar"
    :aria-label="$str('label_toolbar', 'editor_weka')"
  >
    <div class="tui-wekaToolbar__group">
      <Dropdown :separator="false">
        <template v-slot:trigger="{ toggle, isOpen }">
          <ToolbarButton
            class="tui-wekaToolbar__currentBlock tui-wekaToolbar__button"
            :text="activeBlockName"
            :aria-expanded="isOpen ? 'true' : 'false'"
            :aria-label="
              $str('format_as_blocktype_status', 'editor_weka', activeBlockName)
            "
            :disabled="!blockSelectorEnabled"
            caret
            @click="toggle()"
            @keydown.native="handleButtonKeydown"
            @focus.native="handleButtonFocus"
          />
        </template>
        <DropdownItem
          v-for="(item, i) in blockItems"
          :key="i"
          :selected="item.active"
          :disabled="!item.active && !item.enabled"
          @click="itemClick(item)"
        >
          {{ item.label.toString() }}
        </DropdownItem>
      </Dropdown>
    </div>
    <div
      v-for="(itemGroup, groupKey) in itemGroups"
      :key="groupKey"
      class="tui-wekaToolbar__group"
    >
      <template v-for="(item, i) in itemGroup">
        <!-- dropdown toolbar item -->
        <Dropdown
          v-if="item.children"
          :key="i"
          :separator="false"
          context-mode="uncontained"
        >
          <template v-slot:trigger="{ toggle }">
            <ToolbarButtonIcon
              class="tui-wekaToolbar__button"
              :text="item.label.toString()"
              :active="item.active"
              :disabled="!item.enabled"
              caret
              @click="toggle"
              @keydown.native="handleButtonKeydown"
              @focus.native="handleButtonFocus"
            >
              <template slot="icon">
                <component :is="item.iconComponent" />
              </template>
            </ToolbarButtonIcon>
          </template>
          <template v-for="(child, j) in item.children">
            <div
              v-if="child.type === 'separator'"
              :key="j"
              class="tui-wekaToolbar__dropdownSeparator"
            />
            <div
              v-else-if="child.type === 'button'"
              :key="j"
              class="tui-wekaToolbar__dropdownButtonWrap"
            >
              <ButtonIcon
                v-if="child.iconComponent"
                :key="j"
                :text="child.label.toString()"
                :aria-label="child.label.toString()"
                :disabled="!child.enabled"
                @click="itemClick(child)"
              >
                <component :is="child.iconComponent" />
              </ButtonIcon>
              <Button
                v-else
                :key="j"
                :text="child.label.toString()"
                :disabled="!child.enabled"
                @click="itemClick(child)"
              />
            </div>
            <DropdownItem
              v-else
              :key="j"
              :disabled="!child.enabled"
              :selected="child.active"
              @click="itemClick(child)"
            >
              <div class="tui-wekaToolbar__dropdownItemContent">
                <component
                  :is="child.iconComponent"
                  class="tui-wekaToolbar__dropdownItemIcon"
                />{{ child.label.toString() }}
              </div>
            </DropdownItem>
          </template>
        </Dropdown>
        <!-- popover toolbar component -->
        <Popover
          v-else-if="item.popover"
          :key="i"
          :triggers="['click']"
          :title="item.popover.title"
          context-mode="uncontained"
          @open-changed="openChanged(item, ...arguments)"
        >
          <template v-slot:trigger="{ isOpen }">
            <ToolbarButtonIcon
              class="tui-wekaToolbar__button"
              :text="item.label.toString()"
              :selected="item.active"
              :disabled="!item.enabled"
              aria-haspopup="true"
              :aria-expanded="isOpen ? 'true' : 'false'"
              @keydown.native="handleButtonKeydown"
              @focus.native="handleButtonFocus"
            >
              <template slot="icon">
                <component :is="item.iconComponent" />
              </template>
            </ToolbarButtonIcon>
          </template>
          <template v-slot:default="{ close }">
            <component :is="item.popover.component" @close="close" />
          </template>
        </Popover>
        <!-- regular toolbar button -->
        <ToolbarButtonIcon
          v-else
          :key="i"
          class="tui-wekaToolbar__button"
          :text="item.label.toString()"
          :selected="item.active"
          :disabled="!item.enabled"
          @click="itemClick(item)"
          @keydown.native="handleButtonKeydown"
          @focus.native="handleButtonFocus"
        >
          <template slot="icon">
            <component :is="item.iconComponent" />
          </template>
        </ToolbarButtonIcon>
      </template>
    </div>
  </div>
</template>

<script>
import { getFocusableElements } from 'tui/dom/focus';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import ShowIcon from 'tui/components/icons/Show';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ToolbarButton from 'editor_weka/components/toolbar/ToolbarButton';
import ToolbarButtonIcon from 'editor_weka/components/toolbar/ToolbarButtonIcon';
import Popover from 'tui/components/popover/Popover';

export default {
  components: {
    Dropdown,
    DropdownItem,
    ShowIcon,
    Button,
    ButtonIcon,
    ToolbarButton,
    ToolbarButtonIcon,
    Popover,
  },

  props: {
    items: {
      type: Array,
    },
    sticky: Boolean,
  },

  computed: {
    activeBlockName() {
      const block = this.blockItems.find(x => x.active);
      return block ? block.label.toString() : 'Block';
    },

    blockSelectorEnabled() {
      return this.blockItems.some(x => x.enabled);
    },

    blockItems() {
      return this.items.filter(x => x.group == 'blocks');
    },

    itemGroups() {
      const items = this.items.filter(x => x.group != 'blocks');
      const groups = {
        text: [],
        embeds: [],
      };
      items.forEach(x => {
        if (!groups[x.group]) {
          groups[x.group] = [];
        }
        groups[x.group].push(x);
      });
      return groups;
    },
  },

  methods: {
    itemClick(item) {
      if (item.enabled && item.execute) {
        item.execute();
      }
    },

    openChanged(item, visible) {
      if (!visible && item.enabled && item.reset) {
        item.reset();
      }
    },

    /**
     * Handle keydown event on a button
     *
     * @param {KeyboardEvent} e
     */
    handleButtonKeydown(e) {
      const tb = e.currentTarget;

      switch (e.key) {
        case 'ArrowLeft':
        case 'Left':
          this.$_moveFocus(tb, 'prev');
          break;

        case 'ArrowRight':
        case 'Right':
          this.$_moveFocus(tb, 'next');
          break;

        case 'Home':
          this.$_moveFocus(tb, 'first');
          break;

        case 'End':
          this.$_moveFocus(tb, 'last');
          break;

        default:
          return;
      }

      e.stopPropagation();
      e.preventDefault();
    },

    /**
     * Handle focus event on a button
     *
     * @param {FocusEvent} e
     */
    handleButtonFocus(e) {
      // update tabindex
      const currentButton = e.currentTarget;
      const buttons = this.$_getAllButtons();
      buttons.forEach(button => {
        button.tabIndex = button == currentButton ? 0 : -1;
      });
    },

    /**
     * Get all toolbar buttons in order
     *
     * @returns {Element[]}
     */
    $_getAllButtons() {
      return Array.prototype.slice.call(
        this.$el.querySelectorAll('.tui-wekaToolbar__button')
      );
    },

    /**
     * Move focus from a button to a different button
     *
     * @param {Element} relativeTo
     * @param {('next'|'prev'|'first'|'last')} direction
     */
    $_moveFocus(relativeTo, direction) {
      const focusable = getFocusableElements(this.$el);
      const buttons = this.$_getAllButtons().filter(x => focusable.includes(x));

      const lastIndex = buttons.length - 1;

      let index = 0;
      if (direction == 'prev' || direction == 'next') {
        index = buttons.indexOf(relativeTo);
        if (index == -1) {
          index = direction == 'next' ? lastIndex : 0;
        } else {
          index += direction == 'next' ? 1 : -1;
          if (index > lastIndex) {
            index = 0;
          } else if (index < 0) {
            index = lastIndex;
          }
        }
      } else if (direction == 'first') {
        index = 0;
      } else if (direction == 'last') {
        index = lastIndex;
      }

      if (buttons[index]) {
        buttons[index].focus();
      }
    },
  },
};
</script>

<lang-strings>
{
  "editor_weka": ["format_as_blocktype_status", "label_toolbar"]
}
</lang-strings>

<style lang="scss">
.tui-wekaToolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  padding: 0 var(--gap-2);
  background: var(--color-background);
  border-bottom: 1px solid var(--color-neutral-4);

  &--sticky {
    position: sticky;
    top: -0.5px; /* work around a Chrome bug that adds a 1px gap */
    z-index: var(--zindex-sticky);
  }

  &__group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    margin: 0 var(--gap-2);

    > * + * {
      margin-left: 1px;
    }
  }

  &__currentBlock.tui-formBtn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 8em;
    padding: 0 var(--gap-2);

    &:disabled {
      color: var(--color-state-disabled);
    }
  }

  &__dropdownItemContent {
    display: flex;
    align-items: center;
  }

  &__dropdownItemIcon {
    margin-right: var(--gap-2);
  }

  &__dropdownButtonWrap {
    display: flex;
    flex-direction: column;
    padding: var(--gap-3) var(--gap-4);
  }

  &__dropdownSeparator {
    margin: var(--gap-2) var(--gap-4);
    border-bottom: 1px solid var(--color-neutral-5);
  }
}
</style>
