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

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @package totara_core
-->

<template>
  <div v-if="hasContent" class="tui-linkedReviewViewCurrentLearning">
    <div v-if="!preview" class="tui-linkedReviewViewCurrentLearning__date">
      {{ createdAt }}
    </div>
    <Card
      class="tui-linkedReviewViewCurrentLearning__card"
      :clickable="!preview"
      @click="open"
    >
      <div
        class="tui-linkedReviewViewCurrentLearning__image"
        :style="'background-image: url(' + content.image_src + ');'"
      />

      <div class="tui-linkedReviewViewCurrentLearning__content">
        <h3 class="tui-linkedReviewViewCurrentLearning__content-heading">
          <a v-if="!preview" :href="content.url_view" target="_blank">{{
            content.fullname
          }}</a>
          <template v-else>{{ content.fullname }}</template>
        </h3>

        <div
          class="tui-linkedReviewViewCurrentLearning__content-description"
          v-html="content.description"
        />

        <span class="tui-linkedReviewViewCurrentLearning__content-type">
          {{ typeName }}
        </span>
      </div>
      <div class="tui-linkedReviewViewCurrentLearning__chart">
        <PercentageDoughnut
          v-if="content.progress !== null"
          v-bind="chartProps"
        />
        <div v-else class="tui-linkedReviewViewCurrentLearning__chart-text">
          {{ $str('statusnottracked', 'core_completion') }}
        </div>
      </div>
    </Card>
  </div>
  <p
    v-else
    class="tui-linkedReviewViewCurrentLearning tui-linkedReviewViewCurrentLearning--deleted"
  >
    {{ $str('learning_removed', 'totara_core') }}
  </p>
</template>

<script>
import Card from 'tui/components/card/Card';
import PercentageDoughnut from 'tui_charts/components/PercentageDoughnut';

export default {
  components: {
    Card,
    PercentageDoughnut,
  },

  /**
   * These props are setup in ParticipantContentPicker.
   */
  props: {
    content: {
      type: Object,
    },
    createdAt: String,
    fromPrint: Boolean,
    isExternalParticipant: Boolean,
    preview: Boolean,
    subjectUser: Object,
  },

  computed: {
    chartProps() {
      return {
        percentage: this.content.progress,
        showPercentage: true,
        percentageFontSize: 13,
        cutout: 80,
        square: true,
      };
    },

    /**
     * Returns the human readable name for the learning type
     *
     * @returns {String}
     */
    typeName() {
      if (!this.content.itemtype) {
        return this.$str('learning_type_unknown', 'totara_core');
      }

      let typeName = this.$str(
        'learning_type_' + this.content.itemtype,
        'totara_core'
      );

      if (!typeName) {
        typeName = this.$str('learning_type_unknown', 'totara_core');
      }

      return typeName;
    },

    /**
     * Does this  learning item have content
     *
     * @returns bool whether the "no content" message needs to be displayed
     */
    hasContent() {
      return this.content.id || this.content.itemtype;
    },
  },

  methods: {
    open() {
      window.open(this.content.url_view);
    },
  },
};
</script>

<style lang="scss">
.tui-linkedReviewViewCurrentLearning {
  & > * + * {
    margin-top: var(--gap-2);
  }

  &__date {
    @include tui-font-body-small();
  }

  &__card {
    position: relative;
    flex-direction: column;
    padding: var(--gap-2);
    background-color: var(--color-neutral-1);

    @media (min-width: $tui-screen-sm) {
      flex-direction: row;
    }
  }

  &__image {
    display: block;
    flex-grow: 0;
    flex-shrink: 0;
    width: 9.6rem;
    height: 8.4rem;
    background-repeat: no-repeat;
    background-position: 50%;
    background-size: cover;
  }

  &__content {
    display: flex;
    flex-basis: 50%;
    flex-direction: column;
    flex-grow: 1;
    flex-shrink: 0;
    clear: both;
    padding: var(--gap-4) 0;

    @media (min-width: $tui-screen-sm) {
      padding: 0 var(--gap-4);
    }

    &-heading {
      @include tui-font-heading-x-small();
      margin: 0;
    }

    &-description {
      @include tui-font-body();
      flex-grow: 1;
    }

    &-type {
      @include tui-font-body-small();
      margin-top: var(--gap-2);
      color: var(--color-neutral-6);
    }
  }

  &__chart {
    position: absolute;
    top: var(--gap-2);
    right: 0;
    flex-basis: 6.6rem;
    flex-grow: 0;
    flex-shrink: 0;
    width: 6.6rem;
    height: 6.6rem;
    margin-right: var(--gap-2);

    // Use normal flex positioning for larger screens
    @media (min-width: $tui-screen-sm) {
      position: relative;
      top: 0;
    }

    &-text {
      @include tui-font-heading-label();

      @media (min-width: $tui-screen-sm) {
        margin-top: var(--gap-5);
      }
    }
  }
}
</style>

<lang-strings>
{
  "core_completion": ["statusnottracked"],
  "totara_core": [
    "learning_removed",
    "learning_type_certification",
    "learning_type_course",
    "learning_type_program",
    "learning_type_unknown"
  ]
}
</lang-strings>
