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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div class="tui-mod_approval-basicPrintView">
    <div
      v-for="section in sections"
      :key="section.key"
      class="tui-mod_approval-basicPrintView__section"
    >
      <h2 v-if="section.key === 'root'">{{ section.title }}</h2>
      <h3 v-else>{{ section.label }}</h3>
      <dl>
        <div
          v-for="field in section.fields"
          :key="field.key"
          class="tui-mod_approval-basicPrintView__field"
        >
          <dt>{{ field.label }}</dt>
          <dd>
            <template v-if="field.component">
              <component :is="field.component" v-bind="field.props" />
            </template>
            <template v-else>
              <div
                class="tui-mod_approval-basicPrintView__field-value"
                v-text="field.valueText"
              />
            </template>
          </dd>
        </div>
      </dl>
    </div>
    <div v-if="approvers && approvers.length > 0">
      <h3>{{ $str('approvals', 'mod_approval') }}</h3>
      <table class="tui-mod_approval-basicPrintView__approvals-table">
        <thead>
          <tr>
            <th class="tui-mod_approval-basicPrintView__approvals-step">
              {{ $str('approval_step', 'mod_approval') }}
            </th>
            <th class="tui-mod_approval-basicPrintView__approvals-name">
              {{ $str('approver_name', 'mod_approval') }}
            </th>
            <th class="tui-mod_approval-basicPrintView__approvals-date">
              {{ $str('approval_date', 'mod_approval') }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(approver, i) in approvers" :key="i">
            <td
              class="tui-mod_approval-basicPrintView__approvals-step"
              scope="row"
            >
              {{ approver.level }}
            </td>
            <td class="tui-mod_approval-basicPrintView__approvals-name">
              {{ approver.fullname }}
            </td>
            <td class="tui-mod_approval-basicPrintView__approvals-date">
              {{ approver.date }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    schema: {
      type: Object,
      required: true,
    },

    approvers: {
      type: Array,
    },
  },

  computed: {
    sections() {
      return [
        this.$_processSection(Object.assign({}, this.schema, { key: 'root' })),
      ].concat(this.schema.sections.map(this.$_processSection));
    },
  },

  methods: {
    $_processSection(section) {
      const fields = section.fields.map(field => {
        return Object.assign({}, field.resolved, {
          key: field.key,
          label: field.label,
        });
      });
      return Object.assign({}, section, {
        fields,
      });
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "approval_date",
    "approval_step",
    "approvals",
    "approver_name"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-basicPrintView {
  &__section {
    & > dl {
      @include tui-stack-vertical(var(--gap-2));
    }
  }
  &__field {
    display: flex;
    break-inside: avoid;

    & > dt {
      @include tui-font-heading-label();
      flex-basis: 0%;
      flex-grow: 1;
      flex-shrink: 0;
      margin: 0;
    }

    & > dd {
      flex-basis: 0%;
      flex-grow: 2;
      flex-shrink: 0;
      margin: 0;
    }

    &-value {
      white-space: pre-line;
    }
  }

  &__approvals {
    &-table {
      width: 100%;

      thead {
        border-bottom: var(--border-width-thin) solid currentColor;
      }

      th {
        font-weight: bold;
      }

      td,
      th {
        padding: var(--gap-1);
      }
    }

    &-step {
      width: 38%;
    }

    &-name {
      width: 38%;
    }

    &-date {
      width: 24%;
    }
  }
}
</style>
