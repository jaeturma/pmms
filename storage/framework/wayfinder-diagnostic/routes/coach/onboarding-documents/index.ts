import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::show
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:469
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}'
 */
export const show = (args: { coachOnboardingRequest: string | number | { id: string | number }, type: string | number } | [coachOnboardingRequest: string | number | { id: string | number }, type: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::show
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:469
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}'
 */
show.url = (args: { coachOnboardingRequest: string | number | { id: string | number }, type: string | number } | [coachOnboardingRequest: string | number | { id: string | number }, type: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    coachOnboardingRequest: args[0],
                    type: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        coachOnboardingRequest: typeof args.coachOnboardingRequest === 'object'
                ? args.coachOnboardingRequest.id
                : args.coachOnboardingRequest,
                                type: args.type,
                }

    return show.definition.url
            .replace('{coachOnboardingRequest}', parsedArgs.coachOnboardingRequest.toString())
            .replace('{type}', parsedArgs.type.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::show
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:469
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}'
 */
show.get = (args: { coachOnboardingRequest: string | number | { id: string | number }, type: string | number } | [coachOnboardingRequest: string | number | { id: string | number }, type: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::show
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:469
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}'
 */
show.head = (args: { coachOnboardingRequest: string | number | { id: string | number }, type: string | number } | [coachOnboardingRequest: string | number | { id: string | number }, type: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::store
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:481
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}'
 */
export const store = (args: { coachOnboardingRequest: string | number | { id: string | number }, type: string | number } | [coachOnboardingRequest: string | number | { id: string | number }, type: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::store
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:481
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}'
 */
store.url = (args: { coachOnboardingRequest: string | number | { id: string | number }, type: string | number } | [coachOnboardingRequest: string | number | { id: string | number }, type: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    coachOnboardingRequest: args[0],
                    type: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        coachOnboardingRequest: typeof args.coachOnboardingRequest === 'object'
                ? args.coachOnboardingRequest.id
                : args.coachOnboardingRequest,
                                type: args.type,
                }

    return store.definition.url
            .replace('{coachOnboardingRequest}', parsedArgs.coachOnboardingRequest.toString())
            .replace('{type}', parsedArgs.type.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::store
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:481
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}'
 */
store.post = (args: { coachOnboardingRequest: string | number | { id: string | number }, type: string | number } | [coachOnboardingRequest: string | number | { id: string | number }, type: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})
const onboardingDocuments = {
    show: Object.assign(show, show),
store: Object.assign(store, store),
}

export default onboardingDocuments