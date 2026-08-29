import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import assignmentRequests from './assignment-requests'
import onboardingRequests from './onboarding-requests'
import onboardingAssignments from './onboarding-assignments'
import onboardingDocuments from './onboarding-documents'
/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::onboardingAccredit
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:417
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/accredit'
 */
export const onboardingAccredit = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: onboardingAccredit.url(args, options),
    method: 'post',
})

onboardingAccredit.definition = {
    methods: ["post"],
    url: '/coach/onboarding-requests/{coachOnboardingRequest}/accredit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::onboardingAccredit
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:417
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/accredit'
 */
onboardingAccredit.url = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return onboardingAccredit.definition.url
            .replace('{coachOnboardingRequest}', parsedArgs.coachOnboardingRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CoachAssignmentRequestController::onboardingAccredit
 * @see app/Http/Controllers/CoachAssignmentRequestController.php:417
 * @route '/coach/onboarding-requests/{coachOnboardingRequest}/accredit'
 */
onboardingAccredit.post = (args: { coachOnboardingRequest: string | number | { id: string | number } } | [coachOnboardingRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: onboardingAccredit.url(args, options),
    method: 'post',
})
const coach = {
    assignmentRequests: Object.assign(assignmentRequests, assignmentRequests),
onboardingRequests: Object.assign(onboardingRequests, onboardingRequests),
onboardingAssignments: Object.assign(onboardingAssignments, onboardingAssignments),
onboardingAccredit: Object.assign(onboardingAccredit, onboardingAccredit),
onboardingDocuments: Object.assign(onboardingDocuments, onboardingDocuments),
}

export default coach