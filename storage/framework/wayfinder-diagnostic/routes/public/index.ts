import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import scoreboard853524 from './scoreboard'
import teamsE68ab5 from './teams'
/**
* @see \App\Http\Controllers\PortalController::meet
 * @see app/Http/Controllers/PortalController.php:117
 * @route '/meets/{meet}'
 */
export const meet = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: meet.url(args, options),
    method: 'get',
})

meet.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::meet
 * @see app/Http/Controllers/PortalController.php:117
 * @route '/meets/{meet}'
 */
meet.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return meet.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::meet
 * @see app/Http/Controllers/PortalController.php:117
 * @route '/meets/{meet}'
 */
meet.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: meet.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::meet
 * @see app/Http/Controllers/PortalController.php:117
 * @route '/meets/{meet}'
 */
meet.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: meet.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::results
 * @see app/Http/Controllers/PortalController.php:222
 * @route '/meets/{meet}/results'
 */
export const results = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: results.url(args, options),
    method: 'get',
})

results.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/results',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::results
 * @see app/Http/Controllers/PortalController.php:222
 * @route '/meets/{meet}/results'
 */
results.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return results.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::results
 * @see app/Http/Controllers/PortalController.php:222
 * @route '/meets/{meet}/results'
 */
results.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: results.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::results
 * @see app/Http/Controllers/PortalController.php:222
 * @route '/meets/{meet}/results'
 */
results.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: results.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::tally
 * @see app/Http/Controllers/PortalController.php:282
 * @route '/meets/{meet}/tally'
 */
export const tally = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tally.url(args, options),
    method: 'get',
})

tally.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/tally',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::tally
 * @see app/Http/Controllers/PortalController.php:282
 * @route '/meets/{meet}/tally'
 */
tally.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return tally.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::tally
 * @see app/Http/Controllers/PortalController.php:282
 * @route '/meets/{meet}/tally'
 */
tally.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tally.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::tally
 * @see app/Http/Controllers/PortalController.php:282
 * @route '/meets/{meet}/tally'
 */
tally.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: tally.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::rankings
 * @see app/Http/Controllers/PortalController.php:338
 * @route '/meets/{meet}/rankings'
 */
export const rankings = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rankings.url(args, options),
    method: 'get',
})

rankings.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/rankings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::rankings
 * @see app/Http/Controllers/PortalController.php:338
 * @route '/meets/{meet}/rankings'
 */
rankings.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return rankings.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::rankings
 * @see app/Http/Controllers/PortalController.php:338
 * @route '/meets/{meet}/rankings'
 */
rankings.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rankings.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::rankings
 * @see app/Http/Controllers/PortalController.php:338
 * @route '/meets/{meet}/rankings'
 */
rankings.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: rankings.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::athletics
 * @see app/Http/Controllers/PortalController.php:397
 * @route '/meets/{meet}/athletics'
 */
export const athletics = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: athletics.url(args, options),
    method: 'get',
})

athletics.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/athletics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::athletics
 * @see app/Http/Controllers/PortalController.php:397
 * @route '/meets/{meet}/athletics'
 */
athletics.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return athletics.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::athletics
 * @see app/Http/Controllers/PortalController.php:397
 * @route '/meets/{meet}/athletics'
 */
athletics.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: athletics.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::athletics
 * @see app/Http/Controllers/PortalController.php:397
 * @route '/meets/{meet}/athletics'
 */
athletics.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: athletics.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::sports
 * @see app/Http/Controllers/PortalController.php:627
 * @route '/meets/{meet}/sports'
 */
export const sports = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sports.url(args, options),
    method: 'get',
})

sports.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/sports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::sports
 * @see app/Http/Controllers/PortalController.php:627
 * @route '/meets/{meet}/sports'
 */
sports.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return sports.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::sports
 * @see app/Http/Controllers/PortalController.php:627
 * @route '/meets/{meet}/sports'
 */
sports.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sports.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::sports
 * @see app/Http/Controllers/PortalController.php:627
 * @route '/meets/{meet}/sports'
 */
sports.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sports.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::gallery
 * @see app/Http/Controllers/PortalController.php:649
 * @route '/meets/{meet}/gallery'
 */
export const gallery = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: gallery.url(args, options),
    method: 'get',
})

gallery.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/gallery',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::gallery
 * @see app/Http/Controllers/PortalController.php:649
 * @route '/meets/{meet}/gallery'
 */
gallery.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return gallery.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::gallery
 * @see app/Http/Controllers/PortalController.php:649
 * @route '/meets/{meet}/gallery'
 */
