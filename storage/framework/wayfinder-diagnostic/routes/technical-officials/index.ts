import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EligibilityController::eligibility
 * @see app/Http/Controllers/EligibilityController.php:81
 * @route '/technical-officials/{official}/eligibility'
 */
export const eligibility = (args: { official: number | { id: number } } | [official: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eligibility.url(args, options),
    method: 'get',
})

eligibility.definition = {
    methods: ["get","head"],
    url: '/technical-officials/{official}/eligibility',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EligibilityController::eligibility
 * @see app/Http/Controllers/EligibilityController.php:81
 * @route '/technical-officials/{official}/eligibility'
 */
eligibility.url = (args: { official: number | { id: number } } | [official: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { official: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { official: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    official: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        official: typeof args.official === 'object'
                ? args.official.id
                : args.official,
                }

    return eligibility.definition.url
            .replace('{official}', parsedArgs.official.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EligibilityController::eligibility
 * @see app/Http/Controllers/EligibilityController.php:81
 * @route '/technical-officials/{official}/eligibility'
 */
eligibility.get = (args: { official: number | { id: number } } | [official: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eligibility.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EligibilityController::eligibility
 * @see app/Http/Controllers/EligibilityController.php:81
 * @route '/technical-officials/{official}/eligibility'
 */
eligibility.head = (args: { official: number | { id: number } } | [official: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: eligibility.url(args, options),
    method: 'head',
})
const technicalOfficials = {
    eligibility: Object.assign(eligibility, eligibility),
}

export default technicalOfficials