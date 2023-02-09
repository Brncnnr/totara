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
  <div class="tui-playlistStarRating">
    <div class="tui-playlistStarRating__icon">
      <StarRating
        :read-only="true"
        :max-rating="5"
        :increment="0.1"
        :title="starTitle"
        :rating="showRating"
      />
    </div>
    <div>
      <span class="tui-playlistStarRating__rates">
        {{ showRatingCount }}
      </span>

      {{ showRates }}

      <PlaylistPopover
        v-if="showPopover"
        @rating="$emit('rating', Math.ceil(innerValue))"
      >
        <template v-slot:content>
          <StarRating
            v-model="innerValue"
            :read-only="false"
            :increment="1"
            :max-rating="5"
          />
        </template>
      </PlaylistPopover>
    </div>
  </div>
</template>

<script>
import PlaylistPopover from 'totara_playlist/components/popover/PlaylistPopover';
import StarRating from 'totara_engage/components/icons/StarRating';

export default {
  components: {
    PlaylistPopover,
    StarRating,
  },

  props: {
    owned: {
      type: Boolean,
      required: true,
    },

    count: {
      type: Number,
      required: true,
    },

    rating: {
      type: Number,
      required: true,
    },

    rated: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      innerValue: null,
    };
  },

  computed: {
    starTitle() {
      if (this.count <= 1) {
        return this.$str('numberofpersonrating', 'totara_engage', this.count);
      }

      return this.$str('numberofpeoplerating', 'totara_engage', this.count);
    },

    showRates() {
      if (this.count === 1 || this.count === 0) {
        return this.$str('rating', 'totara_playlist');
      }

      return this.$str('ratings', 'totara_playlist');
    },

    showRatingCount() {
      return this.count;
    },

    showRating() {
      return this.rating;
    },

    showPopover() {
      return !this.owned && !this.rated;
    },
  },
};
</script>
<lang-strings>
  {
    "totara_playlist": [
      "rating",
      "ratings"
    ],

    "totara_engage": [
      "numberofpeoplerating",
      "numberofpersonrating"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-playlistStarRating {
  .tui-engageStarIcon {
    width: var(--font-size-15);
    height: var(--font-size-15);

    &__filled {
      stop-color: var(--color-chart-background-2);
    }

    &__unfilled {
      stop-color: var(--color-neutral-1);
    }
  }

  &__rates {
    padding-right: var(--gap-1);
  }

  &__icon {
    margin-bottom: var(--gap-1);
  }
}
</style>
