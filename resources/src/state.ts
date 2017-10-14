
import { Drake } from "dragula";
import { Container, getContainer, getItem } from "./item";
import { createMenu } from "./ui/menu";
import { LayoutHandler } from "./handler";

export class State {
    readonly handler: LayoutHandler;
    readonly containers: Container[] = [];
    readonly drake: Drake;

    constructor(handler: LayoutHandler) {
        this.handler = handler;

        this.drake = dragula({
            copy: (element: Element, source: Element): boolean => {
                return getContainer(source).readonly || getItem(element).readonly;
            },
            accepts: (element: Element, target: Element): boolean => {
                return !getContainer(target).readonly;
            },
            invalid: (element: Element): boolean => {
                // This is not really documented in dragula or I could not find
                // it but when drag starts, it might start with any children in
                // the DOM, and we need to give dragula the right item to move
                // which may be any of the current element's parents.
                let current = element;
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

        // We need to add an extra callback to ensure that onDrop and onOver
        // class methods will not be applied to the event target and will keep
        // 'this' as a reference to the State class instance.
        this.drake.on('drop', (element: Element, target: Element, source: Element, sibling?: Element) => {
            this.onDrop(element, target, source, sibling);
        });

        this.drake.on('over', (element: Element, source: Element) => {
            this.onOver(element, source)
        });
    }

    // Terminate an element completly, at least from this API perspective.
    // This means we get rid of the element if it is in containers list but
    // also terminate the element from DOM by removing it from its parent.
    // Please note this will not call any kind of unitializer.
    remove(element: Element) {
        const index = this.drake.containers.indexOf(element);
        if (-1 !== index) {
            this.drake.containers.splice(index);
        }
        if (element.parentElement) {
            element.parentElement.removeChild(element);
        }
    }

    cancel(error?: any, element?: Element) {
        if (error) {
            this.handler.debug(error);
        }
        if (element) {
            element.remove();
        }
        this.drake.cancel(true);
    }

    onOver (element: Element, source: Element) {
        if (element instanceof HTMLElement) {
            element.style.cssFloat = 'none'; // Avoid visual glitches
        }
    }

    onDrop(element: Element, target: Element, source: Element, sibling?: Element) {
        try {
            const container = getContainer(target);
            const item = getItem(element);

            if (container.readonly) {
                throw `container is readonly`;
            }

            if (item.readonly) {
                this.handler.addItem(container.token, container.layoutId,
                    container.id, item.type, item.id, item.position)
                .then(item => {
                    (<Element>element.parentElement).replaceChild(item, element);
                    this.init(element);
                }) .catch(error => {
                    this.cancel(error, element);
                });
            } else {
                this.handler.moveItem(container.token, container.layoutId,
                    container.id, item.id, item.position)
                .catch(error => {
                    this.cancel(error, element)
                });
            }
        } catch (error) {
            // This is run synchronously (not in a promise): drake.cancel()
            // method will correctly revert the operation, dragula won't have
            // run its cleanup() method yet
            this.cancel(error);
        }
    }

    // Collect new DOM information and initialize behaviours
    init(context: Element): void {
        this.collectContainers(context);
        this.collectSources(context);
    }

    // In given document context, find all source containers
    private collectSources(context: Element) {
        for (let source of <Element[]><any>context.querySelectorAll("[data-layout-source]")) {
            this.drake.containers.push(source);
        }
    }

    // Collect items in, should always be inside a container
    private collectItems(context: Element) {
        for (let itemElement of <Element[]><any>context.querySelectorAll("[data-item-type]:not([draggable])")) {
            try {
                const item = getItem(itemElement);
                if (!item.readonly) {
                    createMenu(this, item);
                }
                itemElement.setAttribute("draggable", "1");
            } catch (error) {
                console.log(error);
            }
        }
    }

    // In given document context, find all containers and connect
    // them to potential sources
    private collectContainers(context: Element) {
        for (let layout of <Element[]><any>context.querySelectorAll("[data-layout]")) {
            if (!layout.hasAttribute("data-token") || !layout.hasAttribute("data-id")) {
                continue;
            }

            let token = <string>layout.getAttribute("data-token");
            let layoutId = <string>layout.getAttribute("data-id");

            // [droppable] elements have already been initialized, drop them
            for (let container of <Element[]><any>layout.querySelectorAll("[data-container]:not([droppable])")) {
                if (!container.hasAttribute("data-id")) {
                    continue;
                }

                container.setAttribute("data-token", token);
                container.setAttribute("data-layout-id", layoutId);
                createMenu(this, getContainer(container));

                if (!container.hasAttribute("data-readonly")) {
                    container.setAttribute("droppable", "1");
                    this.drake.containers.push(container);
                }
            }

            this.collectItems(layout);
        }
    }
}