gallery.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: gallery.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::gallery
 * @see app/Http/Controllers/PortalController.php:649
 * @route '/meets/{meet}/gallery'
 */
gallery.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: gallery.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::news
 * @see app/Http/Controllers/PortalController.php:700
 * @route '/meets/{meet}/news'
 */
export const news = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: news.url(args, options),
    method: 'get',
})

news.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/news',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::news
 * @see app/Http/Controllers/PortalController.php:700
 * @route '/meets/{meet}/news'
 */
news.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return news.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::news
 * @see app/Http/Controllers/PortalController.php:700
 * @route '/meets/{meet}/news'
 */
news.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: news.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::news
 * @see app/Http/Controllers/PortalController.php:700
 * @route '/meets/{meet}/news'
 */
news.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: news.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::contact
 * @see app/Http/Controllers/PortalController.php:747
 * @route '/meets/{meet}/contact'
 */
export const contact = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: contact.url(args, options),
    method: 'get',
})

contact.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/contact',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::contact
 * @see app/Http/Controllers/PortalController.php:747
 * @route '/meets/{meet}/contact'
 */
contact.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return contact.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::contact
 * @see app/Http/Controllers/PortalController.php:747
 * @route '/meets/{meet}/contact'
 */
contact.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: contact.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::contact
 * @see app/Http/Controllers/PortalController.php:747
 * @route '/meets/{meet}/contact'
 */
contact.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: contact.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::about
 * @see app/Http/Controllers/PortalController.php:773
 * @route '/meets/{meet}/about'
 */
export const about = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: about.url(args, options),
    method: 'get',
})

about.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/about',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::about
 * @see app/Http/Controllers/PortalController.php:773
 * @route '/meets/{meet}/about'
 */
about.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return about.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::about
 * @see app/Http/Controllers/PortalController.php:773
 * @route '/meets/{meet}/about'
 */
about.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: about.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::about
 * @see app/Http/Controllers/PortalController.php:773
 * @route '/meets/{meet}/about'
 */
about.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: about.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::faqs
 * @see app/Http/Controllers/PortalController.php:814
 * @route '/meets/{meet}/faqs'
 */
export const faqs = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: faqs.url(args, options),
    method: 'get',
})

faqs.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/faqs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::faqs
 * @see app/Http/Controllers/PortalController.php:814
 * @route '/meets/{meet}/faqs'
 */
faqs.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return faqs.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::faqs
 * @see app/Http/Controllers/PortalController.php:814
 * @route '/meets/{meet}/faqs'
 */
faqs.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: faqs.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::faqs
 * @see app/Http/Controllers/PortalController.php:814
 * @route '/meets/{meet}/faqs'
 */
faqs.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: faqs.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::search
 * @see app/Http/Controllers/PortalController.php:847
 * @route '/meets/{meet}/search'
 */
export const search = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(args, options),
    method: 'get',
})

search.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/search',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::search
 * @see app/Http/Controllers/PortalController.php:847
 * @route '/meets/{meet}/search'
 */
search.url = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                }

    return search.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::search
 * @see app/Http/Controllers/PortalController.php:847
 * @route '/meets/{meet}/search'
 */
search.get = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::search
 * @see app/Http/Controllers/PortalController.php:847
 * @route '/meets/{meet}/search'
 */
