import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\EligibilityController::show
 * @see app/Http/Controllers/EligibilityController.php:223
 * @route '/eligibility/reviews/{review}'
 */
export const show = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/eligibility/reviews/{review}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EligibilityController::show
 * @see app/Http/Controllers/EligibilityController.php:223
 * @route '/eligibility/reviews/{review}'
 */
show.url = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { review: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { review: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    review: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        review: typeof args.review === 'object'
                ? args.review.id
                : args.review,
                }

    return show.definition.url
            .replace('{review}', parsedArgs.review.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EligibilityController::show
 * @see app/Http/Controllers/EligibilityController.php:223
 * @route '/eligibility/reviews/{review}'
 */
show.get = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EligibilityController::show
 * @see app/Http/Controllers/EligibilityController.php:223
 * @route '/eligibility/reviews/{review}'
 */
show.head = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EligibilityController::notifyCoach
 * @see app/Http/Controllers/EligibilityController.php:254
 * @route '/eligibility/reviews/{review}/notify-coach'
 */
export const notifyCoach = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: notifyCoach.url(args, options),
    method: 'post',
})

notifyCoach.definition = {
    methods: ["post"],
    url: '/eligibility/reviews/{review}/notify-coach',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EligibilityController::notifyCoach
 * @see app/Http/Controllers/EligibilityController.php:254
 * @route '/eligibility/reviews/{review}/notify-coach'
 */
notifyCoach.url = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { review: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { review: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    review: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        review: typeof args.review === 'object'
                ? args.review.id
                : args.review,
                }

    return notifyCoach.definition.url
            .replace('{review}', parsedArgs.review.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EligibilityController::notifyCoach
 * @see app/Http/Controllers/EligibilityController.php:254
 * @route '/eligibility/reviews/{review}/notify-coach'
 */
notifyCoach.post = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: notifyCoach.url(args, options),
    method: 'post',
})
const reviews = {
    show: Object.assign(show, show),
notifyCoach: Object.assign(notifyCoach, notifyCoach),
}

export default reviews