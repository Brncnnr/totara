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

  @author Dave Wallace <dave.wallace@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-formRow__defaults">
    {{ $str('formfield_defaults', 'totara_core') }} <slot />
  </div>
</template>

<lang-strings>
{
  "totara_core": [
    "formfield_defaults"
  ]
}
</lang-strings>

<style lang="scss">
.tui-formRow__defaults {
  color: var(--form-defaults-text-color);
  @include tui-font-body-x-small;
}
</style>
