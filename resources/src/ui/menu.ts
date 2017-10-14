
import { Container, ContainerType, Item } from "../item";
import { State } from "../state";

const ICON_TEMPLATE = `<span class="fa fa-__GLYPH__" aria-hidden="true"></span> `;
const MENU_TEMPLATE =
`<div class="layout-menu" data-menu="1">
  <a role="button" href="#" title="__TITLE__">
    <span class="fa fa-cog" aria-hidden="true"></span>
    <span class="title">__TITLE__</span>
  </a>
  <ul></ul>
</div>`;

let globalMenuRegistry: Menu[] = [];
let globalDocumentListenerSet = false;

function globalDocumentCloseMenuListener(event: MouseEvent) {
    if (globalMenuRegistry.length) {
        for (let menu of globalMenuRegistry) {
            if (!(event.target instanceof Node) || !menu.element.contains(event.target)) {
                menu.close();
            }
        }
    }
}

class Menu {
    readonly item: Item;
    readonly element: Element;
    readonly master: Element;

    constructor(item: Item, element: Element) {
        this.item = item;
        this.element = element;
        this.master = (<Element>element.querySelector("a"));
        this.master.addEventListener("dblclick", (event: MouseEvent) => {
            event.stopPropagation();
            this.item.toggleCollapse()
        });
        this.master.addEventListener("click", (event: MouseEvent) => {
            event.preventDefault();
            this.open();
        });
    }

    close(): void {
        const dropdown = this.element.querySelector("ul");
        if (dropdown) {
            dropdown.style.display = "none";
        }
    }

    open(): void {
        const dropdown = this.element.querySelector("ul");
        if (dropdown) {
            dropdown.style.display = "block";
        }
    }
}

function createLink(state: State, item: Item, text: string, icon: void | string, callback: () => Promise<void>): Element {
    const menuItem = document.createElement("li");

    const link = document.createElement("a");
    link.setAttribute("href", "#");
    link.setAttribute("role", "button");

    if (icon) {
        link.innerHTML += ICON_TEMPLATE.replace("__GLYPH__", icon);
    }
    link.innerHTML += text;

    link.addEventListener("click", function (event: Event) {
        event.preventDefault();
        event.stopPropagation();

        // @todo loader
        callback().then(_ => {
            // ok
        }).catch(error => {
            // not ok
            state.handler.debug(error);
        });
        // @todo end loader
    });

    menuItem.appendChild(link);

    return menuItem;
}

function createDivider(): Element {
    const divider = document.createElement('li');
    divider.setAttribute("class", "divider");
    divider.setAttribute("role", "separator");
    return divider;
}

function createItemLinks(state: State, item: Item): Element[] {
    const links: Element[] = [];
    const parent = item.getParentContainer();

    links.push(createLink(state, item, "Remove", "remove", () => {
        return state.handler.removeItem(parent.token, parent.layoutId, item.id).then(() => {
            state.remove(item.element);
        });
    }));

    return links;
}

function createHorizontalLinks(state: State, container: Container): Element[] {
    const links: Element[] = [];

    // prepend column: chevron-left layout/ajax/add-column (containerId, 0)
    // append column: chevron-right layout/ajax/add-column (containerId, length)
    links.push(createDivider());
    // remove: remove layout/ajax/remove (itemId)

    return links;
}

function createLayoutLinks(state: State, container: Container): Element[] {
    const links: Element[] = [];

    // options: wrench layout/callback/edit-item (itemId)
    links.push(createDivider());
    // prepend column container: th-large layout/ajax/add-column-container (containerId, position = 0, columnCount = 2)
    // append column container: th-large layout/ajax/add-column-container (containerId, position = length, columnCount = 2)
    links.push(createDivider());
    // prepend item: picture layout/callback/add-item (containerId, position = 0)
    // append item: picture layout/callback/add-item (containerId, position = length)
    // set page content here: star

    return links;
}

function createColumnLinks(state: State, container: Container): Element[] {
    const links = createLayoutLinks(state, container);

    links.push(createDivider());

    // add column before: chevron-left layout/ajax/add-column (parentId, position)
    // add column after: chevron-right layout/ajax/add-column (parentId, position + 1)

    links.push(createDivider());

    links.push(createLink(state, container, "Remove this column", "remove", () => {
        return state.handler.removeItem(container.token, container.layoutId, container.id).then(() => {
            state.remove(container.element);
        });
    }));

    return links;
}

// Create menu for the given item
export function createMenu(state: State, item: Item): void {

    let links: Element[] = [];
    let title: string = "Error";

    if (item instanceof Container) {
        if (item.type === ContainerType.Column) {
            title = "Column";
            links = createColumnLinks(state, item);
        } else if (item.type === ContainerType.Horizontal) {
            title = "Columns container";
            links = createHorizontalLinks(state, item);
        } else {
            title = "Layout";
            links = createLayoutLinks(state, item);
        }
    } else {
        title = "Item";
        links = createItemLinks(state, item);
    }

    // Use a RegExp to handle multiple occurences (using a raw string
    // does not work and only replaces the very first).
    let output = MENU_TEMPLATE.replace(new RegExp('__TITLE__', 'g'), title).replace("__LINKS__", "<li><a>coucou</a></href>");
    let element = document.createElement('div');
    element.innerHTML = output;

    let parentElement = <Element>element.firstElementChild;
    let menuList = (<HTMLElement>parentElement.querySelector("ul"));

    // Add links
    for (let link of links) {
        menuList.appendChild(link);
    }

    if (!globalDocumentListenerSet) {
        document.addEventListener("click", (event: MouseEvent) => {
            globalDocumentCloseMenuListener(event);
        });
        globalDocumentListenerSet = true;
    }

    globalMenuRegistry.push(new Menu(item, parentElement));

    item.element.insertBefore(parentElement, item.element.firstChild);
}
