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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module performelement_competency_rating
-->

<template>
  <div class="tui-competencyRatingAdminEdit">
    <PerformAdminCustomElementEdit
      :initial-values="initialValues"
      :settings="settings"
      @cancel="$emit('display')"
      @update="$emit('update', $event)"
    >
      <!-- Enable description -->
      <FormRow>
        <FormCheckbox name="scaleDescriptionsEnabled">
          {{
            $str(
              'include_rating_scale_descriptions',
              'performelement_competency_rating'
            )
          }}
        </FormCheckbox>
      </FormRow>
    </PerformAdminCustomElementEdit>
  </div>
</template>

<script>
import { FormCheckbox, FormRow } from 'tui/components/uniform';
import PerformAdminCustomElementEdit from 'mod_perform/components/element/PerformAdminCustomElementEdit';

export default {
  components: {
    FormCheckbox,
    FormRow,
    PerformAdminCustomElementEdit,
  },

  inheritAttrs: false,

  props: {
    data: Object,
    identifier: String,
    isRequired: Boolean,
    rawTitle: String,
    settings: Object,
  },

  data() {
    return {
      initialValues: {
        rawTitle: this.rawTitle,
        identifier: this.identifier,
        responseRequired: this.isRequired,
        scaleDescriptionsEnabled: this.data.scaleDescriptionsEnabled,
      },
    };
  },
};
</script>

<lang-strings>
{
  "performelement_competency_rating": [
    "include_rating_scale_descriptions"
  ]
}
</lang-strings>
