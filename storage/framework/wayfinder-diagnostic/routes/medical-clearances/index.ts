import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MedicalClearanceController::store
 * @see app/Http/Controllers/MedicalClearanceController.php:155
 * @route '/medical-clearances'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/medical-clearances',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MedicalClearanceController::store
 * @see app/Http/Controllers/MedicalClearanceController.php:155
 * @route '/medical-clearances'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalClearanceController::store
 * @see app/Http/Controllers/MedicalClearanceController.php:155
 * @route '/medical-clearances'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MedicalClearanceController::update
 * @see app/Http/Controllers/MedicalClearanceController.php:204
 * @route '/medical-clearances/{medicalClearance}'
 */
export const update = (args: { medicalClearance: number | { id: number } } | [medicalClearance: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/medical-clearances/{medicalClearance}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MedicalClearanceController::update
 * @see app/Http/Controllers/MedicalClearanceController.php:204
 * @route '/medical-clearances/{medicalClearance}'
 */
update.url = (args: { medicalClearance: number | { id: number } } | [medicalClearance: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { medicalClearance: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { medicalClearance: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    medicalClearance: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        medicalClearance: typeof args.medicalClearance === 'object'
                ? args.medicalClearance.id
                : args.medicalClearance,
                }

    return update.definition.url
            .replace('{medicalClearance}', parsedArgs.medicalClearance.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalClearanceController::update
 * @see app/Http/Controllers/MedicalClearanceController.php:204
 * @route '/medical-clearances/{medicalClearance}'
 */
update.put = (args: { medicalClearance: number | { id: number } } | [medicalClearance: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})
const medicalClearances = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
}

export default medicalClearances