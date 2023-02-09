<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module mod_approval
-->
<!-- TODO TL-33917: shift to core -->
<template>
  <div
    :class="[
      'tui-mod_approval-microProfileCard',
      'tui-mod_approval-microProfileCard--' + size,
    ]"
  >
    <Avatar :src="avatarUrl" :alt="avatarAlt" :size="size" />
    <div class="tui-mod_approval-microProfileCard__profile">
      <span v-if="readOnly" :class="fullNameClass">{{ fullName }}</span>
      <a v-else :href="profileUrl" :class="fullNameClass">
        {{ fullName }}
      </a>
      <a
        v-if="email"
        :href="mailTo"
        class="tui-mod_approval-microProfileCard__email"
        >{{ email }}</a
      >
    </div>
  </div>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';
import {
  getProfileUrl,
  validateForProfile,
} from 'mod_approval/item_selectors/user';

export default {
  name: 'MicroProfileCard',
  components: {
    Avatar,
  },
  props: {
    size: {
      type: String,
      default: 'xsmall',
    },
    readOnly: {
      type: Boolean,
      default: false,
    },
    user: {
      type: Object,
      required: true,
      validator: value => validateForProfile(value),
    },
    name: {
      type: String,
      required: false,
      default: '',
    },
    showEmail: {
      type: Boolean,
      default: false,
    },
    emphasiseName: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    display() {
      return this.user.card_display || {};
    },
    avatarUrl() {
      return (
        this.display.profile_picture_url ||
        this.user.profileimageurl ||
        this.user.profileimageurlsmall
      );
    },
    avatarAlt() {
      return this.display.profile_picture_alt || '';
    },
    fullName() {
      return this.name || this.user.fullname;
    },
    email() {
      if (!this.showEmail) {
        return '';
      }
      return this.user.email || '';
    },
    mailTo() {
      if (!this.email) {
        return '';
      }
      return 'mailto:' + this.email;
    },
    profileUrl() {
      return getProfileUrl(this.user);
    },
    fullNameClass() {
      const prefix = 'tui-mod_approval-microProfileCard__name';
      return {
        [prefix]: true,
        [prefix + '--link']: !this.readOnly,
        [prefix + '--primary']: this.showEmail || this.emphasiseName,
      };
    },
  },
};
</script>

<style lang="scss">
.tui-mod_approval-microProfileCard {
  display: flex;
  align-items: center;

  &__profile {
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin-left: var(--gap-2);
  }
  &__name--primary {
    font-weight: bold;
  }
  // We don't want to see a hyphenated email address.
  &__email {
    hyphens: none;
  }
}
</style>
