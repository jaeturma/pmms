import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EquipmentReturnController::store
 * @see app/Http/Controllers/EquipmentReturnController.php:31
 * @route '/equipment-returns'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/equipment-returns',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EquipmentReturnController::store
 * @see app/Http/Controllers/EquipmentReturnController.php:31
 * @route '/equipment-returns'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EquipmentReturnController::store
 * @see app/Http/Controllers/EquipmentReturnController.php:31
 * @route '/equipment-returns'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})
const equipmentReturns = {
    store: Object.assign(store, store),
}

export default equipmentReturns