import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\PortalController::show
 * @see app/Http/Controllers/PortalController.php:728
 * @route '/news/{slug}'
 */
export const show = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/news/{slug}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::show
 * @see app/Http/Controllers/PortalController.php:728
 * @route '/news/{slug}'
 */
show.url = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { slug: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    slug: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        slug: args.slug,
                }

    return show.definition.url
            .replace('{slug}', parsedArgs.slug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::show
 * @see app/Http/Controllers/PortalController.php:728
 * @route '/news/{slug}'
 */
show.get = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::show
 * @see app/Http/Controllers/PortalController.php:728
 * @route '/news/{slug}'
 */
show.head = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\NewsController::publicImage
 * @see app/Http/Controllers/NewsController.php:92
 * @route '/news-images/{newsItem}'
 */
export const publicImage = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicImage.url(args, options),
    method: 'get',
})

publicImage.definition = {
    methods: ["get","head"],
    url: '/news-images/{newsItem}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\NewsController::publicImage
 * @see app/Http/Controllers/NewsController.php:92
 * @route '/news-images/{newsItem}'
 */
publicImage.url = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return publicImage.definition.url
            .replace('{newsItem}', parsedArgs.newsItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\NewsController::publicImage
 * @see app/Http/Controllers/NewsController.php:92
 * @route '/news-images/{newsItem}'
 */
publicImage.get = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicImage.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\NewsController::publicImage
 * @see app/Http/Controllers/NewsController.php:92
 * @route '/news-images/{newsItem}'
 */
publicImage.head = (args: { newsItem: string | number | { id: string | number } } | [newsItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicImage.url(args, options),
    method: 'head',
})
const news = {
    show: Object.assign(show, show),
publicImage: Object.assign(publicImage, publicImage),
}

export default news