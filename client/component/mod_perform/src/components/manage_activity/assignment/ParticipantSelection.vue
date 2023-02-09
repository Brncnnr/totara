<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Murali Nair <murali.nair@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-performAssignmentParticipantSelection">
    <Form
      v-if="manualRelationshipOptions"
      class="tui-performAssignmentParticipantSelection__form"
    >
      <FormRowStack spacing="large">
        <h3 class="tui-performAssignmentParticipantSelection__heading">
          {{ $str('manual_participant_selector_role_heading', 'mod_perform') }}
        </h3>
        <div class="tui-performAssignmentParticipantSelection__description">
          {{
            $str('manual_participant_selector_role_description', 'mod_perform')
          }}
        </div>
        <FormRow
          v-for="relationship in manualRelationships"
          :key="relationship.id"
          v-slot="{ id }"
          :label="relationship.name"
        >
          <div>
            <span v-if="isActive">
              {{ relationship.selector_relationship_name }}
            </span>
            <Select
              v-else
              :id="id"
              v-model="manualRelationshipSelections[relationship.id]"
              :aria-labelledby="id"
              :aria-label="relationship.name"
              :aria-describedby="$id('aria-describedby')"
              :options="manualRelationshipOptions"
              @input="updateManualRelationships"
            />
          </div>
        </FormRow>
      </FormRowStack>
    </Form>
  </div>
</template>

<script>
// Imports
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import FormRowStack from 'tui/components/form/FormRowStack';
import Select from 'tui/components/form/Select';

import { ACTIVITY_STATUS_ACTIVE } from 'mod_perform/constants';

export default {
  components: {
    Form,
    FormRow,
    FormRowStack,
    Select,
  },

  props: {
    activity: {
      type: Object,
      required: true,
    },
    manualRelationshipOptions: {
      type: Array,
    },
  },

  data() {
    return {
      manualRelationshipSelections: this.getManualRelationshipSelections(),
    };
  },

  computed: {
    /**
     * Indicates whether the activity is active
     *
     * @return {boolean}
     */
    isActive() {
      return this.activity.state_details.name === ACTIVITY_STATUS_ACTIVE;
    },

    manualRelationships() {
      return this.activity.manual_relationships.map(relationship => {
        return {
          name: relationship.manual_relationship.name,
          id: relationship.manual_relationship.id,
          selector_relationship_id: relationship.selector_relationship.id,
          selector_relationship_name: relationship.selector_relationship.name,
        };
      });
    },
  },

  methods: {
    /**
     * Gets manual relationship selections as an object map.
     * {manual_relationship_id: selected_relationship_id}
     *
     * @returns {Object}
     */
    getManualRelationshipSelections() {
      let relationshipSelections = {};
      this.activity.manual_relationships.forEach(relationship => {
        relationshipSelections[relationship.manual_relationship.id] =
          relationship.selector_relationship.id;
      });

      return relationshipSelections;
    },

    /**
     * Saves the manual relationship selections to the backend.
     */
    updateManualRelationships() {
      const relationships = Object.keys(this.manualRelationshipSelections).map(
        manual_relationship_id => {
          return {
            manual_relationship_id: manual_relationship_id,
            selector_relationship_id: this.manualRelationshipSelections[
              manual_relationship_id
            ],
          };
        }
      );
      this.$emit('update', relationships);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "manual_participant_selector_role_heading",
      "manual_participant_selector_role_description"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performAssignmentParticipantSelection {
  &__heading {
    margin: 0;
    @include tui-font-heading-small;
  }
}
</style>
