import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::edit
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:283
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/assignments'
 */
export const edit = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/coach/onboarding-requests/{coachOnboardingRequest}/assignments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::edit
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:283
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/assignments'
 */
edit.url = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return edit.definition.url
            .replace('{coachOnboardingRequest}', parsedArgs.coachOnboardingRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::edit
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:283
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/assignments'
 */
edit.get = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::edit
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:283
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/assignments'
 */
edit.head = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::update
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:345
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/assignments'
 */
export const update = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/coach/onboarding-requests/{coachOnboardingRequest}/assignments',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::update
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:345
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/assignments'
 */
update.url = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{coachOnboardingRequest}', parsedArgs.coachOnboardingRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::update
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:345
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/assignments'
 */
update.put = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})
const onboardingAssignments = {
    edit: Object.assign(edit, edit),
update: Object.assign(update, update),
}

export default onboardingAssignments