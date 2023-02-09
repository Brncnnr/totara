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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-participantUserHeader">
    <Avatar
      :src="profilePicture"
      :size="size"
      :alt="profilePictureAlt"
      :aria-hidden="true"
      class="tui-participantUserHeader__avatar"
    />
    <div
      class="tui-participantUserHeader__name"
      :class="{
        'tui-participantUserHeader__name--regular': regularWeight,
      }"
    >
      {{ userName }}
    </div>
  </div>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';

export default {
  components: {
    Avatar,
  },

  props: {
    profilePicture: String,
    profilePictureAlt: String,
    regularWeight: Boolean,
    size: String,
    userName: String,
  },
};
</script>

<style lang="scss">
.tui-participantUserHeader {
  display: flex;
  align-items: center;

  &__avatar {
    flex-shrink: 0;
  }

  &__name {
    @include tui-font-heading-x-small();
    margin-left: var(--gap-2);

    &--regular {
      font-weight: normal;
    }
  }
}
</style>
