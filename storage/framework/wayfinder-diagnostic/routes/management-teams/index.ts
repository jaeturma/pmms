import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ManagementTeamController::index
 * @see app/Http/Controllers/ManagementTeamController.php:39
 * @route '/management-teams'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/management-teams',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ManagementTeamController::index
 * @see app/Http/Controllers/ManagementTeamController.php:39
 * @route '/management-teams'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementTeamController::index
 * @see app/Http/Controllers/ManagementTeamController.php:39
 * @route '/management-teams'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ManagementTeamController::index
 * @see app/Http/Controllers/ManagementTeamController.php:39
 * @route '/management-teams'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ManagementTeamController::store
 * @see app/Http/Controllers/ManagementTeamController.php:108
 * @route '/management-teams'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/management-teams',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ManagementTeamController::store
 * @see app/Http/Controllers/ManagementTeamController.php:108
 * @route '/management-teams'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementTeamController::store
 * @see app/Http/Controllers/ManagementTeamController.php:108
 * @route '/management-teams'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ManagementTeamController::update
 * @see app/Http/Controllers/ManagementTeamController.php:149
 * @route '/management-teams/{managementTeam}'
 */
export const update = (args: { managementTeam: number | { id: number } } | [managementTeam: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/management-teams/{managementTeam}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ManagementTeamController::update
 * @see app/Http/Controllers/ManagementTeamController.php:149
 * @route '/management-teams/{managementTeam}'
 */
update.url = (args: { managementTeam: number | { id: number } } | [managementTeam: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { managementTeam: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { managementTeam: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    managementTeam: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        managementTeam: typeof args.managementTeam === 'object'
                ? args.managementTeam.id
                : args.managementTeam,
                }

    return update.definition.url
            .replace('{managementTeam}', parsedArgs.managementTeam.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementTeamController::update
 * @see app/Http/Controllers/ManagementTeamController.php:149
 * @route '/management-teams/{managementTeam}'
 */
update.put = (args: { managementTeam: number | { id: number } } | [managementTeam: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ManagementTeamController::destroy
 * @see app/Http/Controllers/ManagementTeamController.php:175
 * @route '/management-teams/{managementTeam}'
 */
export const destroy = (args: { managementTeam: number | { id: number } } | [managementTeam: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/management-teams/{managementTeam}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ManagementTeamController::destroy
 * @see app/Http/Controllers/ManagementTeamController.php:175
 * @route '/management-teams/{managementTeam}'
 */
destroy.url = (args: { managementTeam: number | { id: number } } | [managementTeam: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { managementTeam: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { managementTeam: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    managementTeam: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        managementTeam: typeof args.managementTeam === 'object'
                ? args.managementTeam.id
                : args.managementTeam,
                }

    return destroy.definition.url
            .replace('{managementTeam}', parsedArgs.managementTeam.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementTeamController::destroy
 * @see app/Http/Controllers/ManagementTeamController.php:175
 * @route '/management-teams/{managementTeam}'
 */
destroy.delete = (args: { managementTeam: number | { id: number } } | [managementTeam: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const managementTeams = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default managementTeams