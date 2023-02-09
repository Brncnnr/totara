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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-themeTenants">
    <PageHeading :title="themeName || theme" />
    <div class="tui-themeTenants__content">
      <div class="tui-themeTenants__section">
        <h3>{{ $str('sitebranding', 'totara_tui') }}</h3>
        <p>{{ $str('sitebrandinginformation', 'totara_tui') }}</p>
        <ActionLink
          :href="configLink"
          :text="$str('editsitebranding', 'totara_tui')"
          :styleclass="{
            primary: true,
          }"
        />
      </div>

      <div class="tui-themeTenants__section">
        <h3>{{ $str('tenantbranding', 'totara_tui') }}</h3>
        <Table :data="tenants">
          <template v-slot:header-row>
            <HeaderCell>{{ $str('tenant', 'totara_tenant') }}</HeaderCell>
            <HeaderCell>
              {{ $str('tenantidnumber', 'totara_tenant') }}
            </HeaderCell>
            <HeaderCell>{{ $str('branding', 'totara_tui') }}</HeaderCell>
            <HeaderCell>{{ $str('actions', 'core') }}</HeaderCell>
          </template>
          <template v-slot:row="{ row }">
            <Cell>
              {{ row.name }}
            </Cell>
            <Cell>
              {{ row.idnumber }}
            </Cell>
            <Cell>
              {{
                row.customBranding
                  ? $str('custom', 'totara_tui')
                  : $str('site', 'totara_tui')
              }}
            </Cell>
            <Cell>
              <a :href="tenantLink(row.id)">
                <Edit
                  :alt="$str('edittenantsetting', 'totara_tui', row.name)"
                />
              </a>
            </Cell>
          </template>
        </Table>
      </div>
    </div>
  </div>
</template>

<script>
import ActionLink from 'tui/components/links/ActionLink';
import Table from 'tui/components/datatable/Table';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import PageHeading from 'tui/components/layouts/PageHeading';
import Cell from 'tui/components/datatable/Cell';
import Edit from 'tui/components/icons/Edit';

export default {
  components: {
    ActionLink,
    Table,
    HeaderCell,
    PageHeading,
    Cell,
    Edit,
  },

  props: {
    theme: {
      type: String,
      required: true,
    },
    themeName: String,
    tenants: {
      type: Array,
      required: false,
      default: () => [],
    },
  },

  data() {
    return {
      configLink: this.$url('/totara/tui/theme_settings.php', {
        theme_name: this.theme,
      }),
    };
  },

  methods: {
    tenantLink(tenant_id) {
      return this.$url('/totara/tui/theme_settings.php', {
        theme_name: this.theme,
        tenant_id: tenant_id,
      });
    },
  },
};
</script>

<lang-strings>
{
  "core": ["actions"],
  "totara_tui": [
    "branding",
    "custom",
    "editsitebranding",
    "edittenantsetting",
    "site",
    "sitebrandinginformation",
    "sitebranding",
    "tenantbranding"
  ],
  "totara_tenant": [
    "tenant",
    "tenantidnumber"
  ]
}
</lang-strings>

<style lang="scss">
.tui-themeTenants {
  &__content {
    margin-top: var(--gap-8);
  }

  &__section + &__section {
    margin-top: var(--gap-12);
  }

  &__section {
    @include tui-stack-vertical(var(--gap-4));
  }
}
</style>
