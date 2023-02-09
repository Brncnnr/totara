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

  @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-participantContentPrint">
    <Loader :loading="$apollo.loading">
      <div class="tui-participantContentPrint__printedOnDate">
        {{ printedOnDate }}
      </div>
      <ParticipantGeneralInformation
        v-if="subjectUser.card_display"
        class="tui-participantContentPrint__participantGeneralInformation"
        :subject-user="subjectUser"
        :job-assignments="jobAssignments"
        :current-user-is-subject="currentUserIsSubject"
        :relationship="relationshipToUser"
      />
      <div class="tui-participantContentPrint__header">
        <h1 class="tui-participantContentPrint__activityName">
          {{ activity.name }}
        </h1>

        <h2 class="tui-participantContentPrint__activityType">
          {{ activity.type.display_name }}
        </h2>

        <p class="tui-participantContentPrint__instanceDetails">
          <span
            v-for="(detail, i) in participantInstanceDetails"
            :key="i"
            class="tui-participantContentPrint__instanceDetails-detail"
            >{{ detail }}</span
          >
        </p>
      </div>
      <div class="tui-participantContentPrint__legendKey">
        <div>
          <RequiredOptionalIndicator is-required />
          {{ $str('section_element_response_required', 'mod_perform') }}
        </div>
        <div>
          <PrintedTodoIcon class="tui-participantContentPrint__printedTodo" />
          {{ $str('add_your_response', 'mod_perform') }}
        </div>
      </div>

      <div
        v-for="sectionResponse in participantSectionResponses"
        :key="sectionResponse.id"
        class="tui-participantContentPrint__section"
      >
        <h3
          v-if="activity.settings.multisection"
          class="tui-participantContentPrint__sectionHeading"
        >
          {{ sectionResponse.section.display_title }}
        </h3>
        <Uniform
          v-if="sectionResponse.id in initialUniformValues"
          input-width="full"
          :initial-values="initialUniformValues[sectionResponse.id]"
        >
          <div class="tui-participantContentPrint__sectionItems">
            <div
              v-for="elementResponse in elementsResponsesBySection[
                sectionResponse.id
              ]"
              :key="elementResponse.id"
              class="tui-participantContentPrint__sectionItem"
            >
              <ResponseHeader
                v-if="elementResponse.element.title"
                :id="$id('title')"
                :has-printed-to-do-icon="
                  showPrintedTodo(sectionResponse, elementResponse)
                "
                :is-respondable="elementResponse.is_respondable"
                :required="elementResponse.element.is_required"
                :title="elementResponse.element.title"
              />

              <div class="tui-participantContentPrint__sectionItem-content">
                <component
                  :is="elementResponse.formComponent"
                  class="tui-participantContentPrint__element"
                  :active-section-is-closed="
                    sectionResponse.availability_status === 'CLOSED'
                  "
                  :anonymous-responses="activity.anonymous_responses"
                  :element="elementResponse.element"
                  :element-components="elementResponse.element.element_plugin"
                  :from-print="
                    elementResponse.is_respondable && sectionResponse.can_answer
                  "
                  group-id=""
                  :has-printed-to-do-icon="
                    showPrintedTodo(sectionResponse, elementResponse)
                  "
                  :is-draft="isDraft"
                  :participant-can-answer="
                    elementResponse.is_respondable && sectionResponse.can_answer
                  "
                  :participant-instance-id="participantInstanceId"
                  :subject-instance-id="subjectInstanceId"
                  :subject-user="subjectUser"
                  :path="['sectionElements', elementResponse.id]"
                  :section-element="elementResponse"
                  :show-other-response="true"
                  :can-respond="elementResponse.can_respond"
                  :view-only="false"
                />
              </div>
            </div>
          </div>
        </Uniform>
      </div>

      <div
        v-if="!$apollo.loading"
        class="tui-participantContentPrint__actionButtons"
      >
        <Button :text="$str('print', 'mod_perform')" @click="printActivity" />
      </div>
    </Loader>
  </div>
</template>

<script>
// Util
import { uniqueId } from 'tui/util';
import { RELATIONSHIP_SUBJECT } from 'mod_perform/constants';
import { generateInitialValue } from 'mod_perform/initial_value_processor';
// Components
import Button from 'tui/components/buttons/Button';
import Loader from 'tui/components/loading/Loader';
import ParticipantGeneralInformation from 'mod_perform/components/user_activities/participant/ParticipantGeneralInformation';
import RequiredOptionalIndicator from 'mod_perform/components/user_activities/RequiredOptionalIndicator';
import ResponseHeader from 'mod_perform/components/element/ElementParticipantResponseHeader';
import { Uniform } from 'tui/components/uniform';
import PrintedTodoIcon from 'tui/components/icons/PrintedTodo';
// graphQL
import participantSectionsForPrintQuery from 'mod_perform/graphql/participant_sections_for_print';

