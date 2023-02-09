/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./client/component/weka_notification_placeholder/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\]internal[/\\\\]).)*$":
/*!***************************************************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/components/ sync ^(?:(?%21__[a-z]*__%7C[/\\]internal[/\\]).)*$ ***!
  \***************************************************************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

eval("var map = {\n\t\"./nodes/Placeholder\": \"./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue\",\n\t\"./nodes/Placeholder.vue\": \"./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue\",\n\t\"./suggestion/Placeholder\": \"./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue\",\n\t\"./suggestion/Placeholder.vue\": \"./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./client/component/weka_notification_placeholder/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\";\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/_sync_^(?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/js sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\]internal[/\\\\]).)*$":
/*!*******************************************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/js/ sync ^(?:(?%21__[a-z]*__%7C[/\\]internal[/\\]).)*$ ***!
  \*******************************************************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

eval("var map = {\n\t\"./extension\": \"./client/component/weka_notification_placeholder/src/js/extension.js\",\n\t\"./extension.js\": \"./client/component/weka_notification_placeholder/src/js/extension.js\",\n\t\"./plugin\": \"./client/component/weka_notification_placeholder/src/js/plugin.js\",\n\t\"./plugin.js\": \"./client/component/weka_notification_placeholder/src/js/plugin.js\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./client/component/weka_notification_placeholder/src/js sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\";\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/js/_sync_^(?");

/***/ }),

/***/ "./server/lib/editor/weka/extensions/notification_placeholder/webapi/ajax/placeholders.graphql":
/*!*****************************************************************************************************!*\
  !*** ./server/lib/editor/weka/extensions/notification_placeholder/webapi/ajax/placeholders.graphql ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"query\",\"name\":{\"kind\":\"Name\",\"value\":\"weka_notification_placeholder_placeholders\"},\"variableDefinitions\":[{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"context_id\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"param_integer\"}}},\"directives\":[]},{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"pattern\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"param_text\"}}},\"directives\":[]},{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"resolver_class_name\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"param_text\"}}},\"directives\":[]}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"placeholders\"},\"name\":{\"kind\":\"Name\",\"value\":\"weka_notification_placeholder_placeholders\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"context_id\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"context_id\"}}},{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"pattern\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"pattern\"}}},{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"resolver_class_name\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"resolver_class_name\"}}}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"__typename\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"label\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"key\"},\"arguments\":[],\"directives\":[]}]}}]}}]};\n    /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (doc);\n  \n\n//# sourceURL=webpack:///./server/lib/editor/weka/extensions/notification_placeholder/webapi/ajax/placeholders.graphql?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/tui.json":
/*!*********************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/tui.json ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

eval("!function() {\n\"use strict\";\n\nif (typeof tui !== 'undefined' && tui._bundle.isLoaded(\"weka_notification_placeholder\")) {\n  console.warn(\n    '[tui bundle] The bundle \"' + \"weka_notification_placeholder\" +\n    '\" is already loaded, skipping initialisation.'\n  );\n  return;\n};\ntui._bundle.register(\"weka_notification_placeholder\")\ntui._bundle.addModulesFromContext(\"weka_notification_placeholder\", __webpack_require__(\"./client/component/weka_notification_placeholder/src/js sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\"));\ntui._bundle.addModulesFromContext(\"weka_notification_placeholder/components\", __webpack_require__(\"./client/component/weka_notification_placeholder/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\"));\n}();\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/tui.json?");

/***/ }),

