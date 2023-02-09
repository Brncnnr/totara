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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module totara_competency
-->

<template>
  <FormRowStack spacing="large">
    <FormRow>
      <FormCheckbox name="enable_rating">
        {{ $str('enable_performance_rating', 'totara_competency') }}
        <HelpIcon
          class="tui-linkedReviewAdminEditCompetency__help"
          :desc-id="$id('rating-help')"
          :helpmsg="$str('enable_performance_rating_help', 'totara_competency')"
          size="md"
        />
      </FormCheckbox>
    </FormRow>

    <Field v-slot="{ value }" name="enable_rating">
      <SelectParticipant
        v-if="value"
        field-name="rating_relationship"
        :help-msg="
          $str(
            'enable_performance_rating_participant_help',
            'totara_competency'
          )
        "
        :label="
          $str('enable_performance_rating_participant', 'totara_competency')
        "
        :relationships="relationships"
      />
    </Field>
  </FormRowStack>
</template>

<script>
import Field from 'tui/components/reform/Field';
import { FormCheckbox, FormRow } from 'tui/components/uniform';
import FormRowStack from 'tui/components/form/FormRowStack';
import HelpIcon from 'tui/components/form/HelpIcon';
import SelectParticipant from 'performelement_linked_review/components/SelectParticipant';

export default {
  components: {
    Field,
    FormRowStack,
    FormCheckbox,
    FormRow,
    HelpIcon,
    SelectParticipant,
  },

  inheritAttrs: false,

  props: {
    relationships: Array,
  },
};
</script>

<lang-strings>
{
  "totara_competency": [
    "enable_performance_rating",
    "enable_performance_rating_help",
    "enable_performance_rating_participant",
    "enable_performance_rating_participant_help"
  ]
}
</lang-strings>

<style lang="scss">
.tui-linkedReviewAdminEditCompetency {
  &__help {
    padding-left: var(--gap-1);
  }
}
</style>
