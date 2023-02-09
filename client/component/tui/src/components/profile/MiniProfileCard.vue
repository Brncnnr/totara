<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-miniProfileCard"
    :class="{
      'tui-miniProfileCard--border': !noBorder,
      'tui-miniProfileCard--hasShadow': hasShadow,
      'tui-miniProfileCard--no-padding': noPadding,
      'tui-miniProfileCard--horizontal': horizontal,
      'tui-miniProfileCard--no-avatar': !noBorder && !hasAvatar,
      'tui-miniProfileCard--no-dropdown': !noBorder && !hasDropDown,
    }"
  >
    <!-- Only displaying avatar picture if there are no urls -->
    <template v-if="hasAvatar">
      <a
        v-if="!readOnly && profileUrl"
        :href="profileUrl"
        :aria-describedby="ariaDescribedby"
        class="tui-miniProfileCard__avatar"
        :aria-hidden="avatarAlt === ''"
        :tabindex="avatarAlt === '' ? '-1' : ''"
      >
        <Avatar
          :src="avatarSrc"
          :alt="avatarAlt"
          :size="horizontal ? 'xxsmall' : 'xsmall'"
        />
      </a>

      <Avatar
        v-else
        :src="avatarSrc"
        :alt="avatarAlt"
        :size="horizontal ? 'xxsmall' : 'xsmall'"
        class="tui-miniProfileCard__avatar"
      />
    </template>

    <div
      class="tui-miniProfileCard__description"
      :class="{
        'tui-miniProfileCard__description--horizontal': horizontal,
        ['tui-miniProfileCard__description' + numberOfFieldsClass]: horizontal,
      }"
    >
      <template v-for="({ value, url }, index) in displayFields">
        <template v-if="horizontal || !!value">
          <div
            :key="index"
            class="tui-miniProfileCard__row"
            :class="{
              'tui-miniProfileCard__row--withGap': index === 1 && !horizontal,
            }"
          >
            <p
              v-if="!url || readOnly"
              class="tui-miniProfileCard__row-text"
              :class="{
                'tui-miniProfileCard__row-text--bold': index === 0,
              }"
            >
              {{ value }}
            </p>

            <a
              v-else
              :id="labelId"
              :href="url"
              class="tui-miniProfileCard__row-link"
              :class="{
                'tui-miniProfileCard__row-link--bold': index === 0,
              }"
              :aria-describedby="ariaDescribedby"
            >
              {{ value }}
            </a>

            <template v-if="index == 0">
              <!-- Add support for tag on the first section. -->
              <slot name="tag" />
            </template>
          </div>
        </template>
      </template>
    </div>

    <Dropdown
      v-if="hasDropDown"
      :position="dropDownPosition"
      class="tui-miniProfileCard__dropDown"
    >
      <template v-slot:trigger="{ toggle, isOpen }">
        <ButtonIcon
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="dropDownAriaLabel"
          :styleclass="{ transparentNoPadding: true, small: true }"
          @click="toggle"
        >
          <More :size="buttonIconSize" />
        </ButtonIcon>
      </template>

      <slot name="drop-down-items" />
    </Dropdown>
  </div>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';
import Dropdown from 'tui/components/dropdown/Dropdown';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import More from 'tui/components/icons/More';

export default {
  components: {
    Avatar,
    Dropdown,
    ButtonIcon,
    More,
  },

  props: {
    dropDownPosition: {
      type: String,
      default: 'bottom-right',
    },
    buttonIconSize: String,
    dropDownButtonAriaLabel: String,
    noBorder: Boolean,
    noPadding: Boolean,
    horizontal: Boolean,
    hasShadow: Boolean,
    display: {
      type: Object,
      required: true,
      validator(display) {
        if (
          !('profile_picture_url' in display) ||
          !('profile_picture_alt' in display) ||
          !('profile_url' in display) ||
          !('display_fields' in display)
        ) {
          return false;
        }

        const { display_fields: fields } = display;
        return Array.prototype.every.call(fields, function(field) {
          // Only looking for 'value' and 'associate_url' for now.
          return 'value' in field && 'associate_url' in field;
        });
      },
    },
    readOnly: Boolean,
    ariaDescribedby: String,
    labelId: String,
  },

  computed: {
    /**
     * Normalise the display fields with just associated url and the value.
     *
     * @return {Array}
     */
    displayFields() {
      let { display_fields: fields } = this.display;

      // Remove empty fields without a label
      if (this.horizontal) {
        fields = fields.filter(x => x.label);
      }

      return fields.map(function({ value, associate_url }) {
        return {
          value: value,
          url: associate_url,
        };
      });
    },

    /**
     * Provide a string for class modifier based on number of display items
     *
     * @return {String}
     */
    numberOfFieldsClass() {
      return '--horizontalItems-' + this.displayFields.length;
    },

    /**
     * @return {String|null}
     */
    avatarSrc() {
      return this.display.profile_picture_url;
    },

    /**
     *
     * @return {String}
     */
    avatarAlt() {
      if (!this.display.profile_picture_alt) {
        return '';
      }

      return this.display.profile_picture_alt;
    },

    /**
     * @return {Boolean}
     */
    hasDropDown() {
      return !!this.$scopedSlots['drop-down-items'];
    },

    /**
     *
     * @return {Boolean}
     */
    hasAvatar() {
      return !!this.avatarSrc;
    },

    /**
     *
     * @return {String}
     */
    dropDownAriaLabel() {
      if (this.dropDownButtonAriaLabel) {
        return this.dropDownButtonAriaLabel;
      }

      return this.$str('actions', 'core');
    },

    profileUrl() {
      if (!this.display.profile_url) {
        return false;
      }

      return this.display.profile_url;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "actions"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-miniProfileCard {
  // The parent who uses this card decides the width/height.
  display: flex;
  align-items: flex-start;
  padding: var(--gap-2);
  outline: none;

  &--no-avatar {
    padding-left: var(--gap-4);
  }

  &--no-dropdown {
    padding-right: var(--gap-4);
  }

  &--no-padding {
    // Reset padding to zero.
    padding: 0;
  }

  &--border {
    border: var(--border-width-thin) solid var(--color-neutral-5);
    border-radius: var(--border-radius-normal);
  }

  &--hasShadow {
    box-shadow: var(--shadow-2);
  }

  &__avatar {
    margin-right: var(--gap-2);
  }

  &--horizontal {
    align-items: center;
  }

  &__description {
    display: flex;
    flex: 1;
    flex-direction: column;
    overflow: hidden;

    &--horizontal {
      flex-direction: row;
      justify-content: space-between;

      & > * + * {
        padding-left: var(--gap-4);
      }
    }

    &--horizontalItems-1 {
      & > * {
        width: 100%;
      }
    }

    &--horizontalItems-2 {
      & > * {
        width: 50%;
      }
    }

    &--horizontalItems-3 {
      & > * {
        width: 33%;
      }
    }

    &--horizontalItems-4 {
      & > * {
        width: 25%;
      }
    }
  }

  &__row {
    display: flex;
    align-items: center;

    &-text {
      @include tui-font-body-small();
      margin: 0;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;

      &--bold {
        @include tui-font-heavy();
      }
    }

    &-link {
      @include tui-font-link-small();
      margin: 0;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;

      &--bold {
        @include tui-font-heavy();
      }
    }

    &--withGap {
      margin-bottom: var(--gap-1);
    }
  }

  &__dropDown {
    margin-left: var(--gap-4);
  }
}
</style>
