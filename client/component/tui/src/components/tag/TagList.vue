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
  <Dropdown
    :close-on-click="closeOnClick"
    :separator="separator"
    match-width
    :fixed-height="!!virtualScrollOptions"
    @open="$emit('open')"
    @dismiss="setFocus"
  >
    <template v-slot:trigger="{ toggle, isOpen }">
      <div class="tui-tagList" @click="handleClick(toggle, isOpen)">
        <div class="tui-tagList__tags">
          <OverflowDetector v-slot="{ measuring }" @change="overflowChanged">
            <div
              class="tui-tagList__tagItems"
              :class="{ 'tui-tagList__tagItems--open': isOpen }"
              aria-live="polite"
              role="status"
              aria-atomic="false"
              :aria-label="
                labelName
                  ? $str('selected_tag_list_name', 'totara_core', labelName)
                  : $str('tags_selected', 'totara_core')
              "
              aria-relevant="additions"
            >
              <template v-for="(tag, index) in tags">
                <slot
                  v-if="isOpen || measuring || index < visible"
                  name="tag"
                  :tag="tag"
                >
                  <Tag :key="index" :text="tag.text">
                    <template v-slot:button>
                      <ButtonIcon
                        ref="tagIcon"
                        :disabled="disabled"
                        :styleclass="{
                          transparent: true,
                          primary: true,
                          small: true,
                        }"
                        :aria-label="
                          labelName
                            ? $str('tag_remove_from', 'totara_core', {
                                name: tag.text,
                                taglist: labelName,
                              })
                            : $str('tag_remove', 'totara_core', tag.text)
                        "
                        @click.stop.prevent="handleRemove(tag, index)"
                      >
                        <Close size="100" />
                      </ButtonIcon>
                    </template>
                  </Tag>
                </slot>
              </template>
              <div class="tui-tagList__input">
                <InputText
                  :ref="inputRef"
                  v-model="itemName"
                  :styleclass="{ transparent: true }"
                  :disabled="disabled"
                  :placeholder="placeholderText"
                  :aria-label="
                    $str(
                      'filter_x_taglist',
                      'totara_core',
                      labelName || $str('tag_list', 'totara_core')
                    )
                  "
                  @focus.native="!isOpen && toggle()"
                />
              </div>
            </div>
          </OverflowDetector>
          <span
            v-show="!isOpen && tags.length > visible"
            class="tui-tagList__suffix"
            >{{ $str('n_more', 'totara_core', tags.length - visible) }}</span
          >
        </div>
        <ButtonIcon
          ref="expandArrow"
          class="tui-tagList__expandArrow"
          :aria-expanded="isOpen.toString()"
          :aria-label="
            labelName
              ? $str('tag_list_x', 'totara_core', labelName)
              : $str('tag_list', 'totara_core')
          "
          aria-haspopup="menu"
          :disabled="disabled"
          :styleclass="{ transparent: true }"
          @click.stop.prevent="expandList(toggle, isOpen)"
        >
          <Expand custom-class="tui-tagList__caret" />
        </ButtonIcon>
      </div>
    </template>
    <Loader v-if="loading" :loading="true" class="tui-tagList__loading" />
    <template v-else-if="virtualScrollOptions">
      <VirtualScroll
        :data-key="virtualScrollOptions.dataKey"
        :data-list="items"
        :aria-label="virtualScrollOptions.ariaLabel"
        :is-loading="virtualScrollOptions.isLoading || false"
        :start="virtualScrollOptions.start"
        :offset="virtualScrollOptions.offset"
        :top-threshold="virtualScrollOptions.topThreshold"
        :bottom-threshold="virtualScrollOptions.bottomThreshold"
        :use-role="false"
        @scrolltop="onScrollToTop"
        @scrollbottom="onScrollToBottom"
      >
        <template v-slot:item="{ item, index }">
          <DropdownItem :key="index" @click="dropdownItemClicked(item, index)">
            <slot name="item" :item="item" :index="index" />
          </DropdownItem>
        </template>
        <template v-slot:footer>
          <div class="loader-wrapper">
            <Loader :loading="virtualScrollOptions.isLoading" />
          </div>
        </template>
      </VirtualScroll>
    </template>
    <template v-for="(item, index) in items" v-else>
      <DropdownItem :key="index" @click="dropdownItemClicked(item, index)">
        <slot name="item" :item="item" :index="index" />
      </DropdownItem>
    </template>
  </Dropdown>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import InputText from 'tui/components/form/InputText';
import Expand from 'tui/components/icons/Show';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Close from 'tui/components/icons/Close';
import Tag from 'tui/components/tag/Tag';
import OverflowDetector from 'tui/components/util/OverflowDetector';
import VirtualScroll from 'tui/components/virtualscroll/VirtualScroll';
import { validatePropObject } from 'tui/vue_util';
import Loader from 'tui/components/loading/Loader';
import { debounce } from '../../js/util';

