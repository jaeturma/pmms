import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::review
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:177
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}'
 */
export const review = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})

review.definition = {
    methods: ["patch"],
    url: '/coach/onboarding-requests/{coachOnboardingRequest}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::review
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:177
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}'
 */
review.url = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { coachOnboardingRequest: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { coachOnboardingRequest: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    coachOnboardingRequest: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        coachOnboardingRequest: typeof args.coachOnboardingRequest === 'object'
                ? args.coachOnboardingRequest.id
                : args.coachOnboardingRequest,
                }

    return review.definition.url
            .replace('{coachOnboardingRequest}', parsedArgs.coachOnboardingRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::review
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:177
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}'
 */
review.patch = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::resetPassword
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:525
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/reset-password'
 */
export const resetPassword = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})

resetPassword.definition = {
    methods: ["post"],
    url: '/coach/onboarding-requests/{coachOnboardingRequest}/reset-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::resetPassword
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:525
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/reset-password'
 */
resetPassword.url = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { coachOnboardingRequest: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { coachOnboardingRequest: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    coachOnboardingRequest: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        coachOnboardingRequest: typeof args.coachOnboardingRequest === 'object'
                ? args.coachOnboardingRequest.id
                : args.coachOnboardingRequest,
                }

    return resetPassword.definition.url
            .replace('{coachOnboardingRequest}', parsedArgs.coachOnboardingRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::resetPassword
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:525
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/reset-password'
 */
resetPassword.post = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})
const onboardingRequests = {
    review: Object.assign(review, review),
resetPassword: Object.assign(resetPassword, resetPassword),
}

export default onboardingRequests