/***/ "./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1671[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1671[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(component) {\n        component.options.__langStrings = \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n{\n  \"editor_weka\": [\n    \"matching_placeholders\"\n  ]\n}\n;\n    }\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1671%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue":
/*!*********************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _Placeholder_vue_vue_type_template_id_31426794___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Placeholder.vue?vue&type=template&id=31426794& */ \"./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=template&id=31426794&\");\n/* harmony import */ var _Placeholder_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Placeholder.vue?vue&type=script&lang=js& */ \"./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n\n\n\n\n\n/* normalize component */\n;\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(\n  _Placeholder_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _Placeholder_vue_vue_type_template_id_31426794___WEBPACK_IMPORTED_MODULE_0__.render,\n  _Placeholder_vue_vue_type_template_id_31426794___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue\"\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue":
/*!**************************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _Placeholder_vue_vue_type_template_id_9d46c78e___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Placeholder.vue?vue&type=template&id=9d46c78e& */ \"./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=template&id=9d46c78e&\");\n/* harmony import */ var _Placeholder_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Placeholder.vue?vue&type=script&lang=js& */ \"./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _Placeholder_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Placeholder.vue?vue&type=style&index=0&lang=scss& */ \"./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n/* harmony import */ var _Placeholder_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Placeholder.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=custom&index=0&blockType=lang-strings\");\n\n\n\n;\n\n\n/* normalize component */\n\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(\n  _Placeholder_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _Placeholder_vue_vue_type_template_id_9d46c78e___WEBPACK_IMPORTED_MODULE_0__.render,\n  _Placeholder_vue_vue_type_template_id_9d46c78e___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* custom blocks */\n;\nif (typeof _Placeholder_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"] === 'function') (0,_Placeholder_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(component)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue\"\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?");

/***/ }),

/***/ "./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var editor_weka_components_nodes_BaseNode__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! editor_weka/components/nodes/BaseNode */ \"editor_weka/components/nodes/BaseNode\");\n/* harmony import */ var editor_weka_components_nodes_BaseNode__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(editor_weka_components_nodes_BaseNode__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var totara_notification_components_json_editor_nodes_Placeholder__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! totara_notification/components/json_editor/nodes/Placeholder */ \"totara_notification/components/json_editor/nodes/Placeholder\");\n/* harmony import */ var totara_notification_components_json_editor_nodes_Placeholder__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(totara_notification_components_json_editor_nodes_Placeholder__WEBPACK_IMPORTED_MODULE_1__);\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  components: {\n    Placeholder: (totara_notification_components_json_editor_nodes_Placeholder__WEBPACK_IMPORTED_MODULE_1___default()),\n  },\n\n  extends: (editor_weka_components_nodes_BaseNode__WEBPACK_IMPORTED_MODULE_0___default()),\n\n  computed: {\n    placeholderKey() {\n      const attrs = this.attrs;\n      if (!attrs.key) {\n        return '';\n      }\n\n      return attrs.key;\n    },\n\n    displayName() {\n      const attrs = this.attrs;\n      if (!attrs.label) {\n        return '';\n      }\n\n      return attrs.label;\n    },\n  },\n});\n\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var tui_components_dropdown_Dropdown__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tui/components/dropdown/Dropdown */ \"tui/components/dropdown/Dropdown\");\n/* harmony import */ var tui_components_dropdown_Dropdown__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(tui_components_dropdown_Dropdown__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var tui_components_dropdown_DropdownItem__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! tui/components/dropdown/DropdownItem */ \"tui/components/dropdown/DropdownItem\");\n/* harmony import */ var tui_components_dropdown_DropdownItem__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(tui_components_dropdown_DropdownItem__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var tui_components_loading_Loader__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! tui/components/loading/Loader */ \"tui/components/loading/Loader\");\n/* harmony import */ var tui_components_loading_Loader__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(tui_components_loading_Loader__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var weka_notification_placeholder_graphql_placeholders__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! weka_notification_placeholder/graphql/placeholders */ \"./server/lib/editor/weka/extensions/notification_placeholder/webapi/ajax/placeholders.graphql\");\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n\n\n// GraphQL queries\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  components: {\n    Dropdown: (tui_components_dropdown_Dropdown__WEBPACK_IMPORTED_MODULE_0___default()),\n    DropdownItem: (tui_components_dropdown_DropdownItem__WEBPACK_IMPORTED_MODULE_1___default()),\n    Loader: (tui_components_loading_Loader__WEBPACK_IMPORTED_MODULE_2___default()),\n  },\n\n  props: {\n    contextId: {\n      type: [Number, String],\n      required: true,\n    },\n    resolverClassName: {\n      type: String,\n      required: true,\n    },\n\n    location: {\n      required: true,\n      type: Object,\n    },\n\n    pattern: {\n      required: true,\n      type: String,\n    },\n  },\n\n  apollo: {\n    placeholders: {\n      query: weka_notification_placeholder_graphql_placeholders__WEBPACK_IMPORTED_MODULE_3__[\"default\"],\n      fetchPolicy: 'network-only',\n      variables() {\n        return {\n          pattern: this.pattern,\n          context_id: this.contextId,\n          resolver_class_name: this.resolverClassName,\n        };\n      },\n    },\n  },\n\n  data() {\n    return {\n      placeholders: [],\n    };\n  },\n\n  computed: {\n    showSuggestions() {\n      return this.$apollo.loading || this.placeholders.length > 0;\n    },\n\n    positionStyle() {\n      return {\n        left: `${this.location.x}px`,\n        top: `${this.location.y}px`,\n      };\n    },\n  },\n\n  watch: {\n    showSuggestions(active) {\n      if (!active) {\n        this.$emit('dismiss');\n      }\n    },\n  },\n\n  methods: {\n    /**\n     *\n     * @param {Number} key\n     * @param {String} label\n     */\n    pickPlaceholder({ key, label }) {\n      this.$emit('item-selected', { id: key, text: label });\n    },\n  },\n});\n\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-1668[0].rules[0].use[0]!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-1668[0].rules[0].use[1]!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-1668[0].rules[0].use[2]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=style&index=0&lang=scss&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-1668[0].rules[0].use[0]!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-1668[0].rules[0].use[1]!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-1668[0].rules[0].use[2]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=style&index=0&lang=scss& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (() => {

