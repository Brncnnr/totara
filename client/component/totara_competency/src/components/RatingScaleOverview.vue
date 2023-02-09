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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module totara_competency
-->

<template>
  <div class="tui-competencyRatingScaleOverview">
    <p
      :id="$id('rating-scale-title')"
      class="tui-competencyRatingScaleOverview__title"
    >
      {{ $str('rating_scale', 'totara_competency') }}
    </p>
    <ol
      class="tui-competencyRatingScaleOverview__list"
      :aria-labelledby="$id('rating-scale-title')"
    >
      <li
        v-for="scaleValue in scaleValues"
        :key="scaleValue.id"
        class="tui-competencyRatingScaleOverview__list-item"
      >
        <div class="tui-competencyRatingScaleOverview__proficientStatus">
          <CheckSuccess v-if="scaleValue.proficient" size="200" />
        </div>
        <div class="tui-competencyRatingScaleOverview__scaleValue">
          <p class="tui-competencyRatingScaleOverview__scaleValue-name">
            {{ scaleValue.name }}
          </p>
          <p
            v-if="showDescription(scaleValue.description)"
            class="tui-competencyRatingScaleOverview__scaleValue-description"
            v-html="scaleValue.description"
          />
          <span class="sr-only">
            {{
              scaleValue.proficient
                ? $str('is_proficient_value', 'totara_competency')
                : $str('is_not_proficient_value', 'totara_competency')
            }}
          </span>
        </div>
      </li>
    </ol>
  </div>
</template>

<script>
import CheckSuccess from 'tui/components/icons/CheckSuccess';

export default {
  components: { CheckSuccess },
  props: {
    scale: {
      required: true,
      type: Object,
    },
    showDescriptions: {
      type: Boolean,
      default: false,
    },
    reverseValues: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    /**
     * The values we want.
     * @returns {Array}
     */
    scaleValues() {
      if (this.reverseValues) {
        return this.scale.values.slice(0).reverse();
      } else {
        return this.scale.values;
      }
    },
  },

  methods: {
    /**
     * Do we show this description?
     * @param {String} description
     * @returns {boolean}
     */
    showDescription(description) {
      return (
        this.showDescriptions && description != null && description.length > 0
      );
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "is_not_proficient_value",
      "is_proficient_value",
      "rating_scale"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyRatingScaleOverview {
  &__title {
    margin: 0;
    @include tui-font-heading-x-small;
  }

  &__list {
    margin: var(--gap-4) 0 0;

    & > * + * {
      margin-top: var(--gap-2);
    }

    &-item {
      display: flex;
    }
  }

  &__proficientStatus {
    min-width: var(--gap-8);

    .flex-icon {
      position: relative;
      top: -1px;
    }
  }

  &__scaleValue {
    display: flex;
    flex-direction: column;

    &-name {
      @include tui-font-heading-label;
      margin: 0;
    }

    &-description {
      margin: var(--gap-1) 0 0 var(--gap-3);
    }
  }
}
</style>
