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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @module mod_approval
 */
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';
import makeContext from 'mod_approval/workflow/edit/context';
import navigationState from 'mod_approval/workflow/edit/state/navigation';
import persistenceState from 'mod_approval/workflow/edit/state/persistence';

export default function makeState({
  categoryContextId,
  params,
  stagesExtendedContexts,
  workflow,
  approverTypes,
}) {
  const context = makeContext({
    categoryContextId,
    params,
    stagesExtendedContexts,
    workflow,
    approverTypes,
  });

  return {
    id: MOD_APPROVAL__WORKFLOW_EDIT,
    type: 'parallel',
    context,
    states: {
      navigation: navigationState({ params, context }),
      persistence: persistenceState(),
    },
  };
}
