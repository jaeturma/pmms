import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EntryController::index
 * @see app/Http/Controllers/EntryController.php:39
 * @route '/entries'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/entries',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EntryController::index
 * @see app/Http/Controllers/EntryController.php:39
 * @route '/entries'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::index
 * @see app/Http/Controllers/EntryController.php:39
 * @route '/entries'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EntryController::index
 * @see app/Http/Controllers/EntryController.php:39
 * @route '/entries'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EntryController::store
 * @see app/Http/Controllers/EntryController.php:238
 * @route '/entries'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/entries',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EntryController::store
 * @see app/Http/Controllers/EntryController.php:238
 * @route '/entries'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::store
 * @see app/Http/Controllers/EntryController.php:238
 * @route '/entries'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EntryController::confirm
 * @see app/Http/Controllers/EntryController.php:345
 * @route '/entries/{entry}/confirm'
 */
export const confirm = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: confirm.url(args, options),
    method: 'patch',
})

confirm.definition = {
    methods: ["patch"],
    url: '/entries/{entry}/confirm',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\EntryController::confirm
 * @see app/Http/Controllers/EntryController.php:345
 * @route '/entries/{entry}/confirm'
 */
confirm.url = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entry: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { entry: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    entry: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        entry: typeof args.entry === 'object'
                ? args.entry.id
                : args.entry,
                }

    return confirm.definition.url
            .replace('{entry}', parsedArgs.entry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::confirm
 * @see app/Http/Controllers/EntryController.php:345
 * @route '/entries/{entry}/confirm'
 */
confirm.patch = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: confirm.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\EntryController::withdraw
 * @see app/Http/Controllers/EntryController.php:388
 * @route '/entries/{entry}/withdraw'
 */
export const withdraw = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: withdraw.url(args, options),
    method: 'patch',
})

withdraw.definition = {
    methods: ["patch"],
    url: '/entries/{entry}/withdraw',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\EntryController::withdraw
 * @see app/Http/Controllers/EntryController.php:388
 * @route '/entries/{entry}/withdraw'
 */
withdraw.url = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entry: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { entry: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    entry: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        entry: typeof args.entry === 'object'
                ? args.entry.id
                : args.entry,
                }

    return withdraw.definition.url
            .replace('{entry}', parsedArgs.entry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::withdraw
 * @see app/Http/Controllers/EntryController.php:388
 * @route '/entries/{entry}/withdraw'
 */
withdraw.patch = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: withdraw.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\EntryController::destroy
 * @see app/Http/Controllers/EntryController.php:416
 * @route '/entries/{entry}'
 */
export const destroy = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/entries/{entry}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\EntryController::destroy
 * @see app/Http/Controllers/EntryController.php:416
 * @route '/entries/{entry}'
 */
destroy.url = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entry: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { entry: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    entry: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        entry: typeof args.entry === 'object'
                ? args.entry.id
                : args.entry,
                }

    return destroy.definition.url
            .replace('{entry}', parsedArgs.entry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::destroy
 * @see app/Http/Controllers/EntryController.php:416
 * @route '/entries/{entry}'
 */
destroy.delete = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const entries = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
confirm: Object.assign(confirm, confirm),
withdraw: Object.assign(withdraw, withdraw),
destroy: Object.assign(destroy, destroy),
}

export default entries