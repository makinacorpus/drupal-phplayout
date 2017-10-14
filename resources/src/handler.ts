
// Interacts with the store
export interface LayoutHandler {
    debug(message: any): void;
    moveItem(token: string, layout: string, containerId: string, itemId: string, newPosition: number): Promise<void>;
    addItem(token: string, layout: string, containerId: string, itemType: string, itemId: string, position: number, style?: string): Promise<Element>;
    removeItem(token: string, layout: string, itemId: string): Promise<void>;
    // addColumnContainer(token: string, layout: string, containerId: string, position?: number, columnCount?: number, style?: string)
    // addColumn(token: string, layout: string, containerId: string, position?: number)
}