eval("// extracted by mini-css-extract-plugin\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-1668%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-1668%5B0%5D.rules%5B0%5D.use%5B1%5D!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-1668%5B0%5D.rules%5B0%5D.use%5B2%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/js/extension.js":
/*!****************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/js/extension.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var weka_notification_placeholder_components_nodes_Placeholder__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! weka_notification_placeholder/components/nodes/Placeholder */ \"weka_notification_placeholder/components/nodes/Placeholder\");\n/* harmony import */ var weka_notification_placeholder_components_nodes_Placeholder__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(weka_notification_placeholder_components_nodes_Placeholder__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var editor_weka_extensions_Base__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! editor_weka/extensions/Base */ \"editor_weka/extensions/Base\");\n/* harmony import */ var editor_weka_extensions_Base__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(editor_weka_extensions_Base__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var _plugin__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./plugin */ \"./client/component/weka_notification_placeholder/src/js/plugin.js\");\n/**\n * This file is part of Totara Enterprise Extensions.\n *\n * Copyright (C) 2021 onwards Totara Learning Solutions LTD\n *\n * Totara Enterprise Extensions is provided only to Totara\n * Learning Solutions LTD's customers and partners, pursuant to\n * the terms and conditions of a separate agreement with Totara\n * Learning Solutions LTD or its affiliate.\n *\n * If you do not have an agreement with Totara Learning Solutions\n * LTD, you may not access, use, modify, or distribute this software.\n * Please contact [licensing@totaralearning.com] for more information.\n *\n * @author Arshad Anwer <arshad.anwer@totaralearning.com>\n * @module weka_notification_placeholder\n */\n\n\n\n\n\nclass PlaceholderExtension extends (editor_weka_extensions_Base__WEBPACK_IMPORTED_MODULE_1___default()) {\n  nodes() {\n    return {\n      totara_notification_placeholder: {\n        schema: {\n          group: 'inline',\n          inline: true,\n          attrs: {\n            key: { default: undefined },\n            label: { default: undefined },\n          },\n          parseDOM: [\n            {\n              tag: 'span.tui-placeholder__text',\n              getAttrs(dom) {\n                try {\n                  return {\n                    key: dom.getAttribute('data-key'),\n                    label: dom.getAttribute('data-label'),\n                  };\n                } catch (e) {\n                  return {};\n                }\n              },\n            },\n          ],\n          toDOM(node) {\n            return [\n              'span',\n              {\n                class: 'tui-placeholder__text',\n                'data-key': node.attrs.key,\n                'data-label': node.attrs.label,\n              },\n              '[' + node.attrs.label + ']',\n            ];\n          },\n        },\n\n        component: (weka_notification_placeholder_components_nodes_Placeholder__WEBPACK_IMPORTED_MODULE_0___default()),\n      },\n    };\n  }\n\n  plugins() {\n    return [\n      (0,_plugin__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(this.editor, this.options.resolver_class_name),\n    ];\n  }\n}\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (opt => new PlaceholderExtension(opt));\n\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/js/extension.js?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/js/plugin.js":
/*!*************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/js/plugin.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"REGEX\": () => (/* binding */ REGEX),\n/* harmony export */   \"default\": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var tui_util__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tui/util */ \"tui/util\");\n/* harmony import */ var tui_util__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(tui_util__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var ext_prosemirror_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ext_prosemirror/state */ \"ext_prosemirror/state\");\n/* harmony import */ var ext_prosemirror_state__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(ext_prosemirror_state__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var editor_weka_helpers_suggestion__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! editor_weka/helpers/suggestion */ \"editor_weka/helpers/suggestion\");\n/* harmony import */ var editor_weka_helpers_suggestion__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(editor_weka_helpers_suggestion__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var weka_notification_placeholder_components_suggestion_Placeholder__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! weka_notification_placeholder/components/suggestion/Placeholder */ \"weka_notification_placeholder/components/suggestion/Placeholder\");\n/* harmony import */ var weka_notification_placeholder_components_suggestion_Placeholder__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(weka_notification_placeholder_components_suggestion_Placeholder__WEBPACK_IMPORTED_MODULE_3__);\n/**\n * This file is part of Totara Enterprise Extensions.\n *\n * Copyright (C) 2021 onwards Totara Learning Solutions LTD\n *\n * Totara Enterprise Extensions is provided only to Totara\n * Learning Solutions LTD's customers and partners, pursuant to\n * the terms and conditions of a separate agreement with Totara\n * Learning Solutions LTD or its affiliate.\n *\n * If you do not have an agreement with Totara Learning Solutions\n * LTD, you may not access, use, modify, or distribute this software.\n * Please contact [licensing@totaralearning.com] for more information.\n *\n * @author Arshad Anwer <arshad.anwer@totaralearning.com>\n * @module weka_notification_placeholder\n */\n\n\n\n\n\n\nconst REGEX = new RegExp(`\\\\[([a-z_:]+]?)?$`, 'ig');\n\n/**\n *\n * @param {Editor} editor\n * @param {String} resolverClassName\n * @return {Plugin}\n */\n/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(editor, resolverClassName) {\n  const key = new ext_prosemirror_state__WEBPACK_IMPORTED_MODULE_1__.PluginKey('placeholders');\n  let suggestion = new (editor_weka_helpers_suggestion__WEBPACK_IMPORTED_MODULE_2___default())(editor);\n\n  return new ext_prosemirror_state__WEBPACK_IMPORTED_MODULE_1__.Plugin({\n    key: key,\n\n    view() {\n      return {\n        /**\n         *\n         * @param {EditorView} view\n         */\n        update: (0,tui_util__WEBPACK_IMPORTED_MODULE_0__.debounce)(view => {\n          const { text, active, range } = this.key.getState(view.state);\n          suggestion.destroyInstance();\n\n          if (!text || !active) {\n            return;\n          } else if (!view.editable) {\n            // Editor is disabled, do not apply anything.\n            return;\n          }\n\n          // remove [ when passing value to state/component\n          const ammendedText = text.slice(1);\n\n          suggestion.showList({\n            view,\n            component: {\n              name: 'totara_notification_placeholder',\n              component: (weka_notification_placeholder_components_suggestion_Placeholder__WEBPACK_IMPORTED_MODULE_3___default()),\n              attrs: (key, label) => {\n                return {\n                  key: key,\n                  label: label,\n                };\n              },\n              props: {\n                resolverClassName: resolverClassName,\n                contextId: editor.identifier.contextId,\n                pattern: ammendedText,\n              },\n            },\n            state: {\n              text: ammendedText,\n              active,\n              range,\n            },\n          });\n        }, 250),\n      };\n    },\n\n    state: {\n      init() {\n        return {\n          active: false,\n          range: {},\n          text: null,\n        };\n      },\n\n      /**\n       *\n       * @param {Transaction} transaction\n       * @param {Object} oldState\n       *\n       * @return {Object}\n       */\n      apply(transaction, oldState) {\n        // Reset last index in order to perform the regex again at the start of the string.\n        REGEX.lastIndex = 0;\n        return suggestion.apply(transaction, oldState, REGEX);\n      },\n    },\n\n    props: {\n      /**\n       *\n       * @param {EditorView} view\n       * @param {KeyboardEvent} event\n       */\n      handleKeyDown(view, event) {\n        if (event.key === 'Escape' || event.key === 'Esc') {\n          const { active } = this.getState(view.state);\n          if (!active) {\n            return false;\n          }\n\n          suggestion.destroyInstance();\n          view.focus();\n          event.stopPropagation();\n\n          // Returning true to stop the the propagation in the parent editor.\n          return true;\n        }\n      },\n    },\n  });\n}\n\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/js/plugin.js?");

