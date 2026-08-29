import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\NewsController::index
 * @see app/Http/Controllers/NewsController.php:21
 * @route '/content/news'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/content/news',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NewsController::index
 * @see app/Http/Controllers/NewsController.php:21
 * @route '/content/news'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NewsController::index
 * @see app/Http/Controllers/NewsController.php:21
 * @route '/content/news'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\NewsController::index
 * @see app/Http/Controllers/NewsController.php:21
 * @route '/content/news'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NewsController::store
 * @see app/Http/Controllers/NewsController.php:30
 * @route '/content/news'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/content/news',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\NewsController::store
 * @see app/Http/Controllers/NewsController.php:30
 * @route '/content/news'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\NewsController::store
 * @see app/Http/Controllers/NewsController.php:30
 * @route '/content/news'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\NewsController::update
 * @see app/Http/Controllers/NewsController.php:42
 * @route '/content/news/{newsItem}'
 */
export const update = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/content/news/{newsItem}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\NewsController::update
 * @see app/Http/Controllers/NewsController.php:42
 * @route '/content/news/{newsItem}'
 */
update.url = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { newsItem: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { newsItem: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    newsItem: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        newsItem: typeof args.newsItem === 'object'
                ? args.newsItem.id
                : args.newsItem,
                }

    return update.definition.url
            .replace('{newsItem}', parsedArgs.newsItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NewsController::update
 * @see app/Http/Controllers/NewsController.php:42
 * @route '/content/news/{newsItem}'
 */
update.put = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\NewsController::status
 * @see app/Http/Controllers/NewsController.php:52
 * @route '/content/news/{newsItem}/status'
 */
export const status = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/content/news/{newsItem}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\NewsController::status
 * @see app/Http/Controllers/NewsController.php:52
 * @route '/content/news/{newsItem}/status'
 */
status.url = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { newsItem: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { newsItem: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    newsItem: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        newsItem: typeof args.newsItem === 'object'
                ? args.newsItem.id
                : args.newsItem,
                }

    return status.definition.url
            .replace('{newsItem}', parsedArgs.newsItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NewsController::status
 * @see app/Http/Controllers/NewsController.php:52
 * @route '/content/news/{newsItem}/status'
 */
status.patch = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\NewsController::destroy
 * @see app/Http/Controllers/NewsController.php:70
 * @route '/content/news/{newsItem}'
 */
export const destroy = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/content/news/{newsItem}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\NewsController::destroy
 * @see app/Http/Controllers/NewsController.php:70
 * @route '/content/news/{newsItem}'
 */
destroy.url = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { newsItem: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { newsItem: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    newsItem: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        newsItem: typeof args.newsItem === 'object'
                ? args.newsItem.id
                : args.newsItem,
                }

    return destroy.definition.url
            .replace('{newsItem}', parsedArgs.newsItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NewsController::destroy
 * @see app/Http/Controllers/NewsController.php:70
 * @route '/content/news/{newsItem}'
 */
destroy.delete = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const news = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
status: Object.assign(status, status),
destroy: Object.assign(destroy, destroy),
}

export default news