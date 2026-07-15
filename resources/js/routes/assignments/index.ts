import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:19
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
 * @see app/Http/Controllers/AssignmentController.php:19
 * @route '/assignments'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:19
 * @route '/assignments'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:19
 * @route '/assignments'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:19
 * @route '/assignments'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:19
 * @route '/assignments'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AssignmentController::index
 * @see app/Http/Controllers/AssignmentController.php:19
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
* @see \App\Http\Controllers\AssignmentController::store
 * @see app/Http/Controllers/AssignmentController.php:32
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
 * @see app/Http/Controllers/AssignmentController.php:32
 * @route '/assignments'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AssignmentController::store
 * @see app/Http/Controllers/AssignmentController.php:32
 * @route '/assignments'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AssignmentController::store
 * @see app/Http/Controllers/AssignmentController.php:32
 * @route '/assignments'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AssignmentController::store
 * @see app/Http/Controllers/AssignmentController.php:32
 * @route '/assignments'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\AssignmentController::distribute
 * @see app/Http/Controllers/AssignmentController.php:56
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
 * @see app/Http/Controllers/AssignmentController.php:56
 * @route '/assignments/distribute'
 */
distribute.url = (options?: RouteQueryOptions) => {
    return distribute.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AssignmentController::distribute
 * @see app/Http/Controllers/AssignmentController.php:56
 * @route '/assignments/distribute'
 */
distribute.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: distribute.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\AssignmentController::distribute
 * @see app/Http/Controllers/AssignmentController.php:56
 * @route '/assignments/distribute'
 */
    const distributeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: distribute.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\AssignmentController::distribute
 * @see app/Http/Controllers/AssignmentController.php:56
 * @route '/assignments/distribute'
 */
        distributeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: distribute.url(options),
            method: 'post',
        })
    
    distribute.form = distributeForm
const assignments = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
distribute: Object.assign(distribute, distribute),
}

export default assignments