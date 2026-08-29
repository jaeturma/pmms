import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ManagementDashboardController::index
 * @see app/Http/Controllers/ManagementDashboardController.php:61
 * @route '/management'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/management',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ManagementDashboardController::index
 * @see app/Http/Controllers/ManagementDashboardController.php:61
 * @route '/management'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementDashboardController::index
 * @see app/Http/Controllers/ManagementDashboardController.php:61
 * @route '/management'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ManagementDashboardController::index
 * @see app/Http/Controllers/ManagementDashboardController.php:61
 * @route '/management'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const management = {
    index: Object.assign(index, index),
}

export default management