/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ normalizeComponent)\n/* harmony export */ });\n/* globals __VUE_SSR_CONTEXT__ */\n\n// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).\n// This module is a runtime utility for cleaner component module output and will\n// be included in the final webpack user bundle.\n\nfunction normalizeComponent (\n  scriptExports,\n  render,\n  staticRenderFns,\n  functionalTemplate,\n  injectStyles,\n  scopeId,\n  moduleIdentifier, /* server only */\n  shadowMode /* vue-cli only */\n) {\n  // Vue.extend constructor export interop\n  var options = typeof scriptExports === 'function'\n    ? scriptExports.options\n    : scriptExports\n\n  // render functions\n  if (render) {\n    options.render = render\n    options.staticRenderFns = staticRenderFns\n    options._compiled = true\n  }\n\n  // functional template\n  if (functionalTemplate) {\n    options.functional = true\n  }\n\n  // scopedId\n  if (scopeId) {\n    options._scopeId = 'data-v-' + scopeId\n  }\n\n  var hook\n  if (moduleIdentifier) { // server build\n    hook = function (context) {\n      // 2.3 injection\n      context =\n        context || // cached call\n        (this.$vnode && this.$vnode.ssrContext) || // stateful\n        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional\n      // 2.2 with runInNewContext: true\n      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {\n        context = __VUE_SSR_CONTEXT__\n      }\n      // inject component styles\n      if (injectStyles) {\n        injectStyles.call(this, context)\n      }\n      // register component module identifier for async chunk inferrence\n      if (context && context._registeredComponents) {\n        context._registeredComponents.add(moduleIdentifier)\n      }\n    }\n    // used by ssr in case component is cached and beforeCreate\n    // never gets called\n    options._ssrRegister = hook\n  } else if (injectStyles) {\n    hook = shadowMode\n      ? function () {\n        injectStyles.call(\n          this,\n          (options.functional ? this.parent : this).$root.$options.shadowRoot\n        )\n      }\n      : injectStyles\n  }\n\n  if (hook) {\n    if (options.functional) {\n      // for template-only hot-reload because in that case the render fn doesn't\n      // go through the normalizer\n      options._injectStyles = hook\n      // register for functional component in vue file\n      var originalRender = options.render\n      options.render = function renderWithStyleInjection (h, context) {\n        hook.call(context)\n        return originalRender(h, context)\n      }\n    } else {\n      // inject component registration as beforeCreate hook\n      var existing = options.beforeCreate\n      options.beforeCreate = existing\n        ? [].concat(existing, hook)\n        : [hook]\n    }\n  }\n\n  return {\n    exports: scriptExports,\n    options: options\n  }\n}\n\n\n//# sourceURL=webpack:///./node_modules/vue-loader/lib/runtime/componentNormalizer.js?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!*************************************************************************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \*************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1671_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1671[0].rules[0].use[0]!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Placeholder.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1671[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=custom&index=0&blockType=lang-strings\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1671_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=template&id=31426794&":
/*!****************************************************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=template&id=31426794& ***!
  \****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_template_id_31426794___WEBPACK_IMPORTED_MODULE_0__.render),\n/* harmony export */   \"staticRenderFns\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_template_id_31426794___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_template_id_31426794___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Placeholder.vue?vue&type=template&id=31426794& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=template&id=31426794&\");\n\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=template&id=9d46c78e&":
