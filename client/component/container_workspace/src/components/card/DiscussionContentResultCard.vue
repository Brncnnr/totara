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

  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @module container_workspace
-->
<template>
  <div class="tui-workspaceDiscussionContentResultCard" :data-id="id">
    <div class="tui-workspaceDiscussionContentResultCard__card">
      <DiscussionAvatar
        :image-src="creatorImageSrc"
        :image-alt="creatorImageAlt"
        :profile-url="profileUrl"
        class="tui-workspaceDiscussionContentResultCard__avatar"
      />

      <div class="tui-workspaceDiscussionContentResultCard__content">
        <div class="tui-workspaceDiscussionContentResultCard__head">
          <a :id="labelId" :href="profileUrl">
            {{ creatorFullname }}
          </a>
          <p>{{ timeDescription }}</p>
        </div>
        <div
          ref="result-content"
          class="tui-workspaceDiscussionContentResultCard__body"
          v-html="discussionContent"
        />
      </div>
      <div class="tui-workspaceDiscussionContentResultCard__actions">
        <ActionLink
          :href="discussionUrl"
          :text="$str('view', 'core')"
          :styleclass="{ small: true }"
        />
      </div>
    </div>
  </div>
</template>

<script>
import tui from 'tui/tui';
import ActionLink from 'tui/components/links/ActionLink';
import DiscussionAvatar from 'container_workspace/components/profile/DiscussionAvatar';

// GraphQL queries

export default {
  components: {
    ActionLink,
    DiscussionAvatar,
  },

  props: {
    id: {
      type: [String, Number],
      required: true,
    },

    creatorImageSrc: {
      type: String,
      required: true,
    },

    creatorImageAlt: {
      type: String,
      default: '',
    },

    /**
     * The discussion's creator' id - in short user's id of this discussion's creator.
     */
    creatorId: {
      type: [String, Number],
      required: true,
    },

    creatorFullname: {
      type: String,
      required: true,
    },

    discussionContent: {
      type: String,
      required: true,
    },

    timeDescription: {
      type: String,
      required: true,
    },

    discussionId: {
      type: [String, Number],
      required: true,
    },

    labelId: String,
  },

  computed: {
    profileUrl() {
      return this.$url('/user/profile.php', { id: this.creatorId });
    },
    /**
     * Returning the url to single discussion page.
     * @return {String}
     */
    discussionUrl() {
      return this.$url('/container/type/workspace/discussion.php', {
        id: this.discussionId,
      });
    },
  },

  mounted() {
    this.$_scan();
  },

  updated() {
    this.$_scan();
  },

  methods: {
    $_scan() {
      if (!this.$refs['result-content']) {
        return;
      }

      let element = this.$refs['result-content'];
      tui.scan(element);
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "view"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceDiscussionContentResultCard {
  display: flex;
  flex-direction: column;
  padding-top: var(--gap-4);
  color: var(--color-neutral-7);
  border: var(--border-width-thin) solid var(--color-neutral-5);

  &:hover,
  &:focus {
    background-color: var(--color-neutral-3);
  }

  &__card {
    display: flex;
    padding: var(--gap-4);
    padding-top: 0;
  }

  &__avatar {
    margin-right: var(--gap-2);
  }

  &__content {
    flex-grow: 1;
    width: 100%;
  }

  &__head {
    display: flex;
    flex: 1;
    align-items: flex-end;
    margin-bottom: var(--gap-4);
    padding-top: 2px;

    a {
      @include tui-font-body();
      color: var(--color-state);
      font-weight: bold;
    }

    p {
      @include tui-font-body-x-small();
      margin: 0;
      margin-left: var(--gap-2);
      color: var(--color-neutral-7);
    }
  }

  &__actions {
    display: flex;

    &-comment {
      display: flex;
      margin-left: var(--gap-4);
    }
  }
}
</style>