search.head = (args: { meet: string | number } | [meet: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::scoreboard
 * @see app/Http/Controllers/PortalController.php:516
 * @route '/meets/{meet}/matches/{match}/scoreboard'
 */
export const scoreboard = (args: { meet: string | number, match: string | number } | [meet: string | number, match: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: scoreboard.url(args, options),
    method: 'get',
})

scoreboard.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/matches/{match}/scoreboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::scoreboard
 * @see app/Http/Controllers/PortalController.php:516
 * @route '/meets/{meet}/matches/{match}/scoreboard'
 */
scoreboard.url = (args: { meet: string | number, match: string | number } | [meet: string | number, match: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                    match: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                                match: args.match,
                }

    return scoreboard.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::scoreboard
 * @see app/Http/Controllers/PortalController.php:516
 * @route '/meets/{meet}/matches/{match}/scoreboard'
 */
scoreboard.get = (args: { meet: string | number, match: string | number } | [meet: string | number, match: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: scoreboard.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::scoreboard
 * @see app/Http/Controllers/PortalController.php:516
 * @route '/meets/{meet}/matches/{match}/scoreboard'
 */
scoreboard.head = (args: { meet: string | number, match: string | number } | [meet: string | number, match: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: scoreboard.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalTeamsController::teams
 * @see app/Http/Controllers/PortalTeamsController.php:43
 * @route '/teams'
 */
export const teams = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: teams.url(options),
    method: 'get',
})

teams.definition = {
    methods: ["get","head"],
    url: '/teams',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalTeamsController::teams
 * @see app/Http/Controllers/PortalTeamsController.php:43
 * @route '/teams'
 */
teams.url = (options?: RouteQueryOptions) => {
    return teams.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalTeamsController::teams
 * @see app/Http/Controllers/PortalTeamsController.php:43
 * @route '/teams'
 */
teams.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: teams.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalTeamsController::teams
 * @see app/Http/Controllers/PortalTeamsController.php:43
 * @route '/teams'
 */
teams.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: teams.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalSportsController::sportsDirectory
 * @see app/Http/Controllers/PortalSportsController.php:34
 * @route '/sports-directory'
 */
export const sportsDirectory = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sportsDirectory.url(options),
    method: 'get',
})

sportsDirectory.definition = {
    methods: ["get","head"],
    url: '/sports-directory',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalSportsController::sportsDirectory
 * @see app/Http/Controllers/PortalSportsController.php:34
 * @route '/sports-directory'
 */
sportsDirectory.url = (options?: RouteQueryOptions) => {
    return sportsDirectory.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalSportsController::sportsDirectory
 * @see app/Http/Controllers/PortalSportsController.php:34
 * @route '/sports-directory'
 */
sportsDirectory.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sportsDirectory.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalSportsController::sportsDirectory
 * @see app/Http/Controllers/PortalSportsController.php:34
 * @route '/sports-directory'
 */
sportsDirectory.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sportsDirectory.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::liveSportPortal
 * @see app/Http/Controllers/PortalController.php:1013
 * @route '/live/{sportSlug}'
 */
export const liveSportPortal = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: liveSportPortal.url(args, options),
    method: 'get',
})

liveSportPortal.definition = {
    methods: ["get","head"],
    url: '/live/{sportSlug}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::liveSportPortal
 * @see app/Http/Controllers/PortalController.php:1013
 * @route '/live/{sportSlug}'
 */
liveSportPortal.url = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sportSlug: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    sportSlug: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sportSlug: args.sportSlug,
                }

    return liveSportPortal.definition.url
            .replace('{sportSlug}', parsedArgs.sportSlug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::liveSportPortal
 * @see app/Http/Controllers/PortalController.php:1013
 * @route '/live/{sportSlug}'
 */
liveSportPortal.get = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: liveSportPortal.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::liveSportPortal
 * @see app/Http/Controllers/PortalController.php:1013
 * @route '/live/{sportSlug}'
 */
liveSportPortal.head = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: liveSportPortal.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalController::sportPortal
 * @see app/Http/Controllers/PortalController.php:954
 * @route '/{sportSlug}'
 */
export const sportPortal = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sportPortal.url(args, options),
    method: 'get',
})

sportPortal.definition = {
    methods: ["get","head"],
    url: '/{sportSlug}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::sportPortal
 * @see app/Http/Controllers/PortalController.php:954
 * @route '/{sportSlug}'
 */
sportPortal.url = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sportSlug: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    sportSlug: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sportSlug: args.sportSlug,
                }

    return sportPortal.definition.url
            .replace('{sportSlug}', parsedArgs.sportSlug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::sportPortal
 * @see app/Http/Controllers/PortalController.php:954
 * @route '/{sportSlug}'
 */
sportPortal.get = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sportPortal.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::sportPortal
 * @see app/Http/Controllers/PortalController.php:954
 * @route '/{sportSlug}'
 */
sportPortal.head = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sportPortal.url(args, options),
    method: 'head',
})
const publicMethod = {
    meet: Object.assign(meet, meet),
results: Object.assign(results, results),
tally: Object.assign(tally, tally),
rankings: Object.assign(rankings, rankings),
athletics: Object.assign(athletics, athletics),
sports: Object.assign(sports, sports),
gallery: Object.assign(gallery, gallery),
news: Object.assign(news, news),
contact: Object.assign(contact, contact),
about: Object.assign(about, about),
faqs: Object.assign(faqs, faqs),
search: Object.assign(search, search),
scoreboard: Object.assign(scoreboard, scoreboard853524),
teams: Object.assign(teams, teamsE68ab5),
sportsDirectory: Object.assign(sportsDirectory, sportsDirectory),
liveSportPortal: Object.assign(liveSportPortal, liveSportPortal),
sportPortal: Object.assign(sportPortal, sportPortal),
}

export default publicMethod