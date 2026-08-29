import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\IncidentController::index
 * @see app/Http/Controllers/IncidentController.php:28
 * @route '/incidents'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/incidents',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\IncidentController::index
 * @see app/Http/Controllers/IncidentController.php:28
 * @route '/incidents'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\IncidentController::index
 * @see app/Http/Controllers/IncidentController.php:28
 * @route '/incidents'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\IncidentController::index
 * @see app/Http/Controllers/IncidentController.php:28
 * @route '/incidents'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\IncidentController::store
 * @see app/Http/Controllers/IncidentController.php:75
 * @route '/incidents'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/incidents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\IncidentController::store
 * @see app/Http/Controllers/IncidentController.php:75
 * @route '/incidents'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\IncidentController::store
 * @see app/Http/Controllers/IncidentController.php:75
 * @route '/incidents'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IncidentController::update
 * @see app/Http/Controllers/IncidentController.php:94
 * @route '/incidents/{incident}'
 */
export const update = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/incidents/{incident}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\IncidentController::update
 * @see app/Http/Controllers/IncidentController.php:94
 * @route '/incidents/{incident}'
 */
update.url = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { incident: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { incident: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    incident: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        incident: typeof args.incident === 'object'
                ? args.incident.id
                : args.incident,
                }

    return update.definition.url
            .replace('{incident}', parsedArgs.incident.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\IncidentController::update
 * @see app/Http/Controllers/IncidentController.php:94
 * @route '/incidents/{incident}'
 */
update.put = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\IncidentController::resolve
 * @see app/Http/Controllers/IncidentController.php:108
 * @route '/incidents/{incident}/resolve'
 */
export const resolve = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: resolve.url(args, options),
    method: 'patch',
})

resolve.definition = {
    methods: ["patch"],
    url: '/incidents/{incident}/resolve',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\IncidentController::resolve
 * @see app/Http/Controllers/IncidentController.php:108
 * @route '/incidents/{incident}/resolve'
 */
resolve.url = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { incident: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { incident: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    incident: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        incident: typeof args.incident === 'object'
                ? args.incident.id
                : args.incident,
                }

    return resolve.definition.url
            .replace('{incident}', parsedArgs.incident.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\IncidentController::resolve
 * @see app/Http/Controllers/IncidentController.php:108
 * @route '/incidents/{incident}/resolve'
 */
resolve.patch = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: resolve.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\IncidentController::reopen
 * @see app/Http/Controllers/IncidentController.php:134
 * @route '/incidents/{incident}/reopen'
 */
export const reopen = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: reopen.url(args, options),
    method: 'patch',
})

reopen.definition = {
    methods: ["patch"],
    url: '/incidents/{incident}/reopen',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\IncidentController::reopen
 * @see app/Http/Controllers/IncidentController.php:134
 * @route '/incidents/{incident}/reopen'
 */
reopen.url = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { incident: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { incident: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    incident: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        incident: typeof args.incident === 'object'
                ? args.incident.id
                : args.incident,
                }

    return reopen.definition.url
            .replace('{incident}', parsedArgs.incident.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\IncidentController::reopen
 * @see app/Http/Controllers/IncidentController.php:134
 * @route '/incidents/{incident}/reopen'
 */
reopen.patch = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: reopen.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\IncidentController::destroy
 * @see app/Http/Controllers/IncidentController.php:160
 * @route '/incidents/{incident}'
 */
export const destroy = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/incidents/{incident}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\IncidentController::destroy
 * @see app/Http/Controllers/IncidentController.php:160
 * @route '/incidents/{incident}'
 */
destroy.url = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { incident: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { incident: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    incident: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        incident: typeof args.incident === 'object'
                ? args.incident.id
                : args.incident,
                }

    return destroy.definition.url
            .replace('{incident}', parsedArgs.incident.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\IncidentController::destroy
 * @see app/Http/Controllers/IncidentController.php:160
 * @route '/incidents/{incident}'
 */
destroy.delete = (args: { incident: number | { id: number } } | [incident: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const incidents = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
resolve: Object.assign(resolve, resolve),
reopen: Object.assign(reopen, reopen),
destroy: Object.assign(destroy, destroy),
}

export default incidents