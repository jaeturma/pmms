import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\InventoryAdjustmentController::store
 * @see app/Http/Controllers/InventoryAdjustmentController.php:33
 * @route '/inventory-adjustments'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/inventory-adjustments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InventoryAdjustmentController::store
 * @see app/Http/Controllers/InventoryAdjustmentController.php:33
 * @route '/inventory-adjustments'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InventoryAdjustmentController::store
 * @see app/Http/Controllers/InventoryAdjustmentController.php:33
 * @route '/inventory-adjustments'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})
const inventoryAdjustments = {
    store: Object.assign(store, store),
}

export default inventoryAdjustments