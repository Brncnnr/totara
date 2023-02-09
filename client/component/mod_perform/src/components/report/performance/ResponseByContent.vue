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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @module totara_perform
-->
<template>
  <div class="tui-performReportPerformanceResponseByContent">
    <div class="tui-performReportPerformanceResponseByContent__activity">
      <div class="tui-performReportPerformanceResponseByContent__heading">
        {{ $str('performance_report_select_activity', 'mod_perform') }}
      </div>
      <Loader :loading="$apollo.loading">
        <Uniform
          :vertical="true"
          :initial-values="initialValues"
          @submit="loadActivityReport"
        >
          <FormRow
            v-slot="{ id }"
            :label="$str('performance_report_select_activity', 'mod_perform')"
            :hidden="true"
          >
            <FormSelect
              :id="id"
              name="activity"
              :aria-labelledby="id"
              :aria-describedby="$id('aria-describedby')"
              :options="activityOptions"
              :validations="v => [v.required()]"
            />
          </FormRow>
          <FormRow>
            <Button
              class="tui-performReportPerformanceResponseByContent__activity_button"
              :styleclass="{ primary: true }"
              :text="$str('performance_report_load_records', 'mod_perform')"
              type="submit"
            />
          </FormRow>
        </Uniform>
      </Loader>
    </div>
    <div
      v-if="hasReportingIds"
      class="tui-performReportPerformanceResponseByContent__divider"
    >
      <Separator>
        {{ $str('performance_report_load_records_divider', 'mod_perform') }}
      </Separator>
    </div>
    <div
      v-if="hasReportingIds"
      class="tui-performReportPerformanceResponseByContent__id"
    >
      <div class="tui-performReportPerformanceResponseByContent__heading">
        {{ $str('performance_report_select_reporting_ids', 'mod_perform') }}
      </div>
      <Loader :loading="$apollo.loading">
        <Uniform :vertical="true" @submit="loadReportingIDReport">
          <FormRow
            v-slot="{ id }"
            :label="
              $str('performance_report_select_reporting_ids', 'mod_perform')
            "
            :hidden="true"
          >
            <TagList
              :tags="tags"
              :items="reportingIds"
              :filter="searchItem"
              @select="select"
              @remove="remove"
              @filter="filter"
            >
              <template v-slot:item="{ index, item }">
                <div>
                  <p>{{ item.name }}</p>
                </div>
              </template>
            </TagList>
          </FormRow>
          <FormRow>
            <Button
              :styleclass="{ primary: true }"
              :text="$str('performance_report_load_records', 'mod_perform')"
              type="submit"
            />
          </FormRow>
        </Uniform>
      </Loader>
    </div>
  </div>
</template>
<script>
import Button from 'tui/components/buttons/Button';
import performReportableActivitiesQuery from 'mod_perform/graphql/reportable_activities';
import performReportableElementIdentifiersQuery from 'mod_perform/graphql/reportable_element_identifiers';
import TagList from 'tui/components/tag/TagList';
import Separator from 'tui/components/decor/Separator';
import { FormRow, Uniform, FormSelect } from 'tui/components/uniform';
import Loader from 'tui/components/loading/Loader';

export default {
  components: {
    Button,
    Uniform,
    FormRow,
    FormSelect,
    Separator,
    TagList,
    Loader,
  },
  props: {
    hasReportingIds: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      initialValues: { activity: null },
      tags: [],
      activities: [],
      elementIdentifiers: [],
      searchItem: '',
    };
  },
  computed: {
    activityOptions() {
      const options = this.activities.map(activity => {
        return { id: activity.id, label: activity.name };
      });
      options.unshift({
        id: null,
        label: this.$str(
          'performance_report_select_activity_placeholder',
          'mod_perform'
        ),
      });
      return options;
    },
    reportingIds() {
      const items = this.elementIdentifiers
        .map(identifier => {
          return { id: identifier.id, name: identifier.identifier };
        })
        .filter(item => !this.tags.some(tag => item.id === tag.id));

      if (this.searchItem) {
        return items.filter(item => item.name.includes(this.searchItem));
      }
      return items;
    },
  },
  methods: {
    /**
     * redirect to activity report
     * @param values
     */
    loadActivityReport(values) {
      window.location = this.$url(
        '/mod/perform/reporting/performance/activity.php',
        {
          activity_id: values.activity,
        }
      );
    },
    /**
     * redirect to reporting ID report
     * @param values
     */
    loadReportingIDReport() {
      const ids = this.tags.map(tag => {
        return tag.id;
      });
      window.location = this.$url(
        '/mod/perform/reporting/performance/element_identifier.php',
        {
          element_identifier: ids.join(','),
        }
      );
    },
    /**
     * Reporting identifiers select
     * @param item
     */
    select(item) {
      const { name, id } = item;
      this.tags.push({ text: name, id });
      this.searchItem = '';
    },
    /**
     * TReporting identifiers remove
     * @param item
     */
    remove(tag) {
      this.tags = this.tags.filter(t => t !== tag);
    },
    /**
     * TReporting identifiers filter
     * @param item
     */
    filter(value) {
      this.searchItem = value;
    },
  },
  apollo: {
    activities: {
      query: performReportableActivitiesQuery,
      update: data => data.mod_perform_reportable_activities,
    },
    elementIdentifiers: {
      query: performReportableElementIdentifiersQuery,
      update: data => data.mod_perform_reportable_element_identifiers,
      skip() {
        return !this.hasReportingIds;
      },
    },
  },
};
</script>
<lang-strings>
  {
  "mod_perform": [
    "performance_report_load_records",
    "performance_report_load_records_divider",
    "performance_report_select_activity",
    "performance_report_select_activity_placeholder",
    "performance_report_select_reporting_ids"
  ]
  }
</lang-strings>

<style lang="scss">
.tui-performReportPerformanceResponseByContent {
  display: flex;
  flex-direction: column;
  &__heading {
    @include tui-font-heading-small();
  }
  &__divider {
    max-width: 250px;
  }
}
</style>
