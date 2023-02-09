/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @module tui
 */

const NativeModule = require('module');
const path = require('path');
const { getThemeExportedVars } = require('../lib/theme_variables');

function evalModuleCode(loaderContext, code, filename) {
  const module = new NativeModule(filename);

  module.paths = NativeModule._nodeModulePaths(loaderContext.context);
  module.filename = filename;
  module._compile(code, filename);

  return module.exports;
}

module.exports = function(code) {
  const validLocation = /client[/\\]component[/\\]\w+[/\\]src[/\\]global_styles[/\\]_?variables.scss$/.test(
    this.resourcePath
  );
  if (!validLocation) return '';

  // get the CSS loader result
  const result = evalModuleCode(this, code, this.resourcePath);

  // extract exported variable data
  const cssProperties = getThemeExportedVars(result.toString());

  // Need to do something more complicated like mini-css-extract-plugin does to
  // be more correct, but this works for our case.
  const componentSrcDir = path.join(
    path.relative(this._compiler.outputPath, this.context),
    '..'
  );
  const componentBuildDir = path.join(componentSrcDir, '../build');
  this.emitFile(
    path.join(componentBuildDir, 'css_variables.json'),
    JSON.stringify(cssProperties, null, 2) + '\n'
  );
  return code;
};