/*!*********************************************************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=template&id=9d46c78e& ***!
  \*********************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_template_id_9d46c78e___WEBPACK_IMPORTED_MODULE_0__.render),\n/* harmony export */   \"staticRenderFns\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_template_id_9d46c78e___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_template_id_9d46c78e___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Placeholder.vue?vue&type=template&id=9d46c78e& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=template&id=9d46c78e&\");\n\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Placeholder_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./Placeholder.vue?vue&type=script&lang=js& */ \"./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=script&lang=js&\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Placeholder_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Placeholder_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./Placeholder.vue?vue&type=script&lang=js& */ \"./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=script&lang=js&\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_Placeholder_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?");

/***/ }),

/***/ "./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=style&index=0&lang=scss&":
/*!************************************************************************************************************************************!*\
  !*** ./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=style&index=0&lang=scss& ***!
  \************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_1668_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_1668_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_1668_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-1668[0].rules[0].use[0]!../../../../../tooling/webpack/css_raw_loader.js??clonedRuleSet-1668[0].rules[0].use[1]!../../../../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-1668[0].rules[0].use[2]!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Placeholder.vue?vue&type=style&index=0&lang=scss& */ \"./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-1668[0].rules[0].use[0]!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-1668[0].rules[0].use[1]!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-1668[0].rules[0].use[2]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_1668_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_1668_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_1668_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_1668_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_1668_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_1668_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);\n/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};\n/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_1668_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_1668_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_1668_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== \"default\") __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_1668_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_1668_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_1668_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__[__WEBPACK_IMPORT_KEY__]\n/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((_node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_1668_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_1668_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_1668_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_Placeholder_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default())); \n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=template&id=31426794&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?vue&type=template&id=31426794& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* binding */ render),\n/* harmony export */   \"staticRenderFns\": () => (/* binding */ staticRenderFns)\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('Placeholder',{attrs:{\"placeholder-key\":_vm.placeholderKey,\"display-name\":_vm.displayName}})}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/nodes/Placeholder.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=template&id=9d46c78e&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?vue&type=template&id=9d46c78e& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* binding */ render),\n/* harmony export */   \"staticRenderFns\": () => (/* binding */ staticRenderFns)\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:\"tui-wekaPlaceholderSuggestion\",style:(_vm.positionStyle)},[_c('Dropdown',{attrs:{\"separator\":true,\"open\":_vm.showSuggestions,\"inline-menu\":true},on:{\"dismiss\":function($event){return _vm.$emit('dismiss')}}},[_c('span',{staticClass:\"sr-only\"},[_vm._v(\"\\n      \"+_vm._s(_vm.$str('matching_placeholders', 'editor_weka'))+\":\\n    \")]),_vm._v(\" \"),(_vm.$apollo.loading)?[_c('DropdownItem',{attrs:{\"disabled\":true}},[_c('Loader',{attrs:{\"loading\":true}})],1)]:_vm._e(),_vm._v(\" \"),_vm._l((_vm.placeholders),function(placeholder,index){return _c('DropdownItem',{key:index,attrs:{\"no-padding\":true},on:{\"click\":function($event){return _vm.pickPlaceholder(placeholder)}}},[_c('span',{staticClass:\"tui-wekaPlaceholderSuggestion__label\"},[_vm._v(\"\\n        \"+_vm._s(placeholder.label)+\"\\n      \")])])})],2)],1)}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/weka_notification_placeholder/src/components/suggestion/Placeholder.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "editor_weka/components/nodes/BaseNode":
