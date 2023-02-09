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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module engage_survey
-->

<template>
  <div class="tui-engageSurveyAuthor">
    <Avatar :src="profileImageUrl" :alt="profileImageAlt" size="xxsmall" />

    <a
      :href="$url('/user/profile.php', { id: userId })"
      class="tui-engageSurveyAuthor__userLink"
    >
      {{ fullname }}
    </a>
  </div>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';

export default {
  components: {
    Avatar,
  },

  props: {
    userId: {
      required: true,
      type: [Number, String],
    },

    fullname: {
      required: true,
      type: String,
    },

    profileImageUrl: {
      required: true,
      type: String,
    },

    profileImageAlt: {
      required: true,
      type: String,
    },
  },
};
</script>

<style lang="scss">
.tui-engageSurveyAuthor {
  display: flex;
  align-items: center;

  &__userLink {
    margin-left: var(--gap-2);
    font-size: var(--font-size-13);
  }
}
</style>
