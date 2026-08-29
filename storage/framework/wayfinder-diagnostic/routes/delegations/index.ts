import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DelegationController::index
 * @see app/Http/Controllers/DelegationController.php:35
 * @route '/delegations'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/delegations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DelegationController::index
 * @see app/Http/Controllers/DelegationController.php:35
 * @route '/delegations'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DelegationController::index
 * @see app/Http/Controllers/DelegationController.php:35
 * @route '/delegations'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DelegationController::index
 * @see app/Http/Controllers/DelegationController.php:35
 * @route '/delegations'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DelegationController::update
 * @see app/Http/Controllers/DelegationController.php:130
 * @route '/delegations/{delegation}'
 */
export const update = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/delegations/{delegation}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DelegationController::update
 * @see app/Http/Controllers/DelegationController.php:130
 * @route '/delegations/{delegation}'
 */
update.url = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { delegation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { delegation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    delegation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        delegation: typeof args.delegation === 'object'
                ? args.delegation.id
                : args.delegation,
                }

    return update.definition.url
            .replace('{delegation}', parsedArgs.delegation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DelegationController::update
 * @see app/Http/Controllers/DelegationController.php:130
 * @route '/delegations/{delegation}'
 */
update.put = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DelegationController::submit
 * @see app/Http/Controllers/DelegationController.php:148
 * @route '/delegations/{delegation}/submit'
 */
export const submit = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: submit.url(args, options),
    method: 'patch',
})

submit.definition = {
    methods: ["patch"],
    url: '/delegations/{delegation}/submit',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DelegationController::submit
 * @see app/Http/Controllers/DelegationController.php:148
 * @route '/delegations/{delegation}/submit'
 */
submit.url = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { delegation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { delegation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    delegation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        delegation: typeof args.delegation === 'object'
                ? args.delegation.id
                : args.delegation,
                }

    return submit.definition.url
            .replace('{delegation}', parsedArgs.delegation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DelegationController::submit
 * @see app/Http/Controllers/DelegationController.php:148
 * @route '/delegations/{delegation}/submit'
 */
submit.patch = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: submit.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DelegationController::approve
 * @see app/Http/Controllers/DelegationController.php:175
 * @route '/delegations/{delegation}/approve'
 */
export const approve = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: approve.url(args, options),
    method: 'patch',
})

approve.definition = {
    methods: ["patch"],
    url: '/delegations/{delegation}/approve',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DelegationController::approve
 * @see app/Http/Controllers/DelegationController.php:175
 * @route '/delegations/{delegation}/approve'
 */
approve.url = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { delegation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { delegation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    delegation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        delegation: typeof args.delegation === 'object'
                ? args.delegation.id
                : args.delegation,
                }

    return approve.definition.url
            .replace('{delegation}', parsedArgs.delegation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DelegationController::approve
 * @see app/Http/Controllers/DelegationController.php:175
 * @route '/delegations/{delegation}/approve'
 */
approve.patch = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: approve.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DelegationController::returnMethod
 * @see app/Http/Controllers/DelegationController.php:202
 * @route '/delegations/{delegation}/return'
 */
export const returnMethod = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: returnMethod.url(args, options),
    method: 'patch',
})

returnMethod.definition = {
    methods: ["patch"],
    url: '/delegations/{delegation}/return',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DelegationController::returnMethod
 * @see app/Http/Controllers/DelegationController.php:202
 * @route '/delegations/{delegation}/return'
 */
returnMethod.url = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { delegation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { delegation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    delegation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        delegation: typeof args.delegation === 'object'
                ? args.delegation.id
                : args.delegation,
                }

    return returnMethod.definition.url
            .replace('{delegation}', parsedArgs.delegation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DelegationController::returnMethod
 * @see app/Http/Controllers/DelegationController.php:202
 * @route '/delegations/{delegation}/return'
 */
returnMethod.patch = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: returnMethod.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DelegationController::officers
 * @see app/Http/Controllers/DelegationController.php:229
 * @route '/delegations/{delegation}/officers'
 */
export const officers = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: officers.url(args, options),
    method: 'put',
})

officers.definition = {
    methods: ["put"],
    url: '/delegations/{delegation}/officers',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DelegationController::officers
 * @see app/Http/Controllers/DelegationController.php:229
 * @route '/delegations/{delegation}/officers'
 */
officers.url = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { delegation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { delegation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    delegation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        delegation: typeof args.delegation === 'object'
                ? args.delegation.id
                : args.delegation,
                }

    return officers.definition.url
            .replace('{delegation}', parsedArgs.delegation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DelegationController::officers
 * @see app/Http/Controllers/DelegationController.php:229
 * @route '/delegations/{delegation}/officers'
 */
officers.put = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: officers.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DelegationController::store
 * @see app/Http/Controllers/DelegationController.php:102
 * @route '/delegations'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/delegations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DelegationController::store
 * @see app/Http/Controllers/DelegationController.php:102
 * @route '/delegations'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DelegationController::store
 * @see app/Http/Controllers/DelegationController.php:102
 * @route '/delegations'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DelegationController::destroy
 * @see app/Http/Controllers/DelegationController.php:259
 * @route '/delegations/{delegation}'
 */
export const destroy = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/delegations/{delegation}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DelegationController::destroy
 * @see app/Http/Controllers/DelegationController.php:259
 * @route '/delegations/{delegation}'
 */
destroy.url = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { delegation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { delegation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    delegation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        delegation: typeof args.delegation === 'object'
                ? args.delegation.id
                : args.delegation,
                }

    return destroy.definition.url
            .replace('{delegation}', parsedArgs.delegation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DelegationController::destroy
 * @see app/Http/Controllers/DelegationController.php:259
 * @route '/delegations/{delegation}'
 */
destroy.delete = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const delegations = {
    index: Object.assign(index, index),
update: Object.assign(update, update),
submit: Object.assign(submit, submit),
approve: Object.assign(approve, approve),
return: Object.assign(returnMethod, returnMethod),
officers: Object.assign(officers, officers),
store: Object.assign(store, store),
destroy: Object.assign(destroy, destroy),
}

export default delegations