/*!*************************************************************************!*\
  !*** external "tui.require(\"editor_weka/components/nodes/BaseNode\")" ***!
  \*************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("editor_weka/components/nodes/BaseNode");

/***/ }),

/***/ "editor_weka/extensions/Base":
/*!***************************************************************!*\
  !*** external "tui.require(\"editor_weka/extensions/Base\")" ***!
  \***************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("editor_weka/extensions/Base");

/***/ }),

/***/ "editor_weka/helpers/suggestion":
/*!******************************************************************!*\
  !*** external "tui.require(\"editor_weka/helpers/suggestion\")" ***!
  \******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("editor_weka/helpers/suggestion");

/***/ }),

/***/ "ext_prosemirror/state":
/*!*********************************************************!*\
  !*** external "tui.require(\"ext_prosemirror/state\")" ***!
  \*********************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("ext_prosemirror/state");

/***/ }),

/***/ "totara_notification/components/json_editor/nodes/Placeholder":
/*!************************************************************************************************!*\
  !*** external "tui.require(\"totara_notification/components/json_editor/nodes/Placeholder\")" ***!
  \************************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("totara_notification/components/json_editor/nodes/Placeholder");

/***/ }),

/***/ "tui/components/dropdown/DropdownItem":
/*!************************************************************************!*\
  !*** external "tui.require(\"tui/components/dropdown/DropdownItem\")" ***!
  \************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/dropdown/DropdownItem");

/***/ }),

