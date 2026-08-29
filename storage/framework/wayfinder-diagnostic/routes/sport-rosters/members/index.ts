import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\SportRosterController::store
 * @see app/Http/Controllers/SportRosterController.php:21
 * @route '/sport-rosters/members'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/sport-rosters/members',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SportRosterController::store
 * @see app/Http/Controllers/SportRosterController.php:21
 * @route '/sport-rosters/members'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportRosterController::store
 * @see app/Http/Controllers/SportRosterController.php:21
 * @route '/sport-rosters/members'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SportRosterController::destroy
 * @see app/Http/Controllers/SportRosterController.php:41
 * @route '/sport-rosters/members/{sportRosterMember}'
 */
export const destroy = (args: { sportRosterMember: string | number | { id: string | number } } | [sportRosterMember: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/sport-rosters/members/{sportRosterMember}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SportRosterController::destroy
 * @see app/Http/Controllers/SportRosterController.php:41
 * @route '/sport-rosters/members/{sportRosterMember}'
 */
destroy.url = (args: { sportRosterMember: string | number | { id: string | number } } | [sportRosterMember: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sportRosterMember: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { sportRosterMember: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    sportRosterMember: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sportRosterMember: typeof args.sportRosterMember === 'object'
                ? args.sportRosterMember.id
                : args.sportRosterMember,
                }

    return destroy.definition.url
            .replace('{sportRosterMember}', parsedArgs.sportRosterMember.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportRosterController::destroy
 * @see app/Http/Controllers/SportRosterController.php:41
 * @route '/sport-rosters/members/{sportRosterMember}'
 */
destroy.delete = (args: { sportRosterMember: string | number | { id: string | number } } | [sportRosterMember: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const members = {
    store: Object.assign(store, store),
destroy: Object.assign(destroy, destroy),
}

export default members