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
  @module totara_playlist
-->
<template>
  <div class="tui-summaryPlaylistCard">
    <div class="tui-summaryPlaylistCard__top">
      <a :href="url" class="tui-summaryPlaylistCard__title">
        {{ name }}
      </a>

      <StarRating
        :read-only="true"
        :max-rating="5"
        :increment="1"
        :rating="rating"
        :title="ratingTitle"
        class="tui-summaryPlaylistCard__stars"
      />
    </div>

    <p class="tui-summaryPlaylistCard__author">
      {{ $str('by_author', 'totara_playlist', author) }}
    </p>
  </div>
</template>

<script>
import StarRating from 'totara_engage/components/icons/StarRating';

export default {
  components: {
    StarRating,
  },

  props: {
    playlistId: {
      type: [Number, String],
      required: true,
    },

    name: {
      type: String,
      required: true,
    },

    url: {
      type: String,
      default() {
        return this.$url('/totara/playlist/index.php', { id: this.playlistId });
      },
    },

    author: {
      type: String,
      required: true,
    },

    rating: {
      type: [String, Number],
      required: true,
    },

    ratedCount: {
      type: [String, Number],
      required: true,
    },
  },

  computed: {
    /**
     * @return {String}
     */
    ratingTitle() {
      if (this.ratedCount <= 1) {
        return this.$str(
          'numberofpersonrating',
          'totara_engage',
          this.ratedCount
        );
      }

      return this.$str(
        'numberofpeoplerating',
        'totara_engage',
        this.ratedCount
      );
    },
  },
};
</script>

<lang-strings>
  {
    "totara_playlist": [
      "by_author"
    ],

    "totara_engage": [
      "numberofpeoplerating",
      "numberofpersonrating"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-summaryPlaylistCard {
  display: flex;
  flex-direction: column;
  align-items: flex-start;

  &__top {
    display: flex;
    width: 100%;

    // Overriding the star rating
    .tui-engageStarIcon {
      width: var(--font-size-15);
      height: var(--font-size-14);

      &__filled {
        stop-color: var(--color-chart-background-2);
      }

      &__unfilled {
        stop-color: var(--color-neutral-1);
      }
    }
  }

  &__title {
    @include tui-font-link-small();
    @include tui-font-heavy();
    flex: 1;
    margin: 0;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__author {
    @include tui-font-body-x-small();
    margin: 0;
    margin-top: var(--gap-1);
  }
}
</style>
