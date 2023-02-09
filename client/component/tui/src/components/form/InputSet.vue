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
  @module tui
-->

<template>
  <div
    class="tui-inputSet"
    :role="labelId ? 'group' : null"
    :class="[
      charLength ? 'tui-inputSet--charLength-' + charLength : null,
      charLength ? 'tui-input--customSize' : null,
      split ? 'tui-inputSet--split' : null,
    ]"
    :aria-labelledby="labelId"
  >
    <div
      class="tui-inputSet__inner"
      :class="[
        'tui-inputSet__inner--' + direction,
        split ? 'tui-inputSet__inner--split' : null,
        split && stackBelow
          ? 'tui-inputSet__inner--stackBelow-' + stackBelow
          : null,
      ]"
    >
      <slot />
    </div>
  </div>
</template>

<script>
import { charLengthProp, isValidCharLength } from './form_common';

export default {
  inject: {
    reformFieldContext: { default: null },
  },

  provide() {
    return {
      // prevent field context from being passed down
      reformFieldContext: null,
    };
  },

  props: {
    ariaLabelledby: String,
    vertical: Boolean,
    split: Boolean,
    charLength: charLengthProp,
    stackBelow: {
      type: [String, Number],
      validator: isValidCharLength,
    },
  },

  computed: {
    direction() {
      return this.vertical ? 'vertical' : 'horizontal';
    },

    labelId() {
      return (
        this.ariaLabelledby ||
        (this.reformFieldContext && this.reformFieldContext.getLabelId())
      );
    },
  },
};
</script>

<style lang="scss">
@mixin tui-input-set-stack-below($name, $size) {
  &--stackBelow-#{$name} > *,
  // need to specify .tui-formInput here too for specificity reasons
  &--stackBelow-#{$name} > .tui-formInput {
    // This triggers the children to switch to being vertically stacked below a
    // certain width.
    // It works like this:
    // Above the specified width, (width - 100%) evaluates to a large
    // negative flex basis, and is therefore ignored.
    // Below the specified width, (width - 100%) evaluates to a large
    // positve flex basis, and forces each item to take up its own line.
    // Magic!
    // prettier-ignore
    flex-basis: calc((#{tui-char-length($size)} - (100% - var(--input-set-spacing))) * 999);
  }
}
.tui-inputSet {
  display: flex;
  flex-grow: 1;

  @include tui-char-length-classes();

  // can't set margin on inputSet itself, so it is just a wrapper for this
  // the variants are on inner to ensure the & > * selectors retain low specificity (0-1-0)
  & > &__inner {
    display: flex;
    flex-basis: 0; // required for things to look correct in IE 11
    flex-grow: 1;
    margin: calc((var(--input-set-spacing) / 2) * -1);

    &--vertical {
      flex-direction: column;
    }

    &--horizontal {
      flex-direction: row;
      flex-wrap: wrap;
    }

    & > *,
    & > .tui-formInput {
      margin: calc(var(--input-set-spacing) / 2);
    }

    & > .tui-formLabel {
      padding: 0;
    }

    & > {
      // replaced input elements have their width set to 100% normally as
      // `width: auto` doesn't fill the container like it does on divs
      #{$tui-input-replaced-selectors} {
        width: auto;
      }
    }

    &--split {
      & > * {
        flex-basis: 0;
        flex-grow: 1;
        width: auto;
      }
    }

    @each $size in $tui-char-length-scale {
      @include tui-input-set-stack-below($size, $size);
    }
  }
}
</style>
