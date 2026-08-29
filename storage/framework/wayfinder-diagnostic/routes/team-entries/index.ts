import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TeamEntryController::store
 * @see app/Http/Controllers/TeamEntryController.php:29
 * @route '/team-entries'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/team-entries',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamEntryController::store
 * @see app/Http/Controllers/TeamEntryController.php:29
 * @route '/team-entries'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamEntryController::store
 * @see app/Http/Controllers/TeamEntryController.php:29
 * @route '/team-entries'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamEntryController::confirm
 * @see app/Http/Controllers/TeamEntryController.php:70
 * @route '/team-entries/{teamEntry}/confirm'
 */
export const confirm = (args: { teamEntry: string | number | { id: string | number } } | [teamEntry: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: confirm.url(args, options),
    method: 'patch',
})

confirm.definition = {
    methods: ["patch"],
    url: '/team-entries/{teamEntry}/confirm',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TeamEntryController::confirm
 * @see app/Http/Controllers/TeamEntryController.php:70
 * @route '/team-entries/{teamEntry}/confirm'
 */
confirm.url = (args: { teamEntry: string | number | { id: string | number } } | [teamEntry: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { teamEntry: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { teamEntry: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    teamEntry: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        teamEntry: typeof args.teamEntry === 'object'
                ? args.teamEntry.id
                : args.teamEntry,
                }

    return confirm.definition.url
            .replace('{teamEntry}', parsedArgs.teamEntry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamEntryController::confirm
 * @see app/Http/Controllers/TeamEntryController.php:70
 * @route '/team-entries/{teamEntry}/confirm'
 */
confirm.patch = (args: { teamEntry: string | number | { id: string | number } } | [teamEntry: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: confirm.url(args, options),
    method: 'patch',
})
const teamEntries = {
    store: Object.assign(store, store),
confirm: Object.assign(confirm, confirm),
}

export default teamEntries