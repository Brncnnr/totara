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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module mod_perform
-->
<template>
  <Grid :stack-at="764">
    <GridItem :units="4">
      <div>
        {{ $str('section_element_summary_required_questions', 'mod_perform') }}
      </div>
      <div class="tui-performActivitySectionElementSummary__count">
        {{ elementsSummary.required_question_count }}
      </div>
    </GridItem>
    <GridItem :units="4">
      <div>
        {{ $str('section_element_summary_optional_questions', 'mod_perform') }}
      </div>
      <div class="tui-performActivitySectionElementSummary__count">
        {{ elementsSummary.optional_question_count }}
      </div>
    </GridItem>
    <GridItem :units="4">
      <div>
        {{
          $str('section_element_summary_other_content_elements', 'mod_perform')
        }}
      </div>
      <div class="tui-performActivitySectionElementSummary__count">
        {{ elementsSummary.other_element_count }}
      </div>
    </GridItem>
  </Grid>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';

export default {
  components: {
    GridItem,
    Grid,
  },
  props: {
    elementsSummary: {
      type: Object,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
  "mod_perform": [
    "section_element_summary_required_questions",
    "section_element_summary_optional_questions",
    "section_element_summary_other_content_elements"
  ]
  }
</lang-strings>

<style lang="scss">
.tui-performActivitySectionElementSummary {
  &__count {
    @include tui-font-heavy;
  }
}
</style>
