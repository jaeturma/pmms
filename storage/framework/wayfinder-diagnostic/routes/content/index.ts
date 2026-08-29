import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import news from './news'
import faq from './faq'
import gallery from './gallery'
/**
* @see \App\Http\Controllers\ContentManagementController::index
 * @see app/Http/Controllers/ContentManagementController.php:15
 * @route '/content'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/content',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ContentManagementController::index
 * @see app/Http/Controllers/ContentManagementController.php:15
 * @route '/content'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ContentManagementController::index
 * @see app/Http/Controllers/ContentManagementController.php:15
 * @route '/content'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ContentManagementController::index
 * @see app/Http/Controllers/ContentManagementController.php:15
 * @route '/content'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const content = {
    index: Object.assign(index, index),
news: Object.assign(news, news),
faq: Object.assign(faq, faq),
gallery: Object.assign(gallery, gallery),
}

export default content