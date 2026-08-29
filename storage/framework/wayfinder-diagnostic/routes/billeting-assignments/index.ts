import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\BilletingAssignmentController::store
 * @see app/Http/Controllers/BilletingAssignmentController.php:30
 * @route '/billeting-assignments'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/billeting-assignments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BilletingAssignmentController::store
 * @see app/Http/Controllers/BilletingAssignmentController.php:30
 * @route '/billeting-assignments'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BilletingAssignmentController::store
 * @see app/Http/Controllers/BilletingAssignmentController.php:30
 * @route '/billeting-assignments'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BilletingAssignmentController::status
 * @see app/Http/Controllers/BilletingAssignmentController.php:79
 * @route '/billeting-assignments/{billetingAssignment}/status'
 */
export const status = (args: { billetingAssignment: number | { id: number } } | [billetingAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/billeting-assignments/{billetingAssignment}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\BilletingAssignmentController::status
 * @see app/Http/Controllers/BilletingAssignmentController.php:79
 * @route '/billeting-assignments/{billetingAssignment}/status'
 */
status.url = (args: { billetingAssignment: number | { id: number } } | [billetingAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { billetingAssignment: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { billetingAssignment: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    billetingAssignment: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        billetingAssignment: typeof args.billetingAssignment === 'object'
                ? args.billetingAssignment.id
                : args.billetingAssignment,
                }

    return status.definition.url
            .replace('{billetingAssignment}', parsedArgs.billetingAssignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BilletingAssignmentController::status
 * @see app/Http/Controllers/BilletingAssignmentController.php:79
 * @route '/billeting-assignments/{billetingAssignment}/status'
 */
status.patch = (args: { billetingAssignment: number | { id: number } } | [billetingAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\BilletingAssignmentController::destroy
 * @see app/Http/Controllers/BilletingAssignmentController.php:99
 * @route '/billeting-assignments/{billetingAssignment}'
 */
export const destroy = (args: { billetingAssignment: number | { id: number } } | [billetingAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/billeting-assignments/{billetingAssignment}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\BilletingAssignmentController::destroy
 * @see app/Http/Controllers/BilletingAssignmentController.php:99
 * @route '/billeting-assignments/{billetingAssignment}'
 */
destroy.url = (args: { billetingAssignment: number | { id: number } } | [billetingAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { billetingAssignment: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { billetingAssignment: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    billetingAssignment: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        billetingAssignment: typeof args.billetingAssignment === 'object'
                ? args.billetingAssignment.id
                : args.billetingAssignment,
                }

    return destroy.definition.url
            .replace('{billetingAssignment}', parsedArgs.billetingAssignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BilletingAssignmentController::destroy
 * @see app/Http/Controllers/BilletingAssignmentController.php:99
 * @route '/billeting-assignments/{billetingAssignment}'
 */
destroy.delete = (args: { billetingAssignment: number | { id: number } } | [billetingAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const billetingAssignments = {
    store: Object.assign(store, store),
status: Object.assign(status, status),
destroy: Object.assign(destroy, destroy),
}

export default billetingAssignments