/***/ "tui/components/dropdown/Dropdown":
/*!********************************************************************!*\
  !*** external "tui.require(\"tui/components/dropdown/Dropdown\")" ***!
  \********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/dropdown/Dropdown");

/***/ }),

/***/ "tui/components/loading/Loader":
/*!*****************************************************************!*\
  !*** external "tui.require(\"tui/components/loading/Loader\")" ***!
  \*****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/loading/Loader");

/***/ }),

/***/ "tui/util":
/*!********************************************!*\
  !*** external "tui.require(\"tui/util\")" ***!
  \********************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/util");

/***/ }),

/***/ "weka_notification_placeholder/components/nodes/Placeholder":
/*!**********************************************************************************************!*\
  !*** external "tui.require(\"weka_notification_placeholder/components/nodes/Placeholder\")" ***!
  \**********************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("weka_notification_placeholder/components/nodes/Placeholder");

/***/ }),

/***/ "weka_notification_placeholder/components/suggestion/Placeholder":
/*!***************************************************************************************************!*\
  !*** external "tui.require(\"weka_notification_placeholder/components/suggestion/Placeholder\")" ***!
  \***************************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("weka_notification_placeholder/components/suggestion/Placeholder");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./client/component/weka_notification_placeholder/src/tui.json");
/******/ 	
/******/ })()
;