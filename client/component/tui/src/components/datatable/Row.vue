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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <div
    v-focus-within="!inGroup"
    class="tui-dataTableRow"
    :class="{
      'tui-dataTableRow--colorOdd': colorOdd,
      'tui-dataTableRow--disabled': disabled,
      'tui-dataTableRow--inGroup': inGroup,
      'tui-dataTableRow--selected': selected && !selectedHighlightOff,
      'tui-dataTableRow--draggable': draggable,
      'tui-dataTableRow--dragging': dragging,
      'tui-dataTableRow--borderTopFirstOff': borderTopHidden,
      'tui-dataTableRow--borderBottomLastOff': borderBottomHidden,
      'tui-dataTableRow--borderSeparatorOff': borderSeparatorHidden,
      'tui-dataTableRow--hoverOff': hoverOff,
      'tui-dataTableRow--expanded': expanded,
      'tui-dataTableRow--stealth': stealth,
      'tui-dataTableRow--stealthExpanded': stealthExpanded,
      'tui-dataTableRow--indented': indented,
      'tui-dataTableRow--stacked': isStacked,
    }"
    role="row"
  >
    <slot />
  </div>
</template>

<script>
export default {
  props: {
    borderBottomHidden: Boolean,
    borderSeparatorHidden: Boolean,
    borderTopHidden: Boolean,
    colorOdd: Boolean,
    disabled: Boolean,
    hoverOff: Boolean,
    inGroup: Boolean,
    selected: Boolean,
    selectedHighlightOff: Boolean,
    draggable: Boolean,
    dragging: Boolean,
    indented: Boolean,
    expanded: Boolean,
    stealth: Boolean,
    stealthExpanded: Boolean,
    isStacked: Boolean,
  },
};
</script>

<style lang="scss">
.tui-dataTableRow {
  position: relative;
  display: flex;
  flex-direction: row;
  padding: var(--gap-3) var(--gap-1);
  background: var(--datatable-row-bg-color);
  border-top: 1px solid var(--datatable-row-border-color);

  & > * + * {
    padding-left: var(--gap-4);
  }

  &:first-child {
    border-top: var(--border-width-normal) solid
      var(--datatable-row-first-border-color);
  }

  &:last-child {
    border-bottom: 1px solid var(--datatable-row-border-color);
  }

  &.tui-focusWithin,
  &:active,
  &:hover {
    background: var(--datatable-row-bg-color-focus);
  }

  &--indented {
    padding-left: var(--gap-11);
  }

  &--stealth {
    box-shadow: none;
  }

  &--borderTopFirstOff {
    &:first-child {
      border-top: none;
    }
  }

  &--borderBottomLastOff {
    &:last-child {
      border-bottom: none;
    }
  }

  &--borderSeparatorOff:not(:first-child) {
    border-top: none;
  }

  &--selected {
    background: var(--datatable-row-bg-color-active);

    &:hover {
      background: var(--datatable-row-bg-color-focus);
    }
  }

  &--colorOdd:not(&--selected) {
    &:nth-child(odd) {
      background: var(--datatable-row-bg-color-odd);

      &.tui-focusWithin,
      &:hover {
        background: var(--datatable-row-bg-color-focus);
      }
    }
  }

  &--hoverOff {
    &.tui-focusWithin,
    &:active,
    &:hover {
      background: var(--datatable-row-bg-color);
    }
  }

  &--hoverOff&--colorOdd {
    &:nth-child(odd) {
      &.tui-focusWithin,
      &:hover {
        background: var(--datatable-row-bg-color-odd);
      }
    }
  }

  &--inGroup {
    border-top: none;
    &:first-child {
      border-top: none;
    }

    &:last-child {
      border-bottom: none;
    }
  }

  &--disabled {
    color: var(--color-neutral-6);
  }

  // don't show hover background when another item is being dragged over it
  [data-tui-droppable-any-active] &:hover {
    background: var(--datatable-row-bg-color);
  }

  &--draggable {
    // apply a background so you don't see through the row when dragging
    // (default is transparent)
    background: var(--color-background);
    user-select: none;
    &.tui-focusWithin,
    &:active,
    &:hover {
      background: var(--color-background);
    }
  }

  &--draggable > .tui-dataTableCell {
    pointer-events: none;
  }

  &--dragging {
    box-shadow: var(--shadow-3);
  }

  &--expanded {
    margin-left: calc(0px - var(--border-width-thin));
    background-color: var(--datatable-expanded-bg-color);
    border: var(--border-width-thin) solid
      var(--datatable-expanded-border-color);
    border-bottom: none;
    box-shadow: var(--shadow-2);
  }

  &--stealthExpanded {
    border-right: none;
    border-left: none;
    box-shadow: none;
  }
}

.tui-dataTableRow--stacked {
  flex-direction: column;
  padding: var(--gap-3) 0;

  & > * + * {
    padding-left: 0;
  }

  &:first-child {
    border-top: var(--border-width-normal) solid
      var(--datatable-row-first-border-color);
  }

  &:last-child {
    border-bottom: 1px solid var(--datatable-row-border-color);
  }

  &.tui-dataTableRow--inGroup {
    border-top: 1px solid var(--datatable-row-border-color);

    &:first-child {
      border-top: none;
    }

    &:last-child {
      border-bottom: none;
    }

    &:nth-child(odd) {
      background: none;
    }
    &:hover {
      background: none;
    }
  }
}
</style>
