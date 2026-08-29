import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AthleteReadinessController::__invoke
 * @see app/Http/Controllers/AthleteReadinessController.php:19
 * @route '/readiness'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/readiness',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AthleteReadinessController::__invoke
 * @see app/Http/Controllers/AthleteReadinessController.php:19
 * @route '/readiness'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AthleteReadinessController::__invoke
 * @see app/Http/Controllers/AthleteReadinessController.php:19
 * @route '/readiness'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AthleteReadinessController::__invoke
 * @see app/Http/Controllers/AthleteReadinessController.php:19
 * @route '/readiness'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const readiness = {
    index: Object.assign(index, index),
}

export default readiness