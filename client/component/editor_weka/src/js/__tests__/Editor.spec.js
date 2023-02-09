/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module editor_weka
 */

import Editor from '../Editor';
import * as unknownNodeRewritingFixture from './test_util/unknown_node_rewriting';

describe('Editor', () => {
  it('rewrites unknown nodes and marks', () => {
    const editor = new Editor();
    const output = JSON.parse(
      JSON.stringify(unknownNodeRewritingFixture.originalDoc)
    );
    editor._rewriteUnknownNodes(editor.schema, output);
    expect(output).toEqual(unknownNodeRewritingFixture.rewrittenDoc);
  });
});
