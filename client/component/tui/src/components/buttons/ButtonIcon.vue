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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module tui
-->

<template>
  <button
    class="tui-iconBtn"
    :aria-describedby="ariaDescribedby"
    :aria-expanded="ariaExpanded"
    :aria-haspopup="ariaHaspopup"
    :aria-label="ariaLabel"
    :aria-disabled="ariaDisabled"
    :class="{
      'tui-formBtn--alert': styleclass.alert,
      'tui-iconBtn--prim': styleclass.primary,
      'tui-iconBtn--small': styleclass.small,
      'tui-iconBtn--circle': styleclass.circle,
      'tui-iconBtn--hasText': text,
      'tui-iconBtn--transparent': styleclass.transparent,
      'tui-iconBtn--stealth': styleclass.stealth,
      'tui-iconBtn--textFirst': styleclass.textFirst,
      'tui-iconBtn--toggle': styleclass.toggle,
      'tui-iconBtn--xsmall': styleclass.xsmall,
      'tui-iconBtn--transparent-noPadding': styleclass.transparentNoPadding,
    }"
    :disabled="disabled"
    :formaction="formaction"
    :formenctype="formenctype"
    :formmethod="formmethod"
    :formnovalidate="formnovalidate"
    :formtarget="formtarget"
    :name="name"
    :type="type"
    :title="titleText"
    :value="value"
    @click="$emit('click', $event)"
  >
    <span class="tui-iconBtn__wrap">
      <span class="tui-iconBtn__label">
        <span class="tui-iconBtn__icon" aria-hidden="true">
          <slot />
        </span>
        <span v-if="text" class="tui-iconBtn__text">
          {{ text }}
        </span>
      </span>
      <Caret v-if="caret" class="tui-iconBtn__caret" />
      <Loading
        v-if="loading"
        :size="styleclass.xsmall ? 100 : 200"
        class="tui-iconBtn__loading"
        :alt="'(' + $str('loading', 'core') + ')'"
      />
    </span>
  </button>
</template>

<script>
import Caret from 'tui/components/decor/Caret';
import Loading from 'tui/components/icons/Loading';

export default {
  components: {
    Caret,
    Loading,
  },

  props: {
    ariaDescribedby: String,
    ariaExpanded: {
      type: [Boolean, String],
      default: false,
    },
    ariaDisabled: Boolean,
    ariaHaspopup: [Boolean, String],
    ariaLabel: {
      type: [Boolean, String],
      required: true,
    },
    autofocus: Boolean,
    caret: Boolean,
    styleclass: {
      default: () => ({
        primary: false,
        small: false,
        circle: false,
        toggle: false,
        transparent: false,
        textFirst: false,
        xsmall: false,
        transparentNoPadding: false,
      }),
      type: Object,
    },
    disabled: Boolean,
    formaction: String,
    formenctype: {
      type: String,
      validator(value) {
        const allowedOptions = [
          'application/x-www-form-urlencoded',
          'multipart/form-data',
          'text/plain',
        ];
        return allowedOptions.indexOf(value) !== -1;
      },
    },
    formmethod: {
      type: String,
      validator(value) {
        const allowedOptions = ['get', 'post'];
        return allowedOptions.indexOf(value) !== -1;
      },
    },
    formnovalidate: Boolean,
    formtarget: {
      type: String,
      validator(value) {
        const allowedOptions = ['_blank', '_parent', '_self', '_top'];
        return allowedOptions.indexOf(value) !== -1;
      },
    },
    loading: Boolean,
    name: String,
    text: String,
    title: {
      type: [String, Boolean],
      default: null,
    },
    type: {
      default: 'button',
      type: String,
      validator(value) {
        const allowedOptions = ['button', 'reset', 'submit'];
        return allowedOptions.indexOf(value) !== -1;
      },
    },
    value: String,
  },
  computed: {
    titleText() {
      if (this.title) {
        return this.title;
      }
      if (this.title === false) {
        // if title is specifically set to false, it means we don't want a
        // title (tooltip)
        return null;
      }

      return this.text !== this.ariaLabel ? this.ariaLabel : null;
    },
  },
  mounted() {
    if (this.autofocus && this.$el) {
      // Make the input element to be focused, when the prop autofocus is set.
      // We are moving away from the native attribute for element, because
      // different browser will treat autofocus different. Furthermore,
      // the slow performing browser will not make the element focused due
      // to the element is not rendered on time.
      this.$el.focus();
    }
  },
};
</script>

<lang-strings>
{
  "core": [
    "loading"
  ]
}
</lang-strings>

<style lang="scss">
.tui-iconBtn {
  // stylelint-disable-next-line tui/at-extend-only-placeholders
  @extend .tui-formBtn;

  display: inline-block;
  min-width: 0;
  padding: 0 var(--gap-2);

  // in order to vertically center content in IE we need this display: flex
  // wrapping div because:
  //   * putting `display: flex;` on .tui-iconBtn does not center vertically
  //     due to an IE 11 flex bug
  //     https://github.com/philipwalton/flexbugs#flexbug-3
  //   * relying on vertical-align for centering instead is not good enough -
  //     it's off by a px or two
  &__wrap {
    display: flex;
    align-items: center;
    justify-content: center;

    > .tui-iconBtn__caret {
      margin: 0 var(--gap-1);
    }

    > .tui-iconBtn__loading {
      margin-left: var(--gap-1);
    }
  }

  &__icon {
    display: flex;
    flex-shrink: 0;
    font-size: var(--font-size-16);
  }

  &__label {
    display: inline-flex;
    align-items: center;
  }

  &__text {
    @include tui-wordbreak--hard;
  }

  &--alert {
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-formBtn--alert;
  }

  &--prim {
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-formBtn--prim;
  }

  &--small {
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-formBtn--small;
    padding: 0 var(--gap-1);
    font-size: var(--font-size-13);

    .tui-iconBtn__icon {
      padding: 0 2px;
      font-size: var(--font-size-14);
    }
  }

  &--xsmall {
    min-height: 2rem;
    padding: 0 0.1rem;
    font-size: var(--font-size-12);

    .tui-iconBtn__icon {
      padding: 0 1px;
      font-size: var(--font-size-11);
    }
  }

  &--small&--hasText,
  &--xsmall&--hasText {
    .tui-iconBtn__icon {
      padding-right: 0;
    }
  }

  &--circle {
    width: 3.6rem;
    height: 3.6rem;
    padding: 0;
    border-radius: 50%;

    &.tui-iconBtn--small {
      width: 3rem;
      height: 3rem;
    }

    &.tui-iconBtn--xsmall {
      width: 2rem;
      height: 2rem;
    }
  }

  &--transparent {
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-formBtn--transparent;
  }

  &--transparent-noPadding {
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-formBtn--transparent;
    &.tui-iconBtn,
    &.tui-iconBtn--small,
    &.tui-iconBtn--xsmall {
      padding: 0;
    }
  }

  &--stealth {
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-formBtn--stealth;
  }

  &--textFirst &__label {
    flex-direction: row-reverse;
    .tui-iconBtn__text {
      padding: 0 var(--gap-1);
    }
  }
}

.tui-iconBtn__text {
  padding: 0 var(--gap-1);
}
</style>
