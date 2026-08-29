import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import distribution06e635 from './distribution'
/**
* @see \App\Http\Controllers\MealScheduleController::index
 * @see app/Http/Controllers/MealScheduleController.php:36
 * @route '/food'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/food',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealScheduleController::index
 * @see app/Http/Controllers/MealScheduleController.php:36
 * @route '/food'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealScheduleController::index
 * @see app/Http/Controllers/MealScheduleController.php:36
 * @route '/food'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealScheduleController::index
 * @see app/Http/Controllers/MealScheduleController.php:36
 * @route '/food'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MealStubController::distribution
 * @see app/Http/Controllers/MealStubController.php:54
 * @route '/food/distribution'
 */
export const distribution = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: distribution.url(options),
    method: 'get',
})

distribution.definition = {
    methods: ["get","head"],
    url: '/food/distribution',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealStubController::distribution
 * @see app/Http/Controllers/MealStubController.php:54
 * @route '/food/distribution'
 */
distribution.url = (options?: RouteQueryOptions) => {
    return distribution.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealStubController::distribution
 * @see app/Http/Controllers/MealStubController.php:54
 * @route '/food/distribution'
 */
distribution.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: distribution.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealStubController::distribution
 * @see app/Http/Controllers/MealStubController.php:54
 * @route '/food/distribution'
 */
distribution.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: distribution.url(options),
    method: 'head',
})
const food = {
    index: Object.assign(index, index),
distribution: Object.assign(distribution, distribution06e635),
}

export default food