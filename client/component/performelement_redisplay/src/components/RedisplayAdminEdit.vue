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
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module performelement_redisplay
-->

<template>
  <div class="tui-redisplayAdminEdit">
    <PerformAdminCustomElementEdit
      ref="redisplayUniform"
      :initial-values="initialValues"
      :settings="settings"
      validation-mode="auto"
      @change="updateOptionValues"
      @cancel="$emit('display')"
      @update="saveRedisplayElement"
    >
      <FormRow
        v-slot="{ labelId }"
        :label="$str('source_activity_value', 'performelement_redisplay')"
        :helpmsg="
          $str('source_activity_value_help', 'performelement_redisplay')
        "
        :required="true"
      >
        <Loader
          :loading="$apollo.queries.activities.loading"
          class="tui-redisplayAdminEdit__loader tui-redisplayAdminEdit__loader--charLength-25"
        >
          <FormSelect
            :aria-labelledby="labelId"
            :options="activityOptions"
            :disabled="$apollo.queries.activities.loading"
            name="activityId"
            char-length="25"
            :validations="v => [v.required()]"
          />
        </Loader>
      </FormRow>

      <FormRow
        v-if="selectedActivityId"
        v-slot="{ labelId }"
        :label="
          $str('source_question_element_value', 'performelement_redisplay')
        "
        :helpmsg="
          $str('source_question_element_value_help', 'performelement_redisplay')
        "
        :required="true"
      >
        <Loader
          :loading="$apollo.loading || loadingSectionElements"
          class="tui-redisplayAdminEdit__loader tui-redisplayAdminEdit__loader--charLength-25"
        >
          <FormSelect
            v-if="selectedActivityElementOptions"
            :aria-labelledby="labelId"
            name="sourceSectionElementId"
            char-length="25"
            :disabled="$apollo.loading"
            :options="selectedActivityElementOptions"
            :validations="v => [v.required()]"
          />
        </Loader>
      </FormRow>
    </PerformAdminCustomElementEdit>
  </div>
</template>

<script>
import { FormRow, FormSelect } from 'tui/components/uniform';
import Loader from 'tui/components/loading/Loader';
import PerformAdminCustomElementEdit from 'mod_perform/components/element/PerformAdminCustomElementEdit';
import sourceActivitiesQuery from 'performelement_redisplay/graphql/source_activities';
import sourceActivityQuestionElementsQuery from 'performelement_redisplay/graphql/source_activity_question_elements';

