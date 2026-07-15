import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:17
 * @route '/claims-import'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/claims-import',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:17
 * @route '/claims-import'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:17
 * @route '/claims-import'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:17
 * @route '/claims-import'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:17
 * @route '/claims-import'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:17
 * @route '/claims-import'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:17
 * @route '/claims-import'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \App\Http\Controllers\ClaimImportController::store
 * @see app/Http/Controllers/ClaimImportController.php:26
 * @route '/claims-import'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/claims-import',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ClaimImportController::store
 * @see app/Http/Controllers/ClaimImportController.php:26
 * @route '/claims-import'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimImportController::store
 * @see app/Http/Controllers/ClaimImportController.php:26
 * @route '/claims-import'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\ClaimImportController::store
 * @see app/Http/Controllers/ClaimImportController.php:26
 * @route '/claims-import'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ClaimImportController::store
 * @see app/Http/Controllers/ClaimImportController.php:26
 * @route '/claims-import'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
const ClaimImportController = { index, store }

export default ClaimImportController