import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AccreditationController::store
 * @see app/Http/Controllers/AccreditationController.php:93
 * @route '/accreditations'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/accreditations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AccreditationController::store
 * @see app/Http/Controllers/AccreditationController.php:93
 * @route '/accreditations'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccreditationController::store
 * @see app/Http/Controllers/AccreditationController.php:93
 * @route '/accreditations'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AccreditationController::index
 * @see app/Http/Controllers/AccreditationController.php:32
 * @route '/delegations/{delegation}/accreditation'
 */
export const index = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/delegations/{delegation}/accreditation',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AccreditationController::index
 * @see app/Http/Controllers/AccreditationController.php:32
 * @route '/delegations/{delegation}/accreditation'
 */
index.url = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return index.definition.url
            .replace('{delegation}', parsedArgs.delegation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccreditationController::index
 * @see app/Http/Controllers/AccreditationController.php:32
 * @route '/delegations/{delegation}/accreditation'
 */
index.get = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AccreditationController::index
 * @see app/Http/Controllers/AccreditationController.php:32
 * @route '/delegations/{delegation}/accreditation'
 */
index.head = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AccreditationController::cards
 * @see app/Http/Controllers/AccreditationController.php:220
 * @route '/delegations/{delegation}/accreditation/cards'
 */
export const cards = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cards.url(args, options),
    method: 'get',
})

cards.definition = {
    methods: ["get","head"],
    url: '/delegations/{delegation}/accreditation/cards',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AccreditationController::cards
 * @see app/Http/Controllers/AccreditationController.php:220
 * @route '/delegations/{delegation}/accreditation/cards'
 */
cards.url = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return cards.definition.url
            .replace('{delegation}', parsedArgs.delegation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccreditationController::cards
 * @see app/Http/Controllers/AccreditationController.php:220
 * @route '/delegations/{delegation}/accreditation/cards'
 */
cards.get = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cards.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AccreditationController::cards
 * @see app/Http/Controllers/AccreditationController.php:220
 * @route '/delegations/{delegation}/accreditation/cards'
 */
cards.head = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cards.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AccreditationController::card
 * @see app/Http/Controllers/AccreditationController.php:193
 * @route '/accreditations/{accreditation}/card'
 */
export const card = (args: { accreditation: number | { id: number } } | [accreditation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: card.url(args, options),
    method: 'get',
})

card.definition = {
    methods: ["get","head"],
    url: '/accreditations/{accreditation}/card',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AccreditationController::card
 * @see app/Http/Controllers/AccreditationController.php:193
 * @route '/accreditations/{accreditation}/card'
 */
card.url = (args: { accreditation: number | { id: number } } | [accreditation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { accreditation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { accreditation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    accreditation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        accreditation: typeof args.accreditation === 'object'
                ? args.accreditation.id
                : args.accreditation,
                }

    return card.definition.url
            .replace('{accreditation}', parsedArgs.accreditation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccreditationController::card
 * @see app/Http/Controllers/AccreditationController.php:193
 * @route '/accreditations/{accreditation}/card'
 */
card.get = (args: { accreditation: number | { id: number } } | [accreditation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: card.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AccreditationController::card
 * @see app/Http/Controllers/AccreditationController.php:193
 * @route '/accreditations/{accreditation}/card'
 */
card.head = (args: { accreditation: number | { id: number } } | [accreditation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: card.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AccreditationController::destroy
 * @see app/Http/Controllers/AccreditationController.php:177
 * @route '/accreditations/{accreditation}'
 */
export const destroy = (args: { accreditation: number | { id: number } } | [accreditation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/accreditations/{accreditation}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AccreditationController::destroy
 * @see app/Http/Controllers/AccreditationController.php:177
 * @route '/accreditations/{accreditation}'
 */
destroy.url = (args: { accreditation: number | { id: number } } | [accreditation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { accreditation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { accreditation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    accreditation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        accreditation: typeof args.accreditation === 'object'
                ? args.accreditation.id
                : args.accreditation,
                }

    return destroy.definition.url
            .replace('{accreditation}', parsedArgs.accreditation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccreditationController::destroy
 * @see app/Http/Controllers/AccreditationController.php:177
 * @route '/accreditations/{accreditation}'
 */
destroy.delete = (args: { accreditation: number | { id: number } } | [accreditation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const accreditation = {
    store: Object.assign(store, store),
index: Object.assign(index, index),
cards: Object.assign(cards, cards),
card: Object.assign(card, card),
destroy: Object.assign(destroy, destroy),
}

export default accreditation