export default {
  components: {
    FormRow,
    FormSelect,
    Loader,
    PerformAdminCustomElementEdit,
  },

  inheritAttrs: false,

  props: {
    identifier: String,
    isRequired: Boolean,
    rawData: [Object, Array],
    rawTitle: String,
    settings: Object,
    data: [Object, Array],
    currentActivityId: Number,
  },

  data() {
    return {
      initialValues: {
        activityId: this.rawData.activityId || null,
        sourceSectionElementId: this.rawData.sourceSectionElementId || null,
        identifier: this.identifier,
        rawTitle: this.rawTitle,
        responseRequired: this.isRequired,
      },
      activities: [],
      selectedActivityElementOptions: [],
      selectedActivityId: this.rawData.activityId || null,
      loadingSectionElements: false,
    };
  },

  computed: {
    /**
     * Restructure activity data to display options in a select list
     *
     */
    activityOptions() {
      let defaultOption = {
        id: null,
        label: this.$str('select_activity', 'performelement_redisplay'),
      };

      let options = this.activities.map(activity => {
        return {
          id: activity.id,
          label: this.getActivityStatusLabel(activity),
        };
      });

      options.unshift(defaultOption);

      return options;
    },
  },

  apollo: {
    activities: {
      query: sourceActivitiesQuery,
      update(data) {
        return data.mod_perform_activities;
      },
    },
  },

  mounted() {
    if (!this.rawData.activityId) {
      return;
    }
    // Get section element options if existing selected activity ID
    this.fetchSourceSectionElements(this.rawData.activityId);
  },

  methods: {
    /**
     * Update option values for element ID based on user input for activity ID
     *
     * @param {Object} values
     */
    updateOptionValues(values) {
      if (values.activityId === this.selectedActivityId) {
        return;
      }

      if (!values || !values.activityId) {
        this.selectedActivityId = null;
        this.selectedActivityElementOptions = [];
        return;
      }

      this.selectedActivityId = values.activityId;
      this.resetQuestionElementFormValue();
      this.fetchSourceSectionElements(values.activityId);
    },

    /**
     * Fetch source section element data for selected activity & restructure result data
     * for compatibility with a select component
     *
     * @param {Number} activityId
     */
    fetchSourceSectionElements(activityId) {
      this.loadingSectionElements = true;
      this.$apollo
        .query({
          query: sourceActivityQuestionElementsQuery,
          variables: { input: { activity_id: activityId } },
          fetchPolicy: 'network-only',
        })
        .then(data => {
          this.processSectionElements(data);
          this.loadingSectionElements = false;
        });
    },

    /**
     * Process the section elements to select options.
     *
     * @param {Object} data
     */
    processSectionElements(data) {
      let groups =
        data.data.performelement_redisplay_source_activity_question_elements
          .sections;
      let elementOptions;

      if (groups.length === 0) {
        this.selectedActivityElementOptions = [
          {
            id: null,
            label: this.$str(
              'no_available_questions',
              'performelement_redisplay'
            ),
          },
        ];

        return;
      }

      // If there are multiple groups
      if (groups.length > 1) {
        elementOptions = groups.map(group => {
          return {
            label: group.title,
            options: this.filterLinkedReview(
              group.respondable_section_elements
            ).map(sectionElement => {
              return {
                id: sectionElement.id,
                label: this.getElementLabel(sectionElement.element),
              };
            }),
          };
        });
      } else {
        elementOptions = this.filterLinkedReview(
          groups[0].respondable_section_elements
        ).map(sectionElement => {
          return {
            id: sectionElement.id,
            label: this.getElementLabel(sectionElement.element),
          };
        });
      }

      let defaultOption = {
        id: null,
        label: this.$str('select_question_element', 'performelement_redisplay'),
      };

      elementOptions.unshift(defaultOption);
      this.selectedActivityElementOptions = elementOptions;
    },

    /**
     * Filter out linked_review elements. Todo: fix in TL-30351.
     *
     * @param {Array} sectionElements
     * @return {Array}
     */
    filterLinkedReview(sectionElements) {
      return sectionElements.filter(sectionElement => {
        return (
          sectionElement.element.element_plugin.plugin_name !== 'linked_review'
        );
      });
    },

    /**
     * Get activity status and append to the select option label
     *
     * @param {Object} activityItem
     * @return {String}
     */
    getActivityStatusLabel(activityItem) {
      let activityStatus = activityItem.state_details.display_name;

      if (this.currentActivityId === parseInt(activityItem.id)) {
        activityStatus = this.$str(
          'current_activity',
          'performelement_redisplay'
        );
      }

      return this.$str(
        'activity_name_with_status',
        'performelement_redisplay',
        {
          activity_name: activityItem.name,
          activity_status: activityStatus,
        }
      );
    },

    /**
     * Get Element option label
     *
     * @param {Object} element
     * @return {String}
     */
    getElementLabel(element) {
      return this.$str('source_element_option', 'performelement_redisplay', {
        element_title: element.title,
        element_plugin_name: element.element_plugin.name,
      });
    },

    /**
     * Clear the source question element value in the uniform
     *
     */
    resetQuestionElementFormValue() {
      this.$refs.redisplayUniform.update('sourceSectionElementId', null);
    },

    /**
     * Save redisplay element.
     *
     * @param {Object} event
     */
    saveRedisplayElement(event) {
      delete event.data.activityId;
      this.$emit('update', event);
    },
  },
};
</script>

<lang-strings>
  {
    "performelement_redisplay": [
      "activity_name_with_status",
      "current_activity",
      "no_available_questions",
      "select_activity",
      "select_question_element",
      "source_activity_value",
      "source_activity_value_help",
      "source_element_option",
      "source_question_element_value",
      "source_question_element_value_help"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-redisplayAdminEdit {
  &__loader {
    @include tui-char-length-classes();
  }
}
</style>