export default {
  components: {
    RequiredOptionalIndicator,
    Button,
    Loader,
    ParticipantGeneralInformation,
    ResponseHeader,
    Uniform,
    PrintedTodoIcon,
  },

  props: {
    /**
     * The abstract perform activity this is an instance of.
     */
    activity: {
      type: Object,
      required: true,
    },

    /**
     * Created day of activity.
     */
    createdAt: {
      type: String,
      required: true,
    },

    /**
     * Due day of activity.
     */
    dueDate: String,

    /**
     * The user this activity is about.
     */
    subjectUser: {
      type: Object,
      required: true,
      validator(value) {
        return ['id', 'profileimageurlsmall', 'fullname'].every(
          Object.prototype.hasOwnProperty.bind(value)
        );
      },
    },

    /**
     * The participant instance id used to fetch the participant sections.
     */
    participantInstanceId: {
      type: Number,
      required: true,
    },

    /**
     * subject instance id
     */
    subjectInstanceId: {
      type: Number,
      required: true,
    },

    /**
     * The subjects job assignments.
     */
    jobAssignments: {
      type: Array,
      required: true,
    },

    /**
     * Pre-formatted "Printed at ..." header string.
     */
    printedOnDate: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      participantInstance: null,
      participantSectionResponses: [],
      errors: null,
      isDraft: false,
    };
  },
  computed: {
    /**
     * Element responses keyed by participant section id.
     */
    elementsResponsesBySection() {
      const elementResponsesBySection = {};

      this.participantSectionResponses.forEach(sectionResponse => {
        elementResponsesBySection[
          sectionResponse.id
        ] = sectionResponse.section_element_responses.map(
          sectionElementResponse => {
            // Parse child data
            let childElements = sectionElementResponse.element.children.map(
              child => {
                return Object.assign(
                  {
                    participantSectionId: sectionResponse.id,
                  },
                  this.getElementDetails(child)
                );
              }
            );

            return {
              id: sectionElementResponse.section_element_id,
              clientId: uniqueId(),
              formComponent: tui.asyncComponent(
                sectionElementResponse.element.element_plugin
                  .participant_form_component
              ),
              element: Object.assign(
                {
                  children: childElements,
                  participantSectionId: sectionResponse.id,
                },
                this.getElementDetails(sectionElementResponse.element)
              ),
              sort_order: sectionElementResponse.sort_order,
              is_respondable: sectionElementResponse.element.is_respondable,
              can_respond: sectionElementResponse.can_respond,
              response_data: JSON.parse(sectionElementResponse.response_data),
              response_data_raw: JSON.parse(
                sectionElementResponse.response_data_raw
              ),
              response_data_formatted_lines:
                sectionElementResponse.response_data_formatted_lines,
              other_responder_groups:
                sectionElementResponse.other_responder_groups,
            };
          }
        );
      });

      return elementResponsesBySection;
    },

    /**
     * Keyed by participant section id
     */
    initialUniformValues() {
      if (this.participantSectionResponses === []) {
        return {};
      }

      const initialUniformValues = {};

      this.participantSectionResponses.forEach(sectionResponse => {
        initialUniformValues[sectionResponse.id] = { sectionElements: {} };

        this.elementsResponsesBySection[sectionResponse.id]
          .filter(elementResponse => elementResponse.is_respondable)
          .forEach(elementResponse => {
            initialUniformValues[sectionResponse.id].sectionElements[
              elementResponse.id
            ] = generateInitialValue(
              elementResponse.element,
              elementResponse.response_data_raw
            );
          });
      });

      return initialUniformValues;
    },

    /**
     * The current users relationship to the subject of the activity.
     * @return {string|null}
     */
    relationshipToUser() {
      if (this.participantInstance === null) {
        return null;
      }

      if (this.currentUserIsSubject) {
        return this.$str('relation_to_subject_self', 'mod_perform');
      }

      return this.participantInstance.core_relationship.name;
    },

    /**
     * Is the current user the subject on this activity.
     */
    currentUserIsSubject() {
      if (this.participantInstance === null) {
        return null;
      }

      return (
        this.participantInstance.core_relationship.idnumber ===
        RELATIONSHIP_SUBJECT
      );
    },
    participantInstanceDetails() {
      if (this.participantInstance === null) {
        return [];
      }

      const details = [
        this.$str(
          'user_activities_print_created_on',
          'mod_perform',
          this.createdAt
        ),
        this.$str(
          'user_activities_print_overall_progress',
          'mod_perform',
          this.getStatusText(
            this.participantInstance.subject_instance.progress_status
          )
        ),
        this.$str(
          'user_activities_print_your_progress',
          'mod_perform',
          this.getStatusText(this.participantInstance.progress_status)
        ),
      ];

      if (this.dueDate) {
        details.push(
          this.$str(
            'user_activities_print_due_date',
            'mod_perform',
            this.dueDate
          )
        );
      }

      return details;
    },
  },
  apollo: {
    participantSectionResponses: {
      query: participantSectionsForPrintQuery,
      variables() {
        return {
          participant_instance_id: this.participantInstanceId,
        };
      },
      update: data => data['mod_perform_participant_sections'],
      result({ data }) {
        this.participantInstance = data['mod_perform_participant_instance'];
      },
    },
  },
  methods: {
    /**
     * Get element details from object.
     *
     * @param {Object} element
     * @return Object
     */
    getElementDetails(element) {
      return {
        data: element.data ? JSON.parse(element.data) : null,
        element_plugin: element.element_plugin,
        id: element.id,
        is_required: element.is_required,
        is_respondable: element.is_respondable,
        title: element.title,
        identifier: element.identifier,
      };
    },

    /**
     * Open the print dialog.
     */
    printActivity() {
      window.print();
    },
    /**
     * Get the localized status text for a particular user activity.
     *
     * @param status {string}
     * @returns {string}
     */
    getStatusText(status) {
      switch (status) {
        case 'NOT_STARTED':
          return this.$str('user_activities_status_not_started', 'mod_perform');
        case 'IN_PROGRESS':
          return this.$str('user_activities_status_in_progress', 'mod_perform');
        case 'COMPLETE':
          return this.$str('user_activities_status_complete', 'mod_perform');
        case 'PROGRESS_NOT_APPLICABLE':
          return this.$str(
            'user_activities_status_not_applicable',
            'mod_perform'
          );
        case 'NOT_SUBMITTED':
          return this.$str(
            'user_activities_status_not_submitted',
            'mod_perform'
          );
        default:
          return '';
      }
    },
    /**
     * Should we show the printed-todo icon.
     *
     * @param sectionResponse {Object}
     * @param elementResponse {Object}
     * @returns {boolean}
     */
    showPrintedTodo(sectionResponse, elementResponse) {
      if (sectionResponse.availability_status === 'CLOSED') {
        return false;
      }

      if (!sectionResponse.can_answer) {
        return false;
      }

      let responseValue = elementResponse.response_data_formatted_lines[0];
      let noResponseValue =
        responseValue === undefined || responseValue.length === 0;

      return elementResponse.is_respondable && noResponseValue;
    },
  },
};
</script>
<lang-strings>
{
  "mod_perform": [
    "add_your_response",
    "print",
    "relation_to_subject_self",
    "section_element_response_optional",
    "section_element_response_required",
    "user_activities_print_created_on",
    "user_activities_print_due_date",
    "user_activities_print_overall_progress",
    "user_activities_print_your_progress",
    "user_activities_status_complete",
    "user_activities_status_in_progress",
    "user_activities_status_not_applicable",
    "user_activities_status_not_started",
    "user_activities_status_not_submitted"
  ]
}
</lang-strings>

