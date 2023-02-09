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
  @module totara_comment
-->
<template>
  <div class="tui-commentResponseBox">
    <div v-if="!$apollo.loading" class="tui-commentResponseBox__profilePicture">
      <Avatar
        :image-alt="user.profileimagealt || ''"
        :image-src="user.profileimageurl"
        :profile-url="$url('/user/profile.php', { id: user.id })"
      />
    </div>

    <div class="tui-commentResponseBox__formBox">
      <slot />
    </div>
  </div>
</template>

<script>
import Avatar from 'totara_comment/components/profile/CommentAvatar';

// GraphQl query
import getActor from 'totara_comment/graphql/get_actor';

export default {
  components: {
    Avatar,
  },

  apollo: {
    user: {
      query: getActor,
    },
  },

  data() {
    return {
      user: {},
    };
  },
};
</script>

<style lang="scss">
.tui-commentResponseBox {
  display: flex;
  align-content: flex-start;

  &__profilePicture {
    margin-right: var(--gap-2);
  }

  &__formBox {
    flex: 1 1 auto;
    width: 100%;
    @include tui-wordbreak--hyphens;
  }
}
</style>
