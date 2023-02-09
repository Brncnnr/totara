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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->

<template>
  <div>
    <span v-if="anonymousResponses">
      <strong>{{ $str('all_responses_anonymised', 'mod_perform') }}</strong>
    </span>
    <Loader v-else :loading="$apollo.loading">
      <SelectFilter
        :value="value"
        :label="$str('responses_by_relationship', 'mod_perform')"
        :options="options"
        :show-label="true"
        :stacked="true"
        @input="$emit('input', $event)"
      />
    </Loader>
  </div>
</template>

<script>
import SelectFilter from 'tui/components/filters/SelectFilter';
import Loader from 'tui/components/loading/Loader';
import relationshipsInvolvedInActivityQuery from 'mod_perform/graphql/responding_relationships_involved_in_subject_instance';

export default {
  components: {
    Loader,
    SelectFilter,
  },
  props: {
    value: {
      type: String,
      default: null,
    },
    subjectInstanceId: {
      type: [Number, String],
      required: true,
    },
    anonymousResponses: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      relationships: [],
    };
  },
  computed: {
    options() {
      const options = this.relationships.map(relationship => {
        return {
          id: relationship.name,
          label: relationship.name,
        };
      });

      options.unshift({
        id: null,
        label: this.$str(
          'all_relationships_select_option_label',
          'mod_perform'
        ),
      });

      return options;
    },
  },
  apollo: {
    relationships: {
      query: relationshipsInvolvedInActivityQuery,
      variables() {
        return {
          subject_instance_id: this.subjectInstanceId,
        };
      },
      update(data) {
        const relationships =
          data[
            'mod_perform_responding_relationships_involved_in_subject_instance'
          ];

        // If the value is not in one of the relationships, reset it to null ("All").
        if (
          this.value !== null &&
          !relationships.some(relationship => relationship.name === this.value)
        ) {
          this.$emit('input', null);
        }

        return relationships;
      },
    },
  },
};
</script>

<lang-strings>
{
  "mod_perform": [
    "all_relationships_select_option_label",
    "all_responses_anonymised",
    "responses_by_relationship"
  ]
}
</lang-strings>
