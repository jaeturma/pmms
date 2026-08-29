import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\FaqController::index
 * @see app/Http/Controllers/FaqController.php:18
 * @route '/content/faq'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/content/faq',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\FaqController::index
 * @see app/Http/Controllers/FaqController.php:18
 * @route '/content/faq'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\FaqController::index
 * @see app/Http/Controllers/FaqController.php:18
 * @route '/content/faq'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\FaqController::index
 * @see app/Http/Controllers/FaqController.php:18
 * @route '/content/faq'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\FaqController::store
 * @see app/Http/Controllers/FaqController.php:25
 * @route '/content/faq'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/content/faq',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\FaqController::store
 * @see app/Http/Controllers/FaqController.php:25
 * @route '/content/faq'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\FaqController::store
 * @see app/Http/Controllers/FaqController.php:25
 * @route '/content/faq'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\FaqController::update
 * @see app/Http/Controllers/FaqController.php:35
 * @route '/content/faq/{faqItem}'
 */
export const update = (args: { faqItem: string | number | { id: string | number } } | [faqItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/content/faq/{faqItem}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\FaqController::update
 * @see app/Http/Controllers/FaqController.php:35
 * @route '/content/faq/{faqItem}'
 */
update.url = (args: { faqItem: string | number | { id: string | number } } | [faqItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { faqItem: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { faqItem: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    faqItem: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        faqItem: typeof args.faqItem === 'object'
                ? args.faqItem.id
                : args.faqItem,
                }

    return update.definition.url
            .replace('{faqItem}', parsedArgs.faqItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\FaqController::update
 * @see app/Http/Controllers/FaqController.php:35
 * @route '/content/faq/{faqItem}'
 */
update.put = (args: { faqItem: string | number | { id: string | number } } | [faqItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\FaqController::status
 * @see app/Http/Controllers/FaqController.php:44
 * @route '/content/faq/{faqItem}/status'
 */
export const status = (args: { faqItem: string | number | { id: string | number } } | [faqItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/content/faq/{faqItem}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\FaqController::status
 * @see app/Http/Controllers/FaqController.php:44
 * @route '/content/faq/{faqItem}/status'
 */
status.url = (args: { faqItem: string | number | { id: string | number } } | [faqItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { faqItem: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { faqItem: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    faqItem: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        faqItem: typeof args.faqItem === 'object'
                ? args.faqItem.id
                : args.faqItem,
                }

    return status.definition.url
            .replace('{faqItem}', parsedArgs.faqItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\FaqController::status
 * @see app/Http/Controllers/FaqController.php:44
 * @route '/content/faq/{faqItem}/status'
 */
status.patch = (args: { faqItem: string | number | { id: string | number } } | [faqItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\FaqController::destroy
 * @see app/Http/Controllers/FaqController.php:54
 * @route '/content/faq/{faqItem}'
 */
export const destroy = (args: { faqItem: string | number | { id: string | number } } | [faqItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/content/faq/{faqItem}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\FaqController::destroy
 * @see app/Http/Controllers/FaqController.php:54
 * @route '/content/faq/{faqItem}'
 */
destroy.url = (args: { faqItem: string | number | { id: string | number } } | [faqItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { faqItem: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { faqItem: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    faqItem: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        faqItem: typeof args.faqItem === 'object'
                ? args.faqItem.id
                : args.faqItem,
                }

    return destroy.definition.url
            .replace('{faqItem}', parsedArgs.faqItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\FaqController::destroy
 * @see app/Http/Controllers/FaqController.php:54
 * @route '/content/faq/{faqItem}'
 */
destroy.delete = (args: { faqItem: string | number | { id: string | number } } | [faqItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const faq = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
status: Object.assign(status, status),
destroy: Object.assign(destroy, destroy),
}

export default faq