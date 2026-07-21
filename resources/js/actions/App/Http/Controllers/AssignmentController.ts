import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:27
 * @route '/assignments'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/assignments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:27
 * @route '/assignments'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:27
 * @route '/assignments'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:27
 * @route '/assignments'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:27
 * @route '/assignments'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:27
 * @route '/assignments'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:27
 * @route '/assignments'
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
* @see \App\Http\Controllers\AssignmentController::options
 * @see app/Http/Controllers/AssignmentController.php:42
 * @route '/assignments/options'
 */
export const options = (routeOptions?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: options.url(routeOptions),
    method: 'get',
})

options.definition = {
    methods: ["get","head"],
    url: '/assignments/options',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AssignmentController::options
 * @see app/Http/Controllers/AssignmentController.php:42
 * @route '/assignments/options'
 */
options.url = (routeOptions?: RouteQueryOptions) => {
    return options.definition.url
 + queryParams(routeOptions)
}

/**
* @see \App\Http\Controllers\AssignmentController::options
 * @see app/Http/Controllers/AssignmentController.php:42
 * @route '/assignments/options'
 */
options.get = (routeOptions?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: options.url(routeOptions),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AssignmentController::options
 * @see app/Http/Controllers/AssignmentController.php:42
 * @route '/assignments/options'
 */
options.head = (routeOptions?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: options.url(routeOptions),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AssignmentController::options
 * @see app/Http/Controllers/AssignmentController.php:42
 * @route '/assignments/options'
 */
    const optionsForm = (routeOptions?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: options.url(
            
                            routeOptions
                   ),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AssignmentController::options
 * @see app/Http/Controllers/AssignmentController.php:42
 * @route '/assignments/options'
 */
        optionsForm.get = (routeOptions?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: options.url(
                
                                routeOptions
                           ),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AssignmentController::options
 * @see app/Http/Controllers/AssignmentController.php:42
 * @route '/assignments/options'
 */
        optionsForm.head = (routeOptions?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: options.url({
                        [routeOptions?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(routeOptions?.query ?? routeOptions?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    options.form = optionsForm
/**
* @see \App\Http\Controllers\AssignmentController::preview
 * @see app/Http/Controllers/AssignmentController.php:66
 * @route '/assignments/preview'
 */
export const preview = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: preview.url(options),
    method: 'get',
})

preview.definition = {
    methods: ["get","head"],
    url: '/assignments/preview',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AssignmentController::preview
 * @see app/Http/Controllers/AssignmentController.php:66
 * @route '/assignments/preview'
 */
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AssignmentController::preview
 * @see app/Http/Controllers/AssignmentController.php:66
 * @route '/assignments/preview'
 */
preview.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: preview.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AssignmentController::preview
 * @see app/Http/Controllers/AssignmentController.php:66
 * @route '/assignments/preview'
 */
preview.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: preview.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AssignmentController::preview
 * @see app/Http/Controllers/AssignmentController.php:66
 * @route '/assignments/preview'
 */
    const previewForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: preview.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AssignmentController::preview
 * @see app/Http/Controllers/AssignmentController.php:66
 * @route '/assignments/preview'
 */
        previewForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: preview.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AssignmentController::preview
 * @see app/Http/Controllers/AssignmentController.php:66
 * @route '/assignments/preview'
 */
        previewForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: preview.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    preview.form = previewForm
/**
* @see \App\Http\Controllers\AssignmentController::store
 * @see app/Http/Controllers/AssignmentController.php:86
 * @route '/assignments'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/assignments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AssignmentController::store
 * @see app/Http/Controllers/AssignmentController.php:86
 * @route '/assignments'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AssignmentController::store
 * @see app/Http/Controllers/AssignmentController.php:86
 * @route '/assignments'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AssignmentController::store
 * @see app/Http/Controllers/AssignmentController.php:86
 * @route '/assignments'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AssignmentController::store
 * @see app/Http/Controllers/AssignmentController.php:86
 * @route '/assignments'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\AssignmentController::distribute
 * @see app/Http/Controllers/AssignmentController.php:135
 * @route '/assignments/distribute'
 */
export const distribute = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: distribute.url(options),
    method: 'post',
})

distribute.definition = {
    methods: ["post"],
    url: '/assignments/distribute',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AssignmentController::distribute
 * @see app/Http/Controllers/AssignmentController.php:135
 * @route '/assignments/distribute'
 */
distribute.url = (options?: RouteQueryOptions) => {
    return distribute.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AssignmentController::distribute
 * @see app/Http/Controllers/AssignmentController.php:135
 * @route '/assignments/distribute'
 */
distribute.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: distribute.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AssignmentController::distribute
 * @see app/Http/Controllers/AssignmentController.php:135
 * @route '/assignments/distribute'
 */
    const distributeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: distribute.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AssignmentController::distribute
 * @see app/Http/Controllers/AssignmentController.php:135
 * @route '/assignments/distribute'
 */
        distributeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: distribute.url(options),
            method: 'post',
        })
    
    distribute.form = distributeForm
const AssignmentController = { index, options, preview, store, distribute }

export default AssignmentController