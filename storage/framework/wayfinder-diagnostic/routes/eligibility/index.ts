import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import reviews from './reviews'
import documents from './documents'
/**
* @see \App\Http\Controllers\EligibilityController::index
 * @see app/Http/Controllers/EligibilityController.php:118
 * @route '/eligibility'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/eligibility',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EligibilityController::index
 * @see app/Http/Controllers/EligibilityController.php:118
 * @route '/eligibility'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EligibilityController::index
 * @see app/Http/Controllers/EligibilityController.php:118
 * @route '/eligibility'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EligibilityController::index
 * @see app/Http/Controllers/EligibilityController.php:118
 * @route '/eligibility'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EligibilityController::approve
 * @see app/Http/Controllers/EligibilityController.php:523
 * @route '/eligibility/reviews/{review}/approve'
 */
export const approve = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: approve.url(args, options),
    method: 'patch',
})

approve.definition = {
    methods: ["patch"],
    url: '/eligibility/reviews/{review}/approve',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\EligibilityController::approve
 * @see app/Http/Controllers/EligibilityController.php:523
 * @route '/eligibility/reviews/{review}/approve'
 */
approve.url = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return approve.definition.url
            .replace('{review}', parsedArgs.review.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EligibilityController::approve
 * @see app/Http/Controllers/EligibilityController.php:523
 * @route '/eligibility/reviews/{review}/approve'
 */
approve.patch = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: approve.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\EligibilityController::returnMethod
 * @see app/Http/Controllers/EligibilityController.php:574
 * @route '/eligibility/reviews/{review}/return'
 */
export const returnMethod = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: returnMethod.url(args, options),
    method: 'patch',
})

returnMethod.definition = {
    methods: ["patch"],
    url: '/eligibility/reviews/{review}/return',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\EligibilityController::returnMethod
 * @see app/Http/Controllers/EligibilityController.php:574
 * @route '/eligibility/reviews/{review}/return'
 */
returnMethod.url = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return returnMethod.definition.url
            .replace('{review}', parsedArgs.review.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EligibilityController::returnMethod
 * @see app/Http/Controllers/EligibilityController.php:574
 * @route '/eligibility/reviews/{review}/return'
 */
returnMethod.patch = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: returnMethod.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\EligibilityController::reject
 * @see app/Http/Controllers/EligibilityController.php:613
 * @route '/eligibility/reviews/{review}/reject'
 */
export const reject = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: reject.url(args, options),
    method: 'patch',
})

reject.definition = {
    methods: ["patch"],
    url: '/eligibility/reviews/{review}/reject',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\EligibilityController::reject
 * @see app/Http/Controllers/EligibilityController.php:613
 * @route '/eligibility/reviews/{review}/reject'
 */
reject.url = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return reject.definition.url
            .replace('{review}', parsedArgs.review.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EligibilityController::reject
 * @see app/Http/Controllers/EligibilityController.php:613
 * @route '/eligibility/reviews/{review}/reject'
 */
reject.patch = (args: { review: number | { id: number } } | [review: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: reject.url(args, options),
    method: 'patch',
})
const eligibility = {
    index: Object.assign(index, index),
reviews: Object.assign(reviews, reviews),
documents: Object.assign(documents, documents),
approve: Object.assign(approve, approve),
return: Object.assign(returnMethod, returnMethod),
reject: Object.assign(reject, reject),
}

export default eligibility