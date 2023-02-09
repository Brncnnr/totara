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
  <button
    class="tui-formBtn"
    :aria-expanded="ariaExpanded"
    :aria-disabled="computedAriaDisabled"
    :aria-describedby="ariaDescribedby"
    :aria-haspopup="ariaHaspopup"
    :class="{
      'tui-formBtn--alert': styleclass.alert,
      'tui-formBtn--prim': styleclass.primary,
      'tui-formBtn--small': styleclass.small,
      'tui-formBtn--srOnly': styleclass.srOnly,
      'tui-formBtn--transparent': styleclass.transparent,
      'tui-formBtn--reveal': styleclass.reveal,
      'tui-formBtn--stealth': styleclass.stealth,
      'tui-formBtn--loading': loading,
    }"
    :disabled="disabled"
    :formaction="formaction"
    :formenctype="formenctype"
    :formmethod="formmethod"
    :formnovalidate="formnovalidate"
    :formtarget="formtarget"
    :name="name"
    :type="type"
    :value="value"
    @click="handleClick"
  >
    <span class="tui-formBtn__text">
      {{ text }}
    </span>
    <Caret v-if="caret" class="tui-formBtn__caret" />
    <span aria-live="assertive">
      <span v-if="loading" class="tui-formBtn__loading">
        <Loading :alt="$str('button_loading_text', 'totara_core', text)" />
      </span>
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
    ariaDisabled: {
      type: [Boolean, String],
      default: null,
    },
    ariaDescribedby: String,
    ariaExpanded: {
      type: [Boolean, String],
      default: false,
    },
    ariaHaspopup: [Boolean, String],
    autofocus: Boolean,
    caret: Boolean,
    styleclass: {
      default: () => ({
        alert: false,
        primary: false,
        small: false,
        transparent: false,
      }),
      type: Object,
    },
    disabled: Boolean,
    formaction: String,
    formenctype: {
      type: String,
      validator: x =>
        [
          'application/x-www-form-urlencoded',
          'multipart/form-data',
          'text/plain',
        ].includes(x),
    },
    formmethod: {
      type: String,
      validator: x => ['get', 'post'].includes(x),
    },
    formnovalidate: Boolean,
    formtarget: {
      type: String,
      validator: x => ['_blank', '_parent', '_self', '_top'].includes(x),
    },
    loading: Boolean,
    name: String,
    text: {
      required: true,
      type: String,
    },
    type: {
      default: 'button',
      type: String,
      validator: x => ['button', 'reset', 'submit'].includes(x),
    },
    value: String,
  },

  computed: {
    computedAriaDisabled() {
      if (
        this.ariaDisabled === true ||
        this.ariaDisabled === 'true' ||
        this.loading
      ) {
        return 'true';
      }

      // don't add `aria-disabled="false"` if nothing was ever passed
      return this.ariaDisabled == null ? null : 'false';
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

  methods: {
    handleClick(e) {
      if (this.disabled || this.loading) {
        // prevent from acting as a submit button
        e.preventDefault();
        return;
      }
      this.$emit('click', e);
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "button_loading_text"
  ]
}
</lang-strings>

<style lang="scss">
// Reset
.tui-toggleBtn,
.tui-formBtn {
  display: inline-block;
  align-items: flex-start;
  box-sizing: border-box;
  height: auto;
  padding: 1px 7px 2px;
  overflow: visible;
  color: buttontext;
  font-weight: normal;
  font-size: 14px;
  line-height: normal;
  letter-spacing: normal;
  white-space: normal;
  text-align: center;
  text-transform: none;
  text-decoration: none;
  text-indent: 0;
  text-shadow: none;
  vertical-align: baseline;
  word-spacing: normal;
  background-color: buttonface;
  border-color: rgb(216, 216, 216) rgb(209, 209, 209) rgb(186, 186, 186);
  border-style: solid;
  border-width: 1px;
  border-radius: 0;
  border-image: initial;
  cursor: pointer;
  touch-action: auto;
  text-rendering: auto;

  &:active {
    border-style: inset;
  }
  &:focus {
    outline-width: 5px;
    outline-style: auto;
  }
}

.tui-formBtn,
%tui-formBtn {
  position: relative;
  display: inline-block;
  flex-shrink: 0;
  min-width: var(--btn-min-width);
  max-width: 100%;
  min-height: var(--btn-min-height);
  // prettier-ignore
  padding: calc((var(--btn-min-height) - var(--form-input-font-size) * 1.2) / 2 - var(--form-input-border-size)) var(--gap-4);

  color: var(--btn-text-color);
  font-size: var(--form-input-font-size);
  line-height: 1.2;
  word-wrap: break-word;
  word-break: break-word;
  hyphens: none;
  background: var(--btn-bg-color);
  border: var(--form-input-border-size) solid;
  border-color: var(--btn-border-color);
  border-radius: var(--btn-radius);
  cursor: pointer;
  transition: tui-transition('button', background-color),
    tui-transition('button', border-color), tui-transition('button', box-shadow);

  > .tui-formBtn__caret {
    margin-left: var(--gap-2);
  }

  .tui-formBtn__loading {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--btn-loader-color-disabled);
  }

  &:active,
  &:focus,
  &:active:focus,
  &:active:hover,
  &:hover {
    color: var(--btn-text-color-focus);
    text-decoration: none;
    background: var(--btn-bg-color-focus);
    border-color: var(--btn-border-color-focus);
    outline: 0;
    box-shadow: var(--btn-shadow-focus);
  }

  &:active,
  &:active:focus,
  &:active:hover {
    color: var(--btn-text-color-active);
    background: var(--btn-bg-color-active);
    border: var(--form-input-border-size) solid;
    border-color: var(--btn-border-color-active);
    box-shadow: var(--btn-shadow-active);
  }

  &:disabled,
  &[disabled],
  &--loading {
    color: var(--btn-text-color-disabled);
    background-color: var(--btn-bg-color-disabled);
    border-color: var(--btn-border-color-disabled);
    cursor: default;
    opacity: 1;

    &:active,
    &:focus,
    &:active:focus,
    &:active:hover,
    &:hover {
      color: var(--btn-text-color-disabled);
      background-color: var(--btn-bg-color-disabled);
      border-color: var(--btn-border-color-disabled);
      box-shadow: none;
    }
  }

  &--alert {
    color: var(--btn-alert-text-color);
    background: var(--btn-alert-bg-color);
    border-color: var(--btn-alert-border-color);

    &:focus,
    &:hover {
      color: var(--btn-alert-text-color-focus);
      background: var(--btn-alert-bg-color-focus);
      border-color: var(--btn-alert-border-color-focus);
    }

    &:active,
    &:active:focus,
    &:active:hover {
      color: var(--btn-alert-text-color-active);
      background: var(--btn-alert-bg-color-active);
      border-color: var(--btn-alert-border-color-active);
    }

    &:disabled,
    &[disabled],
    &.tui-formBtn--loading {
      color: var(--btn-alert-text-color-disabled);
      background: var(--btn-alert-bg-color-disabled);
      border-color: var(--btn-alert-border-color-disabled);
      cursor: default;
      opacity: 1;

      &:active,
      &:focus,
      &:active:focus,
      &:active:hover,
      &:hover {
        color: var(--btn-alert-text-color-disabled);
        background: var(--btn-alert-bg-color-disabled);
        border-color: var(--btn-alert-border-color-disabled);
        box-shadow: none;
      }
    }
  }

  &--prim {
    color: var(--btn-prim-text-color);
    font-weight: bold;
    background: var(--btn-prim-bg-color);
    border-color: var(--btn-prim-border-color);

    &:focus,
    &:hover {
      color: var(--btn-prim-text-color-focus);
      background: var(--btn-prim-bg-color-focus);
      border-color: var(--btn-prim-border-color-focus);
    }

    &:active,
    &:active:focus,
    &:active:hover {
      color: var(--btn-prim-text-color-active);
      background: var(--btn-prim-bg-color-active);
      border-color: var(--btn-prim-border-color-active);
    }

    &:disabled,
    &[disabled],
    &.tui-formBtn--loading {
      color: var(--btn-prim-text-color-disabled);
      background: var(--btn-prim-bg-color-disabled);
      border-color: var(--btn-prim-border-color-disabled);
      cursor: default;
      opacity: 1;

      &:active,
      &:focus,
      &:active:focus,
      &:active:hover,
      &:hover {
        color: var(--btn-prim-text-color-disabled);
        background: var(--btn-prim-bg-color-disabled);
        border-color: var(--btn-prim-border-color-disabled);
        box-shadow: none;
      }
    }

    .tui-formBtn__loading {
      color: var(--btn-prim-loader-color-disabled);
    }
  }

  &--small {
    min-height: var(--form-input-height);
    // prettier-ignore
    padding: calc((var(--form-input-height) - var(--form-input-font-size-sm) * 1.2) / 2 - var(--form-input-border-size)) var(--gap-3);

    font-size: var(--form-input-font-size-sm);
    line-height: 1.2;
  }

  &--transparent,
  &--reveal {
    min-height: auto;
    padding: 0;
    line-height: 1;
    border-radius: 0;
  }

  &--transparent,
  &--reveal,
  &--stealth {
    min-width: 0;
    color: var(--color-state);
    background: transparent;
    border: none;
    cursor: pointer;

    &:focus {
      color: var(--color-state-focus);
      text-decoration: none;
      background: transparent;
      border: none;
      box-shadow: none;
    }

    &:hover {
      color: var(--color-state-hover);
      text-decoration: none;
      background: transparent;
      border: none;
      box-shadow: none;
    }

    &:active,
    &:active:hover,
    &:active:focus {
      color: var(--color-state-active);
      text-decoration: none;
      background: transparent;
      border: none;
      box-shadow: none;
    }

    &:active:focus,
    &:focus {
      @include tui-focus();
    }

    &:disabled,
    &.tui-formBtn--loading {
      color: var(--color-state-disabled);
      background: transparent;
      opacity: 1;

      &:active,
      &:focus,
      &:active:focus,
      &:active:hover,
      &:hover {
        color: var(--color-state-disabled);
        background: transparent;
        box-shadow: none;
      }
    }
  }

  &--reveal {
    color: currentColor;
    border-bottom: 1px dashed var(--color-state);

    &:focus,
    &:hover,
    &:active,
    &:active:hover,
    &:active:focus {
      color: currentColor;
      border-bottom: 1px dashed var(--color-state);
    }

    &:disabled,
    &.tui-formBtn--loading {
      color: currentColor;
      border-bottom: 1px dashed var(--color-state);
      opacity: 0.7;

      &:active,
      &:focus,
      &:active:focus,
      &:active:hover,
      &:hover {
        color: currentColor;
        border-bottom: 1px dashed var(--color-state);
      }
    }
  }

  &--transparent &__text {
    position: relative;
  }

  &--loading &__text {
    visibility: hidden;
  }

  &--srOnly {
    @include sr-only();
  }
}
</style>
