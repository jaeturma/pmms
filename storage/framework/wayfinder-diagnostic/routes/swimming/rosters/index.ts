import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\SwimmingRosterController::index
 * @see app/Http/Controllers/SwimmingRosterController.php:23
 * @route '/swimming/rosters'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/swimming/rosters',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SwimmingRosterController::index
 * @see app/Http/Controllers/SwimmingRosterController.php:23
 * @route '/swimming/rosters'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SwimmingRosterController::index
 * @see app/Http/Controllers/SwimmingRosterController.php:23
 * @route '/swimming/rosters'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SwimmingRosterController::index
 * @see app/Http/Controllers/SwimmingRosterController.php:23
 * @route '/swimming/rosters'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const rosters = {
    index: Object.assign(index, index),
}

export default rosters