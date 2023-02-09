/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module mod_approval
 */
import { createSelector } from 'tui_xstate/util';
import { langString } from 'tui/i18n';
import { getIdNumberIsUnique } from 'mod_approval/graphql_selectors/workflow_id_number_is_unique';
export * from 'mod_approval/graphql_selectors/workflow_id_number_is_unique';
export * from 'mod_approval/graphql_selectors/get_active_forms';

export const getErrors = createSelector(getIdNumberIsUnique, isUnique =>
  isUnique === false
    ? { id_number: langString('error:workflow_id_not_unique', 'mod_approval') }
    : null
);
