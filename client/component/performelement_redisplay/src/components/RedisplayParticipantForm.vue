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
  @module performelement_redisplay
  -->

<template>
  <div v-if="ready" class="tui-redisplayParticipantForm">
    {{ redisplayData.title }}

    <div class="tui-redisplayParticipantForm__cardArea">
      <Card class="tui-redisplayParticipantForm__card">
        <h4 class="tui-redisplayParticipantForm__card-title">
          {{ element.data.elementTitle }}
        </h4>

        <!-- Handle the different view switching for input / responses (read only / print)
        and output others responses -->
        <ElementParticipantFormContent
          v-bind="$attrs"
          :active-section-is-closed="true"
          :participant-can-answer="participantCanAnswer"
          :element="element"
          :element-components="otherData.element.element_plugin"
          :section-element="otherData"
          :show-other-response="true"
        />
      </Card>
    </div>
  </div>
  <div v-else-if="!element.data.elementPluginDisplayComponent">
    {{ $str('source_activity_missing', 'performelement_redisplay') }}
  </div>
</template>

<script>
import Card from 'tui/components/card/Card';
import ElementParticipantFormContent from 'mod_perform/components/element/ElementParticipantFormContent';
import subjectInstancePreviousResponsesQuery from 'performelement_redisplay/graphql/subject_instance_previous_responses';
import subjectInstancePreviousResponsesForExternalParticipantQuery from 'performelement_redisplay/graphql/subject_instance_previous_responses_nosession';

export default {
  components: {
    Card,
    ElementParticipantFormContent,
  },

  props: {
    element: {
      type: Object,
      validator: function(value) {
        return !!value.data;
      },
    },
    token: String,
    subjectInstanceId: Number,
  },

  data() {
    return {
      ready: false,
      redisplayData: {},
    };
  },

  apollo: {
    redisplayData: {
      skip() {
        return !this.element.data.elementPluginDisplayComponent;
      },
      query() {
        if (!this.element.participantSectionId) {
          return subjectInstancePreviousResponsesQuery;
        }
        return this.token && this.token.length > 0
          ? subjectInstancePreviousResponsesForExternalParticipantQuery
          : subjectInstancePreviousResponsesQuery;
      },
      fetchPolicy: 'network-only',
      variables() {
        let input = {
          subject_instance_id: this.subjectInstanceId,
          section_element_id: this.element.data.sourceSectionElementId,
        };

        if (this.token) {
          input.token = this.token;
        }

        if (this.element.participantSectionId) {
          input.participant_section_id = this.element.participantSectionId;
        }
        return {
          input,
        };
      },
      update(data) {
        this.ready = true;
        return data.redisplayData;
      },
    },
  },

  computed: {
    otherData() {
      if (!this.element.data.elementPluginDisplayComponent) {
        return null;
      }

      let componentTypes = Object.assign({}, this.element.element_plugin, {
        participant_response_component: this.element.data
          .elementPluginDisplayComponent,
      });

      let elementData = Object.assign({}, this.element, {
        element_plugin: componentTypes,
      });

      return {
        element: elementData,
        other_responder_groups: this.redisplayData.other_responder_groups,
        response_data_formatted_lines: this.redisplayData.your_response
          ? this.redisplayData.your_response.response_data_formatted_lines
          : [],
      };
    },

    participantCanAnswer() {
      return this.redisplayData.your_response !== null;
    },
  },
};
</script>

<style lang="scss">
.tui-redisplayParticipantForm {
  &__cardArea {
    margin: var(--gap-4) 0 0 var(--gap-8);
  }

  &__card {
    flex-direction: column;
    padding: var(--gap-4);

    & > * + * {
      margin-top: var(--gap-4);
    }

    &-title {
      margin: 0;
      @include tui-font-heading-label();
    }
  }
}
</style>

<lang-strings>
  {
    "performelement_redisplay": [
      "source_activity_missing"
    ]
  }
</lang-strings>
