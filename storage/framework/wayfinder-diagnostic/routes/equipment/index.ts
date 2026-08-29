import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EquipmentCategoryController::index
 * @see app/Http/Controllers/EquipmentCategoryController.php:49
 * @route '/equipment'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/equipment',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EquipmentCategoryController::index
 * @see app/Http/Controllers/EquipmentCategoryController.php:49
 * @route '/equipment'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EquipmentCategoryController::index
 * @see app/Http/Controllers/EquipmentCategoryController.php:49
 * @route '/equipment'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EquipmentCategoryController::index
 * @see app/Http/Controllers/EquipmentCategoryController.php:49
 * @route '/equipment'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const equipment = {
    index: Object.assign(index, index),
}

export default equipment