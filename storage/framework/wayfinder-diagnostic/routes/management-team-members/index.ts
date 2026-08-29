import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ManagementTeamMemberController::store
 * @see app/Http/Controllers/ManagementTeamMemberController.php:29
 * @route '/management-team-members'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/management-team-members',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ManagementTeamMemberController::store
 * @see app/Http/Controllers/ManagementTeamMemberController.php:29
 * @route '/management-team-members'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementTeamMemberController::store
 * @see app/Http/Controllers/ManagementTeamMemberController.php:29
 * @route '/management-team-members'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ManagementTeamMemberController::status
 * @see app/Http/Controllers/ManagementTeamMemberController.php:75
 * @route '/management-team-members/{managementTeamMember}/status'
 */
export const status = (args: { managementTeamMember: number | { id: number } } | [managementTeamMember: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/management-team-members/{managementTeamMember}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ManagementTeamMemberController::status
 * @see app/Http/Controllers/ManagementTeamMemberController.php:75
 * @route '/management-team-members/{managementTeamMember}/status'
 */
status.url = (args: { managementTeamMember: number | { id: number } } | [managementTeamMember: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { managementTeamMember: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { managementTeamMember: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    managementTeamMember: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        managementTeamMember: typeof args.managementTeamMember === 'object'
                ? args.managementTeamMember.id
                : args.managementTeamMember,
                }

    return status.definition.url
            .replace('{managementTeamMember}', parsedArgs.managementTeamMember.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementTeamMemberController::status
 * @see app/Http/Controllers/ManagementTeamMemberController.php:75
 * @route '/management-team-members/{managementTeamMember}/status'
 */
status.patch = (args: { managementTeamMember: number | { id: number } } | [managementTeamMember: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ManagementTeamMemberController::destroy
 * @see app/Http/Controllers/ManagementTeamMemberController.php:101
 * @route '/management-team-members/{managementTeamMember}'
 */
export const destroy = (args: { managementTeamMember: number | { id: number } } | [managementTeamMember: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/management-team-members/{managementTeamMember}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ManagementTeamMemberController::destroy
 * @see app/Http/Controllers/ManagementTeamMemberController.php:101
 * @route '/management-team-members/{managementTeamMember}'
 */
destroy.url = (args: { managementTeamMember: number | { id: number } } | [managementTeamMember: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { managementTeamMember: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { managementTeamMember: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    managementTeamMember: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        managementTeamMember: typeof args.managementTeamMember === 'object'
                ? args.managementTeamMember.id
                : args.managementTeamMember,
                }

    return destroy.definition.url
            .replace('{managementTeamMember}', parsedArgs.managementTeamMember.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementTeamMemberController::destroy
 * @see app/Http/Controllers/ManagementTeamMemberController.php:101
 * @route '/management-team-members/{managementTeamMember}'
 */
destroy.delete = (args: { managementTeamMember: number | { id: number } } | [managementTeamMember: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const managementTeamMembers = {
    store: Object.assign(store, store),
status: Object.assign(status, status),
destroy: Object.assign(destroy, destroy),
}

export default managementTeamMembers