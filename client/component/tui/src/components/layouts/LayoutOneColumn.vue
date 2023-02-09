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
  @module tui
-->

<template>
  <div
    class="tui-layoutOneColumn"
    :class="{
      'tui-layoutOneColumn--flush': flush,
    }"
  >
    <slot name="feedback-banner" />

    <slot name="user-overview" />

    <div class="tui-layoutOneColumn__heading">
      <slot name="content-nav" />

      <PageHeading :title="title">
        <template v-slot:buttons>
          <slot name="header-buttons" />
        </template>
      </PageHeading>
    </div>

    <slot name="pre-body" />

    <Loader :loading="loading" class="tui-layoutOneColumn__body">
      <slot name="content" />
    </Loader>

    <slot name="modals" />
  </div>
</template>

<script>
import Loader from 'tui/components/loading/Loader';
import PageHeading from 'tui/components/layouts/PageHeading';

export default {
  components: {
    Loader,
    PageHeading,
  },

  props: {
    flush: Boolean,
    loading: Boolean,
    title: {
      required: true,
      type: String,
    },
  },
};
</script>

<style lang="scss">
.tui-layoutOneColumn {
  @include tui-font-body();
  margin-top: var(--gap-2);

  @include tui-stack-vertical(var(--gap-8));

  &__heading {
    @include tui-stack-vertical(var(--gap-2));
  }

  &--flush {
    margin-top: var(--gap-12);
  }
}
</style>