export default {
  components: {
    ButtonIcon,
    Dropdown,
    DropdownItem,
    InputText,
    Expand,
    Close,
    Tag,
    OverflowDetector,
    VirtualScroll,
    Loader,
  },
  props: {
    labelName: String,
    disabled: {
      type: Boolean,
      default: false,
    },
    tags: Array,
    items: Array,
    filter: String,
    separator: {
      type: Boolean,
      default: false,
    },
    closeOnClick: {
      type: Boolean,
      default: false,
    },

    // Virtual scroll will be enabled when minimum options
    // are passed (dataKey, ariaLabel)
    virtualScrollOptions: {
      type: Object,
      validator: options => {
        if (!options) {
          return true;
        }

        const required = ['dataKey', 'ariaLabel'];
        const properties = {
          dataKey: 'string',
          ariaLabel: 'string',
          start: 'number',
          offset: 'number',
          topThreshold: 'number',
          bottomThreshold: 'number',
          isLoading: 'boolean',
        };

        return validatePropObject({ options, properties, required });
      },
    },
    inputPlaceholder: {
      type: String,
      default() {
        return this.$str('tag_list_placeholder', 'totara_core');
      },
    },

    debounceFilter: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      inputRef: 'tagListInput',
      clickedLabelName: '',
      itemName: this.filter || '',
      visible: Infinity,
      loading: false,
    };
  },

  computed: {
    placeholderText() {
      return this.tags.length === 0 ? this.inputPlaceholder : null;
    },
  },

  watch: {
    /**
     * Triggers things that need to happen when the taglist is updated
     */
    itemName() {
      this.loading = true;

      if (this.debounceFilter) {
        this.emitFilter(this);
      } else {
        this.$emit('filter', this.itemName);
      }
    },
    filter(value) {
      if (this.itemName != value) {
        this.itemName = value;
      }
    },

    /**
     * Once items have been changed, we're no longer loading
     */
    items() {
      this.loading = false;
    },
  },

  methods: {
    overflowChanged({ visible }) {
      this.visible = visible;
    },
    handleRemove(tag, index) {
      if (this.tags.length === 1) {
        this.focusInput();
      } else if (index == this.tags.length - 1) {
        this.$refs.tagIcon[index - 1].$el.focus();
      }

      this.$emit('remove', tag, index);
    },
    dropdownItemClicked(item, index) {
      this.$emit('select', item, index);
    },
    handleClick(toggle, isOpen) {
      // Open the dropdown when it's closed
      if (!isOpen) {
        // Reset the input value when opening the menu
        this.itemName = '';
        toggle();
      }

      this.focusInput();
    },
    expandList(toggle, isOpen) {
      toggle();

      // Focus on input after dropdown get opened
      if (!isOpen) {
        // Reset the input value when opening the menu
        this.itemName = '';
        this.focusInput();
      }
    },
    focusInput() {
      /**
       * 2 nextTick are required here as the focusInput should be triggered after the menu opened.
       * And detect the menu open status used one nextTicks already.
       */
      this.$nextTick(() => {
        this.$nextTick(() => {
          this.$refs[this.inputRef].$el.focus();
        });
      });
    },

    onScrollToTop() {
      this.$emit('scrolltop');
    },

    onScrollToBottom() {
      this.$emit('scrollbottom');
    },

    /**
     * Emits the filter event after a time delay to prevent excessive execution
     *
     * @param {Vue} that this Vue object
     */
    emitFilter: debounce(that => {
      that.$emit('filter', that.itemName);
    }, 500),

    /**
     * Sets the focus back to the input (and lets the browser determine where focus should go next)
     */
    setFocus() {
      this.$refs[this.inputRef].$el.focus();
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "filter_x_taglist",
    "n_more",
    "selected_tag_list_name",
    "tag_list",
    "tag_list_placeholder",
    "tag_list_x",
    "tag_remove",
    "tag_remove_from",
    "tags_selected"
  ]
}
</lang-strings>

<style lang="scss">
.tui-tagList {
  display: flex;
  align-items: flex-start;
  min-width: 230px;
  padding: var(--gap-2);
  border: var(--border-width-thin) solid var(--form-input-border-color);
  .tui-contextInvalid & {
    border-color: var(--form-input-border-color-invalid);
    box-shadow: var(--form-input-shadow-invalid);
  }

  &__tags {
    display: flex;
    align-items: center;
    width: 100%;
    min-width: 0;
    min-height: calc(var(--tag-height) + (2 * var(--border-width-thin)));
  }

  &__tagItems {
    display: flex;
    flex-grow: 1;
    align-items: center;
    min-width: 0;

    &--open {
      flex-wrap: wrap;
    }

    & > * {
      margin-right: var(--gap-1);
      margin-bottom: 0.2rem;
    }
  }

  &__suffix {
    @include tui-font-body-small();
    flex-shrink: 0;
    padding-right: var(--gap-2);
    padding-left: var(--gap-1);
    color: var(--color-state);
    white-space: nowrap;
    &:hover {
      cursor: pointer;
    }
  }

  &__input {
    flex-grow: 1;
    margin: -2px 0;
  }

  &__expandArrow {
    height: calc(var(--tag-height) + (2 * var(--border-width-thin)));
  }

  &__caret {
    fill: var(--color-neutral-7);
  }

  &__loading {
    margin: var(--gap-4);
  }
}
</style>
