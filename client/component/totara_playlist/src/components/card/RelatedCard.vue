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
  @package totara_playlist
-->

<template>
  <Card
    class="tui-playlistRelatedCard"
    :clickable="true"
    @click="handleClickCard"
  >
    <a :href="url" />
    <div class="tui-playlistRelatedCard__header" :style="imageStyle">
      <span v-if="imageAlt" class="sr-only">{{ imageAlt }}</span>
      <span class="tui-playlistRelatedCard__resourceCount">
        {{ resources }}
      </span>
    </div>

    <section class="tui-playlistRelatedCard__content">
      <span>{{ name }}</span>
      <span>{{ fullname }}</span>
      <StarRating
        :rating="rating"
        :read-only="true"
        :increment="0.1"
        :max-rating="5"
      />
    </section>
    <BookmarkButton
      size="300"
      :bookmarked="innerBookmarked"
      :primary="false"
      :circle="false"
      :small="true"
      :transparent="true"
      class="tui-playlistRelatedCard__bookmark"
      @click="handleClickBookmark"
    />
  </Card>
</template>

<script>
import Card from 'tui/components/card/Card';

import BookmarkButton from 'totara_engage/components/buttons/BookmarkButton';
import StarRating from 'totara_engage/components/icons/StarRating';

export default {
  components: {
    BookmarkButton,
    Card,
    StarRating,
  },

  props: {
    bookmarked: {
      type: Boolean,
      default: false,
    },
    fullname: {
      type: String,
      required: true,
    },
    image: {
      type: String,
      required: true,
    },
    imageAlt: String,
    name: {
      type: String,
      required: true,
    },
    playlistId: {
      type: [Number, String],
      required: true,
    },
    rating: {
      type: Number,
      required: true,
    },
    resources: {
      type: Number,
      required: true,
    },
    url: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      innerBookmarked: this.bookmarked,
    };
  },

  computed: {
    imageStyle() {
      return {
        backgroundImage: `url(${this.image}})`,
      };
    },
  },

  methods: {
    handleClickBookmark() {
      this.innerBookmarked = !this.innerBookmarked;
      this.$emit('update', this.playlistId, this.innerBookmarked);
    },
    handleClickCard() {
      window.location.href = this.url;
    },
  },
};
</script>

<style lang="scss">
.tui-playlistRelatedCard {
  display: flex;
  min-width: 120px;
  height: var(--engage-sidepanel-card-height);
  background-color: var(--color-neutral-1);

  &__header {
    @include card-header-image(
      var(--engage-sidepanel-card-height),
      var(--engage-sidepanel-card-height),
      null,
      'horizontal'
    );
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--color-primary);
    border-top-left-radius: var(--border-radius-normal);
    border-bottom-left-radius: var(--border-radius-normal);
  }

  &__resourceCount {
    @include tui-font-heading-label-small();
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background-color: var(--color-neutral-1);
    border-radius: 50%;
  }

  &__content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    margin-left: var(--gap-2);
    padding: var(--gap-4) 0 var(--gap-2) 0;
    overflow: hidden;

    & > * {
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
    }

    > :first-child {
      @include tui-font-heading-label-small();
      color: inherit;
      text-decoration: none;
    }

    > :nth-child(2) {
      @include tui-font-body-x-small();
    }

    > :last-child {
      margin-top: auto;
      margin-bottom: 0;

      .tui-engageStarIcon {
        width: var(--font-size-14);
        height: var(--font-size-14);

        &__filled {
          stop-color: var(--color-chart-background-2);
        }

        &__unfilled {
          stop-color: var(--color-neutral-1);
        }
      }
    }
  }

  &__bookmark {
    align-self: flex-start;
    // neutralize the default icon padding
    margin-top: -2px;
  }
}
</style>
