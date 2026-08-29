import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\BilletingVenueController::index
 * @see app/Http/Controllers/BilletingVenueController.php:45
 * @route '/billeting'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/billeting',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BilletingVenueController::index
 * @see app/Http/Controllers/BilletingVenueController.php:45
 * @route '/billeting'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BilletingVenueController::index
 * @see app/Http/Controllers/BilletingVenueController.php:45
 * @route '/billeting'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BilletingVenueController::index
 * @see app/Http/Controllers/BilletingVenueController.php:45
 * @route '/billeting'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const billeting = {
    index: Object.assign(index, index),
}

export default billeting