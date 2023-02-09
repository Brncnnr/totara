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
    class="tui-searchFilter"
    :class="{ 'tui-searchFilter--stacked': stacked }"
  >
    <Label
      v-if="!dropLabel"
      :for-id="generatedId"
      :hidden="!showLabel"
      :label="label"
    />
    <div class="tui-searchFilter__group">
      <div class="tui-searchFilter__group-icon">
        <SearchIcon />
      </div>
      <InputSearch
        :id="generatedId"
        ref="search"
        v-bind="$props"
        :aria-label="ariaLabel || label"
        :styleclass="{ preIcon: true, postIcon: true }"
        class="tui-searchFilter__search"
        @input="input"
        @submit="submit"
      />
      <ButtonIcon
        v-if="isClearIconVisible"
        class="tui-searchFilter__group-clearContainer"
        :aria-label="$str('clear_search_term', 'totara_core')"
        :disabled="disabled"
        :styleclass="{ small: true, transparent: true }"
        @click="clear"
      >
        <RemoveIcon class="tui-searchBox__removeIcon" />
      </ButtonIcon>
    </div>
  </div>
</template>

<script>
// Components
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import InputSearch from 'tui/components/form/InputSearch';
import Label from 'tui/components/form/Label';
import RemoveIcon from 'tui/components/icons/Remove';
import SearchIcon from 'tui/components/icons/Search';
import { debounce } from 'tui/util';

export default {
  components: {
    ButtonIcon,
    InputSearch,
    Label,
    RemoveIcon,
    SearchIcon,
  },
  inheritAttrs: false,

  /* eslint-disable vue/require-prop-types */
  props: {
    ariaDescribedby: {},
    ariaLabel: {},
    ariaLabelledby: {},
    debounceInput: {
      type: Boolean,
      default: true,
    },
    disabled: {},
    dropLabel: {
      required: false,
      type: Boolean,
    },
    id: {},
    label: {
      required: true,
      type: String,
    },
    list: {},
    maxlength: {},
    minlength: {},
    name: {},
    pattern: {},
    placeholder: {},
    showLabel: {
      required: false,
      type: Boolean,
    },
    size: {},
    spellcheck: {},
    stacked: {},
    value: {},
  },

  computed: {
    generatedId() {
      return this.id || this.$id();
    },

    isClearIconVisible() {
      return this.value && this.value.length > 0;
    },
  },

  created() {
    this.inputDebounced = debounce(e => {
      this.$emit('input', e);
    }, 500);
  },

  methods: {
    input(e) {
      if (this.debounceInput) {
        this.inputDebounced(e);
      } else {
        this.$emit('input', e);
      }
    },

    clear() {
      this.$refs.search.$el.focus();
      this.$emit('clear');
      this.$emit('input', ''); // This is not debounced so that clearing isn't slowed in the ui.
    },

    submit() {
      this.$emit('submit');
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "clear_search_term"
  ]
}
</lang-strings>

<style lang="scss">
.tui-searchFilter {
  position: relative;
  display: flex;
  flex-direction: row;
  align-items: center;

  input.tui-formInput {
    flex-grow: 0;
  }

  .tui-formLabel {
    margin: auto var(--gap-3) auto 0;
  }

  &__group {
    position: relative;
    display: flex;
    flex-grow: 1;
    margin-top: 0;
    margin-bottom: auto;

    &--stacked {
      margin-top: var(--gap-1);
    }

    &-icon {
      position: absolute;
      left: var(--gap-1);
      display: flex;
      align-items: center;
      height: 100%;
      color: var(--filter-search-icon-color);

      .fa-search {
        margin: auto 0;
      }
    }

    &-clearContainer {
      position: absolute;
      right: 0;
      height: 100%;
    }
  }

  &__search {
    // disable the default clear (x) action in IE
    &::-ms-clear {
      display: none;
    }
  }

  &__removeIcon {
    color: var(--filter-search-clear-icon-color);
  }

  &--stacked {
    flex-direction: column;
    align-items: stretch;

    .tui-formLabel {
      margin: var(--gap-1) 0;
    }
  }
}
</style>
