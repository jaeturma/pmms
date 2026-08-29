import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MealStubController::show
 * @see app/Http/Controllers/MealStubController.php:26
 * @route '/meal-stub'
 */
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/meal-stub',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MealStubController::show
 * @see app/Http/Controllers/MealStubController.php:26
 * @route '/meal-stub'
 */
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealStubController::show
 * @see app/Http/Controllers/MealStubController.php:26
 * @route '/meal-stub'
 */
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MealStubController::show
 * @see app/Http/Controllers/MealStubController.php:26
 * @route '/meal-stub'
 */
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MealStubController::consume
 * @see app/Http/Controllers/MealStubController.php:45
 * @route '/meal-stub/{mealEntitlement}/consume'
 */
export const consume = (args: { mealEntitlement: string | number | { id: string | number } } | [mealEntitlement: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: consume.url(args, options),
    method: 'post',
})

consume.definition = {
    methods: ["post"],
    url: '/meal-stub/{mealEntitlement}/consume',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MealStubController::consume
 * @see app/Http/Controllers/MealStubController.php:45
 * @route '/meal-stub/{mealEntitlement}/consume'
 */
consume.url = (args: { mealEntitlement: string | number | { id: string | number } } | [mealEntitlement: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { mealEntitlement: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { mealEntitlement: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    mealEntitlement: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        mealEntitlement: typeof args.mealEntitlement === 'object'
                ? args.mealEntitlement.id
                : args.mealEntitlement,
                }

    return consume.definition.url
            .replace('{mealEntitlement}', parsedArgs.mealEntitlement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealStubController::consume
 * @see app/Http/Controllers/MealStubController.php:45
 * @route '/meal-stub/{mealEntitlement}/consume'
 */
consume.post = (args: { mealEntitlement: string | number | { id: string | number } } | [mealEntitlement: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: consume.url(args, options),
    method: 'post',
})
const mealStub = {
    show: Object.assign(show, show),
consume: Object.assign(consume, consume),
}

export default mealStub