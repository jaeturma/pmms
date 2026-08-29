import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\VehicleController::index
 * @see app/Http/Controllers/VehicleController.php:44
 * @route '/transport'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/transport',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\VehicleController::index
 * @see app/Http/Controllers/VehicleController.php:44
 * @route '/transport'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VehicleController::index
 * @see app/Http/Controllers/VehicleController.php:44
 * @route '/transport'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\VehicleController::index
 * @see app/Http/Controllers/VehicleController.php:44
 * @route '/transport'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const transport = {
    index: Object.assign(index, index),
}

export default transport