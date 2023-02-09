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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_engage
-->

<template>
  <div
    class="tui-engageStarRating"
    :class="ratingClasses"
    @mouseleave="resetRating"
  >
    <template v-for="n in maxRating">
      <Star
        :key="n"
        class="tui-engageStarRating__star"
        :class="starClasses"
        :fill="fillLevel[n - 1]"
        :star-points="starPoints"
        :star-index="n"
        :rtl="rtl"
        :size="size"
        :read-only="readOnly"
        @star-selected="setRating($event, true)"
        @star-mouse-move="setRating"
      />
    </template>
    <span class="tui-engageStarRating__srOnly">{{
      $str('ratingsforscreenreader', 'totara_engage', rating)
    }}</span>
  </div>
</template>
<script>
import Star from 'totara_engage/components/icons/Star';

export default {
  components: {
    Star,
  },

  inheritAttrs: false,

  model: {
    prop: 'rating',
    event: 'rating-selected',
  },

  props: {
    increment: {
      type: Number,
      default: 1,
    },
    rating: {
      type: Number,
      default: 0,
    },
    maxRating: {
      type: Number,
      default: 5,
    },
    starPoints: {
      type: Array,
      default() {
        return [];
      },
    },
    readOnly: {
      type: Boolean,
      default: true,
    },
    inline: {
      type: Boolean,
      default: false,
    },
    rtl: {
      type: Boolean,
      default: false,
    },
    size: {
      type: Number,
      default: 15,
    },
  },

  data() {
    return {
      fillLevel: [],
      currentRating: 0,
      selectedRating: 0,
      ratingSelected: false,
    };
  },

  computed: {
    ratingClasses() {
      return [
        {
          'tui-engageStarRating--rtl': this.rtl,
          'tui-engageStarRating--inline': this.inline,
        },
      ];
    },
    starClasses() {
      return [
        {
          'tui-engageStarRating__star--pointer': !this.readOnly,
        },
      ];
    },
    starId() {
      return this.$id('engageRatingStar');
    },
  },

  watch: {
    rating(val) {
      this.currentRating = val;
      this.selectedRating = val;
      this.createStars();
    },
  },

  created() {
    this.currentRating = this.rating;
    this.selectedRating = this.currentRating;
    this.createStars();
  },

  methods: {
    setRating(event, persist) {
      if (this.readOnly) return;

      if (event.position) {
        const position = this.rtl
          ? (100 - event.position) / 100
          : event.position / 100;

        this.currentRating = Math.min(
          this.maxRating,
          Number(event.index + position - 1).toFixed(2)
        );
      } else {
        this.currentRating = Math.min(this.maxRating, event.index);
      }

      if (persist) {
        this.selectedRating = this.currentRating;
        this.$emit('rating-selected', this.selectedRating);
        this.ratingSelected = true;
      } else {
        this.$emit('current-rating', this.currentRating);
      }
      this.createStars();
    },
    resetRating() {
      if (!this.readOnly) {
        this.currentRating = this.selectedRating;
        this.createStars();
      }
    },
    createStars() {
      this.round();
      for (let i = 0; i < this.maxRating; i++) {
        let level = 0;
        if (i < this.currentRating) {
          level =
            this.currentRating - i > 1 ? 100 : (this.currentRating - i) * 100;
        }
        this.$set(this.fillLevel, i, Math.round(level));
      }
    },
    round() {
      const inv = 1.0 / this.increment;
      this.currentRating = Math.min(
        this.maxRating,
        Math.ceil(this.currentRating * inv) / inv
      );
    },
  },
};
</script>
<lang-strings>
  {
    "totara_engage": [
      "ratingsforscreenreader"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageStarRating {
  position: relative;
  display: flex;

  &--rtl {
    direction: rtl;
  }

  &--inline {
    display: inline-flex;
  }

  &__srOnly {
    @include sr-only();
  }

  &__star {
    &--pointer {
      cursor: pointer;
    }
  }
}

// IE11 is doing a poor job of handling flex here, and a min-height on child
// Nodes is not correcting things, so resort to simpler styles that achieve the
// same thing (in a deleteable style block).
.ie {
  .tui-engageStarRating {
    display: block;
    &__star {
      display: inline-block;
    }
  }
}
</style>
