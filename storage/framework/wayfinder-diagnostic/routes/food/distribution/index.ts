import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MealStubController::consume
 * @see app/Http/Controllers/MealStubController.php:120
 * @route '/food/distribution/{mealEntitlement}/consume'
 */
export const consume = (args: { mealEntitlement: string | number | { id: string | number } } | [mealEntitlement: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: consume.url(args, options),
    method: 'post',
})

consume.definition = {
    methods: ["post"],
    url: '/food/distribution/{mealEntitlement}/consume',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MealStubController::consume
 * @see app/Http/Controllers/MealStubController.php:120
 * @route '/food/distribution/{mealEntitlement}/consume'
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
 * @see app/Http/Controllers/MealStubController.php:120
 * @route '/food/distribution/{mealEntitlement}/consume'
 */
consume.post = (args: { mealEntitlement: string | number | { id: string | number } } | [mealEntitlement: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: consume.url(args, options),
    method: 'post',
})
const distribution = {
    consume: Object.assign(consume, consume),
}

export default distribution