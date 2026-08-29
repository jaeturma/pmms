import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EmergencyIncidentController::index
 * @see app/Http/Controllers/EmergencyIncidentController.php:36
 * @route '/drrm/incidents'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/drrm/incidents',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EmergencyIncidentController::index
 * @see app/Http/Controllers/EmergencyIncidentController.php:36
 * @route '/drrm/incidents'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmergencyIncidentController::index
 * @see app/Http/Controllers/EmergencyIncidentController.php:36
 * @route '/drrm/incidents'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EmergencyIncidentController::index
 * @see app/Http/Controllers/EmergencyIncidentController.php:36
 * @route '/drrm/incidents'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EmergencyIncidentController::store
 * @see app/Http/Controllers/EmergencyIncidentController.php:90
 * @route '/emergency-incidents'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/emergency-incidents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmergencyIncidentController::store
 * @see app/Http/Controllers/EmergencyIncidentController.php:90
 * @route '/emergency-incidents'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmergencyIncidentController::store
 * @see app/Http/Controllers/EmergencyIncidentController.php:90
 * @route '/emergency-incidents'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmergencyIncidentController::status
 * @see app/Http/Controllers/EmergencyIncidentController.php:119
 * @route '/emergency-incidents/{emergencyIncident}/status'
 */
export const status = (args: { emergencyIncident: number | { id: number } } | [emergencyIncident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/emergency-incidents/{emergencyIncident}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\EmergencyIncidentController::status
 * @see app/Http/Controllers/EmergencyIncidentController.php:119
 * @route '/emergency-incidents/{emergencyIncident}/status'
 */
status.url = (args: { emergencyIncident: number | { id: number } } | [emergencyIncident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { emergencyIncident: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { emergencyIncident: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    emergencyIncident: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        emergencyIncident: typeof args.emergencyIncident === 'object'
                ? args.emergencyIncident.id
                : args.emergencyIncident,
                }

    return status.definition.url
            .replace('{emergencyIncident}', parsedArgs.emergencyIncident.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmergencyIncidentController::status
 * @see app/Http/Controllers/EmergencyIncidentController.php:119
 * @route '/emergency-incidents/{emergencyIncident}/status'
 */
status.patch = (args: { emergencyIncident: number | { id: number } } | [emergencyIncident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})
const emergencyIncidents = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
status: Object.assign(status, status),
}

export default emergencyIncidents