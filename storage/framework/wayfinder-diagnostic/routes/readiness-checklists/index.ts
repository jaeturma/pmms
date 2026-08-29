import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ReadinessChecklistController::store
 * @see app/Http/Controllers/ReadinessChecklistController.php:25
 * @route '/readiness-checklists'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/readiness-checklists',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ReadinessChecklistController::store
 * @see app/Http/Controllers/ReadinessChecklistController.php:25
 * @route '/readiness-checklists'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReadinessChecklistController::store
 * @see app/Http/Controllers/ReadinessChecklistController.php:25
 * @route '/readiness-checklists'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReadinessChecklistController::status
 * @see app/Http/Controllers/ReadinessChecklistController.php:47
 * @route '/readiness-checklists/{readinessChecklist}/status'
 */
export const status = (args: { readinessChecklist: number | { id: number } } | [readinessChecklist: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/readiness-checklists/{readinessChecklist}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ReadinessChecklistController::status
 * @see app/Http/Controllers/ReadinessChecklistController.php:47
 * @route '/readiness-checklists/{readinessChecklist}/status'
 */
status.url = (args: { readinessChecklist: number | { id: number } } | [readinessChecklist: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { readinessChecklist: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { readinessChecklist: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    readinessChecklist: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        readinessChecklist: typeof args.readinessChecklist === 'object'
                ? args.readinessChecklist.id
                : args.readinessChecklist,
                }

    return status.definition.url
            .replace('{readinessChecklist}', parsedArgs.readinessChecklist.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReadinessChecklistController::status
 * @see app/Http/Controllers/ReadinessChecklistController.php:47
 * @route '/readiness-checklists/{readinessChecklist}/status'
 */
status.patch = (args: { readinessChecklist: number | { id: number } } | [readinessChecklist: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ReadinessChecklistController::destroy
 * @see app/Http/Controllers/ReadinessChecklistController.php:72
 * @route '/readiness-checklists/{readinessChecklist}'
 */
export const destroy = (args: { readinessChecklist: number | { id: number } } | [readinessChecklist: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/readiness-checklists/{readinessChecklist}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ReadinessChecklistController::destroy
 * @see app/Http/Controllers/ReadinessChecklistController.php:72
 * @route '/readiness-checklists/{readinessChecklist}'
 */
destroy.url = (args: { readinessChecklist: number | { id: number } } | [readinessChecklist: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { readinessChecklist: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { readinessChecklist: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    readinessChecklist: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        readinessChecklist: typeof args.readinessChecklist === 'object'
                ? args.readinessChecklist.id
                : args.readinessChecklist,
                }

    return destroy.definition.url
            .replace('{readinessChecklist}', parsedArgs.readinessChecklist.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReadinessChecklistController::destroy
 * @see app/Http/Controllers/ReadinessChecklistController.php:72
 * @route '/readiness-checklists/{readinessChecklist}'
 */
destroy.delete = (args: { readinessChecklist: number | { id: number } } | [readinessChecklist: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const readinessChecklists = {
    store: Object.assign(store, store),
status: Object.assign(status, status),
destroy: Object.assign(destroy, destroy),
}

export default readinessChecklists