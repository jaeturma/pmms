import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EmergencyContactController::store
 * @see app/Http/Controllers/EmergencyContactController.php:25
 * @route '/emergency-contacts'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/emergency-contacts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmergencyContactController::store
 * @see app/Http/Controllers/EmergencyContactController.php:25
 * @route '/emergency-contacts'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmergencyContactController::store
 * @see app/Http/Controllers/EmergencyContactController.php:25
 * @route '/emergency-contacts'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmergencyContactController::update
 * @see app/Http/Controllers/EmergencyContactController.php:46
 * @route '/emergency-contacts/{emergencyContact}'
 */
export const update = (args: { emergencyContact: number | { id: number } } | [emergencyContact: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/emergency-contacts/{emergencyContact}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\EmergencyContactController::update
 * @see app/Http/Controllers/EmergencyContactController.php:46
 * @route '/emergency-contacts/{emergencyContact}'
 */
update.url = (args: { emergencyContact: number | { id: number } } | [emergencyContact: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { emergencyContact: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { emergencyContact: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    emergencyContact: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        emergencyContact: typeof args.emergencyContact === 'object'
                ? args.emergencyContact.id
                : args.emergencyContact,
                }

    return update.definition.url
            .replace('{emergencyContact}', parsedArgs.emergencyContact.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmergencyContactController::update
 * @see app/Http/Controllers/EmergencyContactController.php:46
 * @route '/emergency-contacts/{emergencyContact}'
 */
update.put = (args: { emergencyContact: number | { id: number } } | [emergencyContact: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\EmergencyContactController::destroy
 * @see app/Http/Controllers/EmergencyContactController.php:66
 * @route '/emergency-contacts/{emergencyContact}'
 */
export const destroy = (args: { emergencyContact: number | { id: number } } | [emergencyContact: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/emergency-contacts/{emergencyContact}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\EmergencyContactController::destroy
 * @see app/Http/Controllers/EmergencyContactController.php:66
 * @route '/emergency-contacts/{emergencyContact}'
 */
destroy.url = (args: { emergencyContact: number | { id: number } } | [emergencyContact: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { emergencyContact: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { emergencyContact: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    emergencyContact: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        emergencyContact: typeof args.emergencyContact === 'object'
                ? args.emergencyContact.id
                : args.emergencyContact,
                }

    return destroy.definition.url
            .replace('{emergencyContact}', parsedArgs.emergencyContact.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmergencyContactController::destroy
 * @see app/Http/Controllers/EmergencyContactController.php:66
 * @route '/emergency-contacts/{emergencyContact}'
 */
destroy.delete = (args: { emergencyContact: number | { id: number } } | [emergencyContact: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const emergencyContacts = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default emergencyContacts