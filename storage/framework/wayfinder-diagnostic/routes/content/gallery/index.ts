import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\GalleryController::index
 * @see app/Http/Controllers/GalleryController.php:24
 * @route '/content/gallery'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/content/gallery',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\GalleryController::index
 * @see app/Http/Controllers/GalleryController.php:24
 * @route '/content/gallery'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\GalleryController::index
 * @see app/Http/Controllers/GalleryController.php:24
 * @route '/content/gallery'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\GalleryController::index
 * @see app/Http/Controllers/GalleryController.php:24
 * @route '/content/gallery'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\GalleryController::store
 * @see app/Http/Controllers/GalleryController.php:48
 * @route '/content/gallery'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/content/gallery',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\GalleryController::store
 * @see app/Http/Controllers/GalleryController.php:48
 * @route '/content/gallery'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\GalleryController::store
 * @see app/Http/Controllers/GalleryController.php:48
 * @route '/content/gallery'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\GalleryController::review
 * @see app/Http/Controllers/GalleryController.php:92
 * @route '/content/gallery/{galleryItem}/review'
 */
export const review = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})

review.definition = {
    methods: ["patch"],
    url: '/content/gallery/{galleryItem}/review',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\GalleryController::review
 * @see app/Http/Controllers/GalleryController.php:92
 * @route '/content/gallery/{galleryItem}/review'
 */
review.url = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return review.definition.url
            .replace('{galleryItem}', parsedArgs.galleryItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\GalleryController::review
 * @see app/Http/Controllers/GalleryController.php:92
 * @route '/content/gallery/{galleryItem}/review'
 */
review.patch = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\GalleryController::publish
 * @see app/Http/Controllers/GalleryController.php:109
 * @route '/content/gallery/publish'
 */
export const publish = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: publish.url(options),
    method: 'patch',
})

publish.definition = {
    methods: ["patch"],
    url: '/content/gallery/publish',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\GalleryController::publish
 * @see app/Http/Controllers/GalleryController.php:109
 * @route '/content/gallery/publish'
 */
publish.url = (options?: RouteQueryOptions) => {
    return publish.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\GalleryController::publish
 * @see app/Http/Controllers/GalleryController.php:109
 * @route '/content/gallery/publish'
 */
publish.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: publish.url(options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\GalleryController::unpublish
 * @see app/Http/Controllers/GalleryController.php:135
 * @route '/content/gallery/{galleryItem}/unpublish'
 */
export const unpublish = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: unpublish.url(args, options),
    method: 'patch',
})

unpublish.definition = {
    methods: ["patch"],
    url: '/content/gallery/{galleryItem}/unpublish',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\GalleryController::unpublish
 * @see app/Http/Controllers/GalleryController.php:135
 * @route '/content/gallery/{galleryItem}/unpublish'
 */
unpublish.url = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return unpublish.definition.url
            .replace('{galleryItem}', parsedArgs.galleryItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\GalleryController::unpublish
 * @see app/Http/Controllers/GalleryController.php:135
 * @route '/content/gallery/{galleryItem}/unpublish'
 */
unpublish.patch = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: unpublish.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\GalleryController::image
 * @see app/Http/Controllers/GalleryController.php:145
 * @route '/content/gallery/{galleryItem}/image'
 */
export const image = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: image.url(args, options),
    method: 'get',
})

image.definition = {
    methods: ["get","head"],
    url: '/content/gallery/{galleryItem}/image',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\GalleryController::image
 * @see app/Http/Controllers/GalleryController.php:145
 * @route '/content/gallery/{galleryItem}/image'
 */
image.url = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return image.definition.url
            .replace('{galleryItem}', parsedArgs.galleryItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\GalleryController::image
 * @see app/Http/Controllers/GalleryController.php:145
 * @route '/content/gallery/{galleryItem}/image'
 */
image.get = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: image.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\GalleryController::image
 * @see app/Http/Controllers/GalleryController.php:145
 * @route '/content/gallery/{galleryItem}/image'
 */
image.head = (args: { galleryItem: string | number | { id: string | number } } | [galleryItem: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: image.url(args, options),
    method: 'head',
})
const gallery = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
review: Object.assign(review, review),
publish: Object.assign(publish, publish),
unpublish: Object.assign(unpublish, unpublish),
image: Object.assign(image, image),
}

export default gallery