<style lang="scss">
.tui-participantContentPrint {
  @include tui-font-body-small;

  &__printedOnDate {
    text-align: center;
  }

  &__user {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    padding: var(--gap-2) var(--gap-4);
    border: var(--border-width-thin) solid var(--color-border);
    border-radius: var(--border-radius-normal);

    &-relationshipValue {
      display: block;
      margin: var(--gap-1) 0 0;
    }
  }

  &__activityType {
    @include tui-font-heading-small;
    margin: 0;
  }

  &__activityName {
    @include tui-font-heading-medium;
  }

  &__header {
    margin-top: var(--gap-12);
    padding: var(--gap-4);
    text-align: center;
    border: var(--border-width-thin) solid var(--color-primary);

    & > * + * {
      margin: var(--gap-6) 0 0 0;
    }
  }

  &__participantGeneralInformation {
    margin-top: var(--gap-4);
  }

  &__legendKey {
    margin-top: var(--gap-2);

    & > * + * {
      margin-top: var(--gap-2);
    }
  }

  &__sectionHeading {
    margin: var(--gap-4) 0 0;
    @include tui-font-heading-small;
  }

  &__section {
    margin-top: var(--gap-8);

    & > * + * {
      margin-top: var(--gap-4);
    }

    &-requiredContainer {
      margin-top: var(--gap-2);
    }

    &-responseRequired {
      display: inline-flex;
      @include tui-font-heading-label;
      color: var(--color-primary);
    }
  }

  &__instanceDetails {
    text-align: center;

    &-detail {
      display: inline-block;

      &:not(:last-child):after {
        margin: 0 var(--gap-1);
        border-left: solid 1px var(--color-text);
        content: '';
      }
    }
  }

  &__element {
    pointer-events: none;
  }

  &__sectionItems {
    & > * + * {
      margin-top: var(--gap-8);
    }
  }

  &__sectionItem {
    &-content {
      & > * {
        margin-top: var(--gap-4);
      }
    }
  }

  &__actionButtons {
    position: fixed;
    bottom: 0;
    left: 0;
    display: flex;
    justify-content: center;
    width: 100%;
    padding: var(--gap-2) 0;
    background: rgba(247, 247, 247, 0.8);
  }

  @media screen {
    max-width: 21cm;
    margin: auto auto var(--gap-12);
    padding: var(--gap-12);
    border: var(--border-width-thin) solid var(--color-border);
    box-shadow: var(--shadow-4);
  }

  @media print {
    &__actionButtons {
      display: none;
    }

    &__sectionItem {
      break-inside: avoid;
    }
  }
}
</style>
