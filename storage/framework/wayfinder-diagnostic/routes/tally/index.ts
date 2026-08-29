import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TallyController::index
 * @see app/Http/Controllers/TallyController.php:23
 * @route '/tally'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/tally',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TallyController::index
 * @see app/Http/Controllers/TallyController.php:23
 * @route '/tally'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TallyController::index
 * @see app/Http/Controllers/TallyController.php:23
 * @route '/tally'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TallyController::index
 * @see app/Http/Controllers/TallyController.php:23
 * @route '/tally'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const tally = {
    index: Object.assign(index, index),
}

export default tally