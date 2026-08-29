import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\GalleryController::publicImage
 * @see app/Http/Controllers/GalleryController.php:153
 * @route '/gallery-images/{galleryItem}'
 */
export const publicImage = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicImage.url(args, options),
    method: 'get',
})

publicImage.definition = {
    methods: ["get","head"],
    url: '/gallery-images/{galleryItem}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\GalleryController::publicImage
 * @see app/Http/Controllers/GalleryController.php:153
 * @route '/gallery-images/{galleryItem}'
 */
publicImage.url = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { galleryItem: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { galleryItem: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    galleryItem: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        galleryItem: typeof args.galleryItem === 'object'
                ? args.galleryItem.id
                : args.galleryItem,
                }

    return publicImage.definition.url
            .replace('{galleryItem}', parsedArgs.galleryItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\GalleryController::publicImage
 * @see app/Http/Controllers/GalleryController.php:153
 * @route '/gallery-images/{galleryItem}'
 */
publicImage.get = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicImage.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\GalleryController::publicImage
 * @see app/Http/Controllers/GalleryController.php:153
 * @route '/gallery-images/{galleryItem}'
 */
publicImage.head = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicImage.url(args, options),
    method: 'head',
})
const gallery = {
    publicImage: Object.assign(publicImage, publicImage),
}

export default gallery