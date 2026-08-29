import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DistrictController::logo
 * @see app/Http/Controllers/DistrictController.php:61
 * @route '/districts/{district}/logo'
 */
export const logo = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logo.url(args, options),
    method: 'get',
})

logo.definition = {
    methods: ["get","head"],
    url: '/districts/{district}/logo',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DistrictController::logo
 * @see app/Http/Controllers/DistrictController.php:61
 * @route '/districts/{district}/logo'
 */
logo.url = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { district: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { district: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    district: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        district: typeof args.district === 'object'
                ? args.district.id
                : args.district,
                }

    return logo.definition.url
            .replace('{district}', parsedArgs.district.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DistrictController::logo
 * @see app/Http/Controllers/DistrictController.php:61
 * @route '/districts/{district}/logo'
 */
logo.get = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logo.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DistrictController::logo
 * @see app/Http/Controllers/DistrictController.php:61
 * @route '/districts/{district}/logo'
 */
logo.head = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: logo.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DistrictController::teamLogo
 * @see app/Http/Controllers/DistrictController.php:74
 * @route '/districts/{district}/team-logo'
 */
export const teamLogo = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: teamLogo.url(args, options),
    method: 'get',
})

teamLogo.definition = {
    methods: ["get","head"],
    url: '/districts/{district}/team-logo',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DistrictController::teamLogo
 * @see app/Http/Controllers/DistrictController.php:74
 * @route '/districts/{district}/team-logo'
 */
teamLogo.url = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { district: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { district: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    district: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        district: typeof args.district === 'object'
                ? args.district.id
                : args.district,
                }

    return teamLogo.definition.url
            .replace('{district}', parsedArgs.district.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DistrictController::teamLogo
 * @see app/Http/Controllers/DistrictController.php:74
 * @route '/districts/{district}/team-logo'
 */
teamLogo.get = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: teamLogo.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DistrictController::teamLogo
 * @see app/Http/Controllers/DistrictController.php:74
 * @route '/districts/{district}/team-logo'
 */
teamLogo.head = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: teamLogo.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DistrictController::index
 * @see app/Http/Controllers/DistrictController.php:31
 * @route '/districts'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/districts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DistrictController::index
 * @see app/Http/Controllers/DistrictController.php:31
 * @route '/districts'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DistrictController::index
 * @see app/Http/Controllers/DistrictController.php:31
 * @route '/districts'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DistrictController::index
 * @see app/Http/Controllers/DistrictController.php:31
 * @route '/districts'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DistrictController::store
 * @see app/Http/Controllers/DistrictController.php:86
 * @route '/districts'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/districts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DistrictController::store
 * @see app/Http/Controllers/DistrictController.php:86
 * @route '/districts'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DistrictController::store
 * @see app/Http/Controllers/DistrictController.php:86
 * @route '/districts'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DistrictController::update
 * @see app/Http/Controllers/DistrictController.php:114
 * @route '/districts/{district}'
 */
export const update = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/districts/{district}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DistrictController::update
 * @see app/Http/Controllers/DistrictController.php:114
 * @route '/districts/{district}'
 */
update.url = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { district: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { district: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    district: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        district: typeof args.district === 'object'
                ? args.district.id
                : args.district,
                }

    return update.definition.url
            .replace('{district}', parsedArgs.district.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DistrictController::update
 * @see app/Http/Controllers/DistrictController.php:114
 * @route '/districts/{district}'
 */
update.put = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DistrictController::archive
 * @see app/Http/Controllers/DistrictController.php:160
 * @route '/districts/{district}/archive'
 */
export const archive = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

archive.definition = {
    methods: ["patch"],
    url: '/districts/{district}/archive',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DistrictController::archive
 * @see app/Http/Controllers/DistrictController.php:160
 * @route '/districts/{district}/archive'
 */
archive.url = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { district: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { district: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    district: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        district: typeof args.district === 'object'
                ? args.district.id
                : args.district,
                }

    return archive.definition.url
            .replace('{district}', parsedArgs.district.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DistrictController::archive
 * @see app/Http/Controllers/DistrictController.php:160
 * @route '/districts/{district}/archive'
 */
archive.patch = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DistrictController::restore
 * @see app/Http/Controllers/DistrictController.php:174
 * @route '/districts/{district}/restore'
 */
export const restore = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

restore.definition = {
    methods: ["patch"],
    url: '/districts/{district}/restore',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DistrictController::restore
 * @see app/Http/Controllers/DistrictController.php:174
 * @route '/districts/{district}/restore'
 */
restore.url = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { district: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { district: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    district: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        district: typeof args.district === 'object'
                ? args.district.id
                : args.district,
                }

    return restore.definition.url
            .replace('{district}', parsedArgs.district.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DistrictController::restore
 * @see app/Http/Controllers/DistrictController.php:174
 * @route '/districts/{district}/restore'
 */
restore.patch = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DistrictController::destroy
 * @see app/Http/Controllers/DistrictController.php:188
 * @route '/districts/{district}'
 */
export const destroy = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/districts/{district}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DistrictController::destroy
 * @see app/Http/Controllers/DistrictController.php:188
 * @route '/districts/{district}'
 */
destroy.url = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { district: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { district: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    district: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        district: typeof args.district === 'object'
                ? args.district.id
                : args.district,
                }

    return destroy.definition.url
            .replace('{district}', parsedArgs.district.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DistrictController::destroy
 * @see app/Http/Controllers/DistrictController.php:188
 * @route '/districts/{district}'
 */
destroy.delete = (args: { district: number | { id: number } } | [district: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const districts = {
    logo: Object.assign(logo, logo),
teamLogo: Object.assign(teamLogo, teamLogo),
index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
archive: Object.assign(archive, archive),
restore: Object.assign(restore, restore),
destroy: Object.assign(destroy, destroy),
}

export default districts