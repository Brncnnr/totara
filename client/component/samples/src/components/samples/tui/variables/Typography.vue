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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module samples
-->

<template>
  <div>
    Available typography mixins

    <SamplesExample>
      <Table :data="typography">
        <template v-slot:header-row>
          <HeaderCell size="4">Mixin</HeaderCell>
          <HeaderCell size="12">Example</HeaderCell>
        </template>
        <template v-slot:row="{ row }">
          <Cell size="4" column-header="Mixin">
            {{ row.name }}
          </Cell>

          <Cell size="12" column-header="Example">
            <div :class="row.class">
              The quick brown fox jumps over the lazy dog.
            </div>
          </Cell>
        </template>
      </Table>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    Cell,
    HeaderCell,
    SamplesCode,
    SamplesExample,
    Table,
  },

  data() {
    return {
      typography: [
        {
          class: 'tui-typographySamples__heading-large',
          name: '@include tui-font-heading-large();',
        },
        {
          class: 'tui-typographySamples__heading-medium',
          name: '@include tui-font-heading-medium();',
        },
        {
          class: 'tui-typographySamples__heading-small',
          name: '@include tui-font-heading-small();',
        },
        {
          class: 'tui-typographySamples__heading-smallRegular',
          name: '@include tui-font-heading-small-regular();',
        },
        {
          class: 'tui-typographySamples__heading-xSmall',
          name: '@include tui-font-heading-x-small();',
        },
        {
          class: 'tui-typographySamples__heading-label',
          name: '@include tui-font-heading-label();',
        },
        {
          class: 'tui-typographySamples__heading-labelSmall',
          name: '@include tui-font-heading-label-small();',
        },
        {
          class: 'tui-typographySamples__body',
          name: '@include tui-font-body();',
        },
        {
          class: 'tui-typographySamples__body-small',
          name: '@include tui-font-body-small();',
        },
        {
          class: 'tui-typographySamples__body-xSmall',
          name: '@include tui-font-body-x-small();',
        },
        {
          class: 'tui-typographySamples__body-xxSmall',
          name: '@include tui-font-body-xx-small();',
        },
        {
          class: 'tui-typographySamples__body-disabled',
          name: '@include tui-font-body-disabled();',
        },
        {
          class: 'tui-typographySamples__body-placeholder',
          name: '@include tui-font-body-placeholder();',
        },
        {
          class: 'tui-typographySamples__body-heavy',
          name: '@include tui-font-heavy();',
        },
        {
          class: 'tui-typographySamples__body-hint',
          name: '@include tui-font-hint();',
        },
        {
          class: 'tui-typographySamples__link-large',
          name: '@include tui-font-link-large();',
        },
        {
          class: 'tui-typographySamples__link',
          name: '@include tui-font-link();',
        },
        {
          class: 'tui-typographySamples__link-small',
          name: '@include tui-font-link-small();',
        },
      ],
      codeTemplate: `<div class="tui-example__text">Hello</div>`,
      codeScript: `<style lang="scss">
  .tui-example__text {
    @include tui-font-heading-large();
  }
</style>`,
    };
  },
};
</script>

<style lang="scss">
.tui-typographySamples__heading-large {
  @include tui-font-heading-large();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__heading-medium {
  @include tui-font-heading-medium();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__heading-small {
  @include tui-font-heading-small();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__heading-smallRegular {
  @include tui-font-heading-small-regular();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__heading-xSmall {
  @include tui-font-heading-x-small();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__heading-label {
  @include tui-font-heading-label();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__heading-labelSmall {
  @include tui-font-heading-label-small();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__body {
  @include tui-font-body();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__body-small {
  @include tui-font-body-small();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__body-xSmall {
  @include tui-font-body-x-small();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__body-xxSmall {
  @include tui-font-body-xx-small();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__body-disabled {
  @include tui-font-body-disabled();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__body-placeholder {
  @include tui-font-body-placeholder();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__body-heavy {
  @include tui-font-heavy();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__body-hint {
  @include tui-font-hint();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__link {
  @include tui-font-link();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__link-large {
  @include tui-font-link-large();
  margin: var(--gap-1) 0;
}

.tui-typographySamples__link-small {
  @include tui-font-link-small();
  margin: var(--gap-1) 0;
}
</style>
