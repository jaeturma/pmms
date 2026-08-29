import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::store
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:70
 * @route '/coach/assignment-requests'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/coach/assignment-requests',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::store
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:70
 * @route '/coach/assignment-requests'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::store
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:70
 * @route '/coach/assignment-requests'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::index
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:35
 * @route '/coach/assignment-requests'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/coach/assignment-requests',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::index
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:35
 * @route '/coach/assignment-requests'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::index
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:35
 * @route '/coach/assignment-requests'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::index
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:35
 * @route '/coach/assignment-requests'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::review
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:115
 * @route '/coach/assignment-requests/{coachAssignmentRequest}'
 */
export const review = (args: { coachAssignmentRequest: string | number | { id: string | number } } | [coachAssignmentRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})

review.definition = {
    methods: ["patch"],
    url: '/coach/assignment-requests/{coachAssignmentRequest}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::review
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:115
 * @route '/coach/assignment-requests/{coachAssignmentRequest}'
 */
review.url = (args: { coachAssignmentRequest: string | number | { id: string | number } } | [coachAssignmentRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { coachAssignmentRequest: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { coachAssignmentRequest: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    coachAssignmentRequest: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        coachAssignmentRequest: typeof args.coachAssignmentRequest === 'object'
                ? args.coachAssignmentRequest.id
                : args.coachAssignmentRequest,
                }

    return review.definition.url
            .replace('{coachAssignmentRequest}', parsedArgs.coachAssignmentRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::review
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:115
 * @route '/coach/assignment-requests/{coachAssignmentRequest}'
 */
review.patch = (args: { coachAssignmentRequest: string | number | { id: string | number } } | [coachAssignmentRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::resetPassword
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:152
 * @route '/coach/assignment-requests/{coachAssignmentRequest}/reset-password'
 */
export const resetPassword = (args: { coachAssignmentRequest: string | number | { id: string | number } } | [coachAssignmentRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})

resetPassword.definition = {
    methods: ["post"],
    url: '/coach/assignment-requests/{coachAssignmentRequest}/reset-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::resetPassword
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:152
 * @route '/coach/assignment-requests/{coachAssignmentRequest}/reset-password'
 */
resetPassword.url = (args: { coachAssignmentRequest: string | number | { id: string | number } } | [coachAssignmentRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { coachAssignmentRequest: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { coachAssignmentRequest: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    coachAssignmentRequest: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        coachAssignmentRequest: typeof args.coachAssignmentRequest === 'object'
                ? args.coachAssignmentRequest.id
                : args.coachAssignmentRequest,
                }

    return resetPassword.definition.url
            .replace('{coachAssignmentRequest}', parsedArgs.coachAssignmentRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::resetPassword
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:152
 * @route '/coach/assignment-requests/{coachAssignmentRequest}/reset-password'
 */
resetPassword.post = (args: { coachAssignmentRequest: string | number | { id: string | number } } | [coachAssignmentRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})
const assignmentRequests = {
    store: Object.assign(store, store),
index: Object.assign(index, index),
review: Object.assign(review, review),
resetPassword: Object.assign(resetPassword, resetPassword),
}

export default assignmentRequests