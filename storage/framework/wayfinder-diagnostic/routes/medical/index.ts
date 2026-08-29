import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MedicalClearanceController::index
 * @see app/Http/Controllers/MedicalClearanceController.php:45
 * @route '/medical'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/medical',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MedicalClearanceController::index
 * @see app/Http/Controllers/MedicalClearanceController.php:45
 * @route '/medical'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MedicalClearanceController::index
 * @see app/Http/Controllers/MedicalClearanceController.php:45
 * @route '/medical'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MedicalClearanceController::index
 * @see app/Http/Controllers/MedicalClearanceController.php:45
 * @route '/medical'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const medical = {
    index: Object.assign(index, index),
}

export default medical