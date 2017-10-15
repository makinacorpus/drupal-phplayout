/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 1);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var __extends = (this && this.__extends) || (function () {
    var extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
function findPosition(element, attribute) {
    var pos = 0;
    if (element.parentNode) {
        for (var _i = 0, _a = element.parentNode.childNodes; _i < _a.length; _i++) {
            var sibling = _a[_i];
            if (sibling instanceof Element) {
                if (element === sibling) {
                    break;
                }
                if (sibling.hasAttribute(attribute)) {
                    pos++;
                }
            }
        }
    }
    return pos;
}
function toggleClass(element, cssClass) {
    if (element.classList.contains(cssClass)) {
        element.classList.remove(cssClass);
    }
    else {
        element.classList.add(cssClass);
    }
}
function findItemPosition(element) {
    return findPosition(element, "data-item-id");
}
function findContainerPosition(element) {
    return findPosition(element, "data-id");
}
function getContainerCount(element) {
    var count = 0;
    for (var _i = 0, _a = element.childNodes; _i < _a.length; _i++) {
        var child = _a[_i];
        if (child instanceof Element) {
            if (child.hasAttribute("data-id") || child.hasAttribute("data-item-type")) {
                count++;
            }
        }
    }
    return count;
}
exports.getContainerCount = getContainerCount;
var ContainerType;
(function (ContainerType) {
    ContainerType["Column"] = "vbox";
    ContainerType["Horizontal"] = "hbox";
    ContainerType["Layout"] = "Layout";
})(ContainerType = exports.ContainerType || (exports.ContainerType = {}));
function getLayout(element) {
    if (element.hasAttribute("data-token") || !element.hasAttribute("data-layout-id") || !element.hasAttribute("data-id")) {
        return new Container(element.getAttribute("data-id"), element.getAttribute("data-container"), element, element.getAttribute("data-token"), element.getAttribute("data-id"));
    }
    throw "element is not a container, or is not initialized properly";
}
exports.getLayout = getLayout;
function getContainer(element) {
    if (element.hasAttribute("data-token") || !element.hasAttribute("data-layout-id") || !element.hasAttribute("data-id")) {
        return new Container(element.getAttribute("data-id"), element.getAttribute("data-container"), element, element.getAttribute("data-token"), element.getAttribute("data-layout-id"));
    }
    throw "element is not a container, or is not initialized properly";
}
exports.getContainer = getContainer;
function getItem(element) {
    if (element.hasAttribute("data-item-id")) {
        return new Item((element.getAttribute("data-id") || element.getAttribute("data-item-id")), (element.getAttribute("data-item-type") || "null"), !element.hasAttribute("data-id"), element);
    }
    throw "element is not an item";
}
exports.getItem = getItem;
var Item = (function () {
    function Item(id, type, readonly, element) {
        this.id = id;
        this.type = type;
        this.readonly = readonly;
        this.element = element;
    }
    Item.prototype.getPosition = function () {
        return findItemPosition(this.element);
    };
    Item.prototype.toggleCollapse = function () {
        toggleClass(this.element, "collapsed");
    };
    Item.prototype.findParentElement = function () {
        var current = this.element;
        while (current.parentElement) {
            current = current.parentElement;
            if (current.hasAttribute("data-id")) {
                return current;
            }
        }
        throw "Parent has not identifier";
    };
    Item.prototype.getParentContainer = function () {
        return getContainer(this.findParentElement());
    };
    Item.prototype.findParentId = function () {
        return this.findParentElement().getAttribute("data-id") || "";
    };
    return Item;
}());
exports.Item = Item;
var Container = (function (_super) {
    __extends(Container, _super);
    function Container(id, type, element, token, layoutId) {
        var _this = _super.call(this, id, type, type === ContainerType.Horizontal, element) || this;
        _this.layoutId = layoutId;
        _this.token = token;
        return _this;
    }
    Container.prototype.getPosition = function () {
        return findContainerPosition(this.element);
    };
    return Container;
}(Item));
exports.Container = Container;


/***/ }),
/* 1 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__less_edit_less__ = __webpack_require__(2);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__less_edit_less___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0__less_edit_less__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__src_drupal__ = __webpack_require__(3);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__src_drupal___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_1__src_drupal__);




/***/ }),
/* 2 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var __extends = (this && this.__extends) || (function () {
    var extendStatics = Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
        function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
__webpack_require__(4);
var ajax_1 = __webpack_require__(5);
var state_1 = __webpack_require__(6);
var state;
var DrupalState = (function (_super) {
    __extends(DrupalState, _super);
    function DrupalState() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    DrupalState.prototype.init = function (context) {
        _super.prototype.init.call(this, context);
        Drupal.attachBehaviors(context);
    };
    DrupalState.prototype.initNoBehaviors = function (context) {
        _super.prototype.init.call(this, context);
    };
    return DrupalState;
}(state_1.State));
Drupal.behaviors.Layout = {
    attach: function (context, settings) {
        if (!state) {
            state = new DrupalState(new ajax_1.AjaxLayoutHandler(settings.layout.baseurl, settings.layout.destination));
        }
        state.initNoBehaviors(context);
    }
};


/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";



/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : new P(function (resolve) { resolve(result.value); }).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = y[op[0] & 2 ? "return" : op[0] ? "throw" : "next"]) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [0, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
Object.defineProperty(exports, "__esModule", { value: true });
var AjaxRoute;
(function (AjaxRoute) {
    AjaxRoute["Add"] = "layout/ajax/add-item";
    AjaxRoute["AddColumn"] = "layout/ajax/add-column";
    AjaxRoute["AddColumnContainer"] = "layout/ajax/add-column-container";
    AjaxRoute["Move"] = "layout/ajax/move";
    AjaxRoute["Remove"] = "layout/ajax/remove";
})(AjaxRoute || (AjaxRoute = {}));
var AjaxLayoutHandler = (function () {
    function AjaxLayoutHandler(baseUrl, destination) {
        this.baseUrl = baseUrl;
        this.destination = destination;
    }
    AjaxLayoutHandler.prototype.buildFormData = function (data) {
        var formData = new FormData();
        for (var key in data) {
            formData.append(key, data[key]);
        }
        return formData;
    };
    AjaxLayoutHandler.prototype.request = function (route, data) {
        var _this = this;
        return new Promise(function (resolve, reject) {
            var req = new XMLHttpRequest();
            req.open('POST', _this.baseUrl + route);
            req.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            req.addEventListener("load", function () {
                if (this.status !== 200) {
                    reject(this.status + ": " + this.statusText);
                }
                else {
                    resolve(req);
                }
            });
            req.addEventListener("error", function () {
                reject(this.status + ": " + this.statusText);
            });
            req.send(_this.buildFormData(data));
        });
    };
    AjaxLayoutHandler.prototype.createElementFromResponse = function (req) {
        var data = JSON.parse(req.responseText);
        if (!data || !data.success || !data.output) {
            throw req.status + ": " + req.statusText + ": got invalid response data";
        }
        var element = document.createElement('div');
        element.innerHTML = data.output;
        if (!(element.firstElementChild instanceof Element)) {
            throw req.status + ": " + req.statusText + ": got invalid response html output";
        }
        return element.firstElementChild;
    };
    AjaxLayoutHandler.prototype.debug = function (message) {
        console.log("layout error: " + message);
    };
    AjaxLayoutHandler.prototype.moveItem = function (token, layout, containerId, itemId, newPosition) {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4, this.request(AjaxRoute.Move, {
                            token: token,
                            layout: layout,
                            containerId: containerId,
                            itemId: itemId,
                            newPosition: newPosition,
                            destination: this.destination
                        })];
                    case 1:
                        _a.sent();
                        return [2];
                }
            });
        });
    };
    AjaxLayoutHandler.prototype.addItem = function (token, layout, containerId, itemType, itemId, position, style) {
        return __awaiter(this, void 0, void 0, function () {
            var req;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4, this.request(AjaxRoute.Add, {
                            token: token,
                            layout: layout,
                            containerId: containerId,
                            itemType: itemType,
                            itemId: itemId,
                            position: position,
                            style: "default",
                            destination: this.destination
                        })];
                    case 1:
                        req = _a.sent();
                        return [2, this.createElementFromResponse(req)];
                }
            });
        });
    };
    AjaxLayoutHandler.prototype.removeItem = function (token, layout, itemId) {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4, this.request(AjaxRoute.Remove, {
                            token: token,
                            layout: layout,
                            itemId: itemId,
                            destination: this.destination
                        })];
                    case 1:
                        _a.sent();
                        return [2];
                }
            });
        });
    };
    AjaxLayoutHandler.prototype.addColumnContainer = function (token, layout, containerId, position, columnCount, style) {
        return __awaiter(this, void 0, void 0, function () {
            var req;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4, this.request(AjaxRoute.AddColumnContainer, {
                            token: token,
                            layout: layout,
                            containerId: containerId,
                            position: position,
                            columnCount: columnCount || 2,
                            style: style || "default",
                            destination: this.destination
                        })];
                    case 1:
                        req = _a.sent();
                        return [2, this.createElementFromResponse(req)];
                }
            });
        });
    };
    AjaxLayoutHandler.prototype.addColumn = function (token, layout, containerId, position) {
        return __awaiter(this, void 0, void 0, function () {
            var req;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4, this.request(AjaxRoute.AddColumn, {
                            token: token,
                            layout: layout,
                            containerId: containerId,
                            position: position || 0,
                            destination: this.destination
                        })];
                    case 1:
                        req = _a.sent();
                        return [2, this.createElementFromResponse(req)];
                }
            });
        });
    };
    return AjaxLayoutHandler;
}());
exports.AjaxLayoutHandler = AjaxLayoutHandler;


/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var item_1 = __webpack_require__(0);
var menu_1 = __webpack_require__(7);
var State = (function () {
    function State(handler) {
        var _this = this;
        this.containers = [];
        this.handler = handler;
        this.drake = dragula({
            copy: function (element, source) {
                return item_1.getContainer(source).readonly || item_1.getItem(element).readonly;
            },
            accepts: function (element, target) {
                return !item_1.getContainer(target).readonly;
            },
            invalid: function (element) {
                var current = element;
                while (current) {
                    if (current.hasAttribute("data-menu")) {
                        return true;
                    }
                    if (current.hasAttribute("data-item-id")) {
                        return false;
                    }
                    if (!current.parentElement) {
                        break;
                    }
                    current = current.parentElement;
                }
                return true;
            },
            revertOnSpill: true,
            removeOnSpill: false,
            direction: 'vertical'
        });
        this.drake.on('drop', function (element, target, source, sibling) {
            _this.onDrop(element, target, source, sibling);
        });
        this.drake.on('over', function (element, source) {
            _this.onOver(element, source);
        });
    }
    State.prototype.remove = function (element) {
        var index = this.drake.containers.indexOf(element);
        if (-1 !== index) {
            this.drake.containers.splice(index);
        }
        if (element.parentElement) {
            element.parentElement.removeChild(element);
        }
    };
    State.prototype.cancel = function (error, element) {
        if (error) {
            this.handler.debug(error);
        }
        if (element) {
            element.remove();
        }
        this.drake.cancel(true);
    };
    State.prototype.onOver = function (element, source) {
        if (element instanceof HTMLElement) {
            element.style.cssFloat = 'none';
        }
    };
    State.prototype.onDrop = function (element, target, source, sibling) {
        var _this = this;
        try {
            var container = item_1.getContainer(target);
            var item = item_1.getItem(element);
            if (container.readonly) {
                throw "container is readonly";
            }
            if (item.readonly) {
                this.handler.addItem(container.token, container.layoutId, container.id, item.type, item.id, item.getPosition())
                    .then(function (item) {
                    element.parentElement.replaceChild(item, element);
                    _this.init(element);
                }).catch(function (error) {
                    _this.cancel(error, element);
                });
            }
            else {
                this.handler.moveItem(container.token, container.layoutId, container.id, item.id, item.getPosition())
                    .catch(function (error) {
                    _this.cancel(error, element);
                });
            }
        }
        catch (error) {
            this.cancel(error);
        }
    };
    State.prototype.init = function (context) {
        this.collectLayouts(context);
        this.collectSources(context);
    };
    State.prototype.initContainer = function (element, parent) {
        if (element.hasAttribute("droppable")) {
            return;
        }
        if (!element.hasAttribute("data-container") || !element.hasAttribute("data-id")) {
            console.log("not a container");
            console.log(element);
            return;
        }
        element.setAttribute("data-token", parent.token);
        element.setAttribute("data-layout-id", parent.layoutId);
        var container = item_1.getContainer(element);
        if (container.type !== item_1.ContainerType.Horizontal) {
            element.setAttribute("droppable", "1");
            this.drake.containers.push(container.element);
        }
        this.collectItems(container);
        menu_1.createMenu(this, container);
    };
    State.prototype.initItem = function (element, parent) {
        if (element.hasAttribute("draggable")) {
            return;
        }
        if (!element.hasAttribute("data-item-id")) {
            console.log("not an item");
            console.log(element);
            return;
        }
        var item = item_1.getItem(element);
        element.setAttribute("draggable", "1");
        if (!item.readonly) {
            menu_1.createMenu(this, item);
        }
    };
    State.prototype.collectSources = function (context) {
        for (var _i = 0, _a = context.querySelectorAll("[data-layout-source]"); _i < _a.length; _i++) {
            var source = _a[_i];
            this.drake.containers.push(source);
        }
    };
    State.prototype.collectItems = function (container) {
        for (var _i = 0, _a = container.element.childNodes; _i < _a.length; _i++) {
            var element = _a[_i];
            if (element instanceof Element) {
                if (element.hasAttribute("data-item-id")) {
                    this.initItem(element, container);
                }
                else {
                    this.initContainer(element, container);
                }
            }
        }
    };
    State.prototype.collectLayouts = function (context) {
        for (var _i = 0, _a = context.querySelectorAll("[data-layout]"); _i < _a.length; _i++) {
            var element = _a[_i];
            if (!element.hasAttribute("data-token") || !element.hasAttribute("data-id")) {
                continue;
            }
            var layout = item_1.getLayout(element);
            element.setAttribute("droppable", "1");
            this.drake.containers.push(layout.element);
            this.collectItems(layout);
            menu_1.createMenu(this, layout);
        }
    };
    return State;
}());
exports.State = State;


/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

Object.defineProperty(exports, "__esModule", { value: true });
var item_1 = __webpack_require__(0);
var ICON_TEMPLATE = "<span class=\"fa fa-__GLYPH__\" aria-hidden=\"true\"></span> ";
var DRAG_TEMPLATE = "<a role=\"drag\" title=\"Maintain left mouse button to move\">\n  <span class=\"fa fa-arrows\" aria-hidden=\"true\"></span>\n</a>";
var MENU_TEMPLATE = "<div class=\"layout-menu\" data-menu=\"1\">\n  <a role=\"button\" href=\"#\" title=\"Click to open, double-click to expand/hide content\">\n    <span class=\"fa fa-cog\" aria-hidden=\"true\"></span>\n    <span class=\"title\">__TITLE__</span>\n  </a>\n  <ul></ul>\n</div>";
var globalMenuRegistry = [];
var globalDocumentListenerSet = false;
function globalDocumentCloseMenuListener(event) {
    if (globalMenuRegistry.length) {
        for (var _i = 0, globalMenuRegistry_1 = globalMenuRegistry; _i < globalMenuRegistry_1.length; _i++) {
            var menu = globalMenuRegistry_1[_i];
            if (!(event.target instanceof Node) || !menu.element.contains(event.target)) {
                menu.close();
            }
        }
    }
}
var Menu = (function () {
    function Menu(item, element) {
        var _this = this;
        this.item = item;
        this.element = element;
        this.master = element.querySelector("a");
        this.master.addEventListener("dblclick", function (event) {
            event.stopPropagation();
            _this.item.toggleCollapse();
        });
        this.master.addEventListener("click", function (event) {
            event.preventDefault();
            _this.open();
        });
    }
    Menu.prototype.close = function () {
        var dropdown = this.element.querySelector("ul");
        if (dropdown) {
            dropdown.style.display = "none";
        }
    };
    Menu.prototype.open = function () {
        var dropdown = this.element.querySelector("ul");
        if (dropdown) {
            dropdown.style.display = "block";
        }
    };
    return Menu;
}());
function createLink(state, item, text, icon, callback) {
    var menuItem = document.createElement("li");
    var link = document.createElement("a");
    link.setAttribute("href", "#");
    link.setAttribute("role", "button");
    if (icon) {
        link.innerHTML += ICON_TEMPLATE.replace("__GLYPH__", icon);
    }
    link.innerHTML += text;
    link.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        callback().then(function (_) {
        }).catch(function (error) {
            state.handler.debug(error);
        });
    });
    menuItem.appendChild(link);
    return menuItem;
}
function createDivider() {
    var divider = document.createElement('li');
    divider.setAttribute("class", "divider");
    divider.setAttribute("role", "separator");
    return divider;
}
function createItemLinks(state, item) {
    var links = [];
    var parent = item.getParentContainer();
    links.push(createLink(state, item, "Remove", "remove", function () {
        return state.handler.removeItem(parent.token, parent.layoutId, item.id).then(function () {
            state.remove(item.element);
        });
    }));
    return links;
}
function createHorizontalLinks(state, container) {
    var links = [];
    links.push(createLink(state, container, "Add column to left", "chevron-left", function () {
        return state.handler.addColumn(container.token, container.layoutId, container.id, 0).then(function (element) {
            container.element.insertBefore(element, container.element.firstChild);
            state.init(element);
            state.initContainer(element, container);
        });
    }));
    links.push(createLink(state, container, "Add column to right", "chevron-right", function () {
        var position = item_1.getContainerCount(container.element);
        return state.handler.addColumn(container.token, container.layoutId, container.id, position).then(function (element) {
            container.element.appendChild(element);
            state.init(element);
            state.initContainer(element, container);
        });
    }));
    links.push(createDivider());
    links.push(createLink(state, container, "Remove", "remove", function () {
        return state.handler.removeItem(container.token, container.layoutId, container.id).then(function () {
            state.remove(container.element);
        });
    }));
    return links;
}
function createLayoutLinks(state, container) {
    var links = [];
    links.push(createDivider());
    links.push(createLink(state, container, "Add columns to top", "columns", function () {
        return state.handler.addColumnContainer(container.token, container.layoutId, container.id, 0).then(function (element) {
            container.element.insertBefore(element, container.element.firstChild);
            state.init(element);
            state.initContainer(element, container);
        });
    }));
    links.push(createLink(state, container, "Add columns to bottom", "columns", function () {
        var position = item_1.getContainerCount(container.element);
        return state.handler.addColumn(container.token, container.layoutId, container.id, position).then(function (element) {
            container.element.appendChild(element);
            state.init(element);
            state.initContainer(element, container);
        });
    }));
    links.push(createDivider());
    return links;
}
function createColumnLinks(state, container) {
    var links = createLayoutLinks(state, container);
    var parent = container.getParentContainer();
    links.push(createDivider());
    links.push(createLink(state, container, "Add column before", "chevron-left", function () {
        return state.handler.addColumn(parent.token, parent.layoutId, parent.id, container.getPosition()).then(function (element) {
            parent.element.insertBefore(element, container.element);
            state.init(element);
            state.initContainer(element, parent);
        });
    }));
    links.push(createLink(state, container, "Add column after", "chevron-right", function () {
        return state.handler.addColumn(parent.token, parent.layoutId, parent.id, container.getPosition() + 1).then(function (element) {
            parent.element.insertBefore(element, container.element.nextSibling);
            state.init(element);
            state.initContainer(element, parent);
        });
    }));
    links.push(createDivider());
    links.push(createLink(state, container, "Remove this column", "remove", function () {
        return state.handler.removeItem(container.token, container.layoutId, container.id).then(function () {
            state.remove(container.element);
        });
    }));
    return links;
}
function createMenu(state, item) {
    var links = [];
    var title = "Error";
    var addDragIcon = false;
    if (item instanceof item_1.Container) {
        if (item.type === item_1.ContainerType.Column) {
            title = "Column";
            links = createColumnLinks(state, item);
        }
        else if (item.type === item_1.ContainerType.Horizontal) {
            title = "Columns container";
            links = createHorizontalLinks(state, item);
            addDragIcon = true;
        }
        else {
            title = "Layout";
            links = createLayoutLinks(state, item);
        }
    }
    else {
        title = "Item";
        links = createItemLinks(state, item);
        addDragIcon = true;
    }
    var output = MENU_TEMPLATE.replace(new RegExp('__TITLE__', 'g'), title).replace("__LINKS__", "<li><a>coucou</a></href>");
    var element = document.createElement('div');
    element.innerHTML = output;
    var parentElement = element.firstElementChild;
    var menuList = parentElement.querySelector("ul");
    for (var _i = 0, links_1 = links; _i < links_1.length; _i++) {
        var link = links_1[_i];
        menuList.appendChild(link);
    }
    if (!globalDocumentListenerSet) {
        document.addEventListener("click", function (event) {
            globalDocumentCloseMenuListener(event);
        });
        globalDocumentListenerSet = true;
    }
    globalMenuRegistry.push(new Menu(item, parentElement));
    item.element.insertBefore(parentElement, item.element.firstChild);
}
exports.createMenu = createMenu;


/***/ })
/******/ ]);
//# sourceMappingURL=edit.js.map