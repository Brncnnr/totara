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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @module totara_competency
-->

<template>
  <Popover v-if="scale" :triggers="['click']">
    <template v-slot:trigger>
      <Button :styleclass="{ reveal: true }" :text="value.name" />
    </template>
    <RatingScaleOverview :scale="scale" :reverse-values="true" />
  </Popover>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Popover from 'tui/components/popover/Popover';
import RatingScaleOverview from 'totara_competency/components/RatingScaleOverview';

export default {
  components: {
    Button,
    Popover,
    RatingScaleOverview,
  },
  props: {
    value: {
      required: true,
      type: Object,
    },
    ratingScale: Object,
    /**
     * This property has been deprecated, please use scale instead.
     * @deprecated since Totara 14.0
     */
    scales: {
      type: Array,
      validator() {
        console.warn(
          'The "scales" prop has been deprecated, please use the "rating-scale" prop instead.'
        );
        return true;
      },
    },
  },
  computed: {
    /**
     * This computed property has been deprecated,
     * this exists for backwards compatibility with the scales prop.
     * @deprecated since Totara 14.0
     */
    scale() {
      if (this.scales) {
        return this.scales.find(({ id }) => id === this.value.scale_id);
      }

      return this.ratingScale;
    },
  },
  /**
   * This exists for backwards compatibility with the scales prop,
   * rating-scale should be made required once removed.
   * @deprecated since Totara 14.0
   */
  created() {
    if (!this.scales && !this.ratingScale) {
      console.warn('Either scale or scales prop must be supplied.');
    }
  },
};
</script>
