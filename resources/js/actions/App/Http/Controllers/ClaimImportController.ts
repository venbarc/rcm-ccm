import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:18
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
 * @see app/Http/Controllers/ClaimImportController.php:18
 * @route '/claims-import'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:18
 * @route '/claims-import'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:18
 * @route '/claims-import'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:18
 * @route '/claims-import'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:18
 * @route '/claims-import'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimImportController::index
 * @see app/Http/Controllers/ClaimImportController.php:18
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
 * @see app/Http/Controllers/ClaimImportController.php:32
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
 * @see app/Http/Controllers/ClaimImportController.php:32
 * @route '/claims-import'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimImportController::store
 * @see app/Http/Controllers/ClaimImportController.php:32
 * @route '/claims-import'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\ClaimImportController::store
 * @see app/Http/Controllers/ClaimImportController.php:32
 * @route '/claims-import'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ClaimImportController::store
 * @see app/Http/Controllers/ClaimImportController.php:32
 * @route '/claims-import'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\ClaimImportController::progress
 * @see app/Http/Controllers/ClaimImportController.php:53
 * @route '/claims-import/{claimImport}/progress'
 */
export const progress = (args: { claimImport: number | { id: number } } | [claimImport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: progress.url(args, options),
    method: 'get',
})

progress.definition = {
    methods: ["get","head"],
    url: '/claims-import/{claimImport}/progress',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimImportController::progress
 * @see app/Http/Controllers/ClaimImportController.php:53
 * @route '/claims-import/{claimImport}/progress'
 */
progress.url = (args: { claimImport: number | { id: number } } | [claimImport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { claimImport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { claimImport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    claimImport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        claimImport: typeof args.claimImport === 'object'
                ? args.claimImport.id
                : args.claimImport,
                }

    return progress.definition.url
            .replace('{claimImport}', parsedArgs.claimImport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimImportController::progress
 * @see app/Http/Controllers/ClaimImportController.php:53
 * @route '/claims-import/{claimImport}/progress'
 */
progress.get = (args: { claimImport: number | { id: number } } | [claimImport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: progress.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimImportController::progress
 * @see app/Http/Controllers/ClaimImportController.php:53
 * @route '/claims-import/{claimImport}/progress'
 */
progress.head = (args: { claimImport: number | { id: number } } | [claimImport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: progress.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimImportController::progress
 * @see app/Http/Controllers/ClaimImportController.php:53
 * @route '/claims-import/{claimImport}/progress'
 */
    const progressForm = (args: { claimImport: number | { id: number } } | [claimImport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: progress.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimImportController::progress
 * @see app/Http/Controllers/ClaimImportController.php:53
 * @route '/claims-import/{claimImport}/progress'
 */
        progressForm.get = (args: { claimImport: number | { id: number } } | [claimImport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: progress.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimImportController::progress
 * @see app/Http/Controllers/ClaimImportController.php:53
 * @route '/claims-import/{claimImport}/progress'
 */
        progressForm.head = (args: { claimImport: number | { id: number } } | [claimImport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: progress.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    progress.form = progressForm
const ClaimImportController = { index, store, progress }

export default ClaimImportController