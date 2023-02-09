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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module pathway_perform_rating
-->

<template>
  <div
    v-show="settings.enable_rating"
    class="tui-linkedReviewRatingFormPreview"
  >
    <Rating v-if="settings.enable_rating" :required="true">
      <template v-slot:form="{ titleId }">
        <div class="tui-linkedReviewRatingFormPreview__form">
          <Form input-width="full" :vertical="true">
            <FormSelect
              :aria-labelledby="titleId"
              :options="scaleValueOptions"
              char-length="15"
              :value="null"
            />
          </Form>
        </div>
      </template>
    </Rating>
  </div>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormSelect from 'tui/components/form/Select';
import Rating from 'pathway_perform_rating/components/Rating';

export default {
  components: {
    Form,
    FormSelect,
    Rating,
  },

  props: {
    settings: Object,
  },

  computed: {
    /**
     * Generate select list options with placeholder value
     *
     * @return {array}
     */
    scaleValueOptions() {
      let values = [
        {
          id: null,
          label: this.$str('rating_select', 'pathway_perform_rating'),
        },
      ];

      return values;
    },
  },
};
</script>

<lang-strings>
{
  "pathway_perform_rating": [
    "rating_select"
  ]
}
</lang-strings>
