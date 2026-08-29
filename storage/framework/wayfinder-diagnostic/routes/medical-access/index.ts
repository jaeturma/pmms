import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MedicalAccessController::store
 * @see app/Http/Controllers/MedicalAccessController.php:31
 * @route '/medical-access'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/medical-access',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MedicalAccessController::store
 * @see app/Http/Controllers/MedicalAccessController.php:31
 * @route '/medical-access'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalAccessController::store
 * @see app/Http/Controllers/MedicalAccessController.php:31
 * @route '/medical-access'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MedicalAccessController::review
 * @see app/Http/Controllers/MedicalAccessController.php:68
 * @route '/medical-access/{medicalAccessLog}/review'
 */
export const review = (args: { medicalAccessLog: number | { id: number } } | [medicalAccessLog: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})

review.definition = {
    methods: ["patch"],
    url: '/medical-access/{medicalAccessLog}/review',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\MedicalAccessController::review
 * @see app/Http/Controllers/MedicalAccessController.php:68
 * @route '/medical-access/{medicalAccessLog}/review'
 */
review.url = (args: { medicalAccessLog: number | { id: number } } | [medicalAccessLog: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { medicalAccessLog: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { medicalAccessLog: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    medicalAccessLog: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        medicalAccessLog: typeof args.medicalAccessLog === 'object'
                ? args.medicalAccessLog.id
                : args.medicalAccessLog,
                }

    return review.definition.url
            .replace('{medicalAccessLog}', parsedArgs.medicalAccessLog.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalAccessController::review
 * @see app/Http/Controllers/MedicalAccessController.php:68
 * @route '/medical-access/{medicalAccessLog}/review'
 */
review.patch = (args: { medicalAccessLog: number | { id: number } } | [medicalAccessLog: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})
const medicalAccess = {
    store: Object.assign(store, store),
review: Object.assign(review, review),
}

export default medicalAccess