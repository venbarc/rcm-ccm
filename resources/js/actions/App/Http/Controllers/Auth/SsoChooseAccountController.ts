import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::create
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/sso/choose-account',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::create
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::create
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::create
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::create
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
    const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: create.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::create
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
        createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::create
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
        createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    create.form = createForm
/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::store
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:31
 * @route '/sso/choose-account'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/sso/choose-account',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::store
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:31
 * @route '/sso/choose-account'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::store
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:31
 * @route '/sso/choose-account'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::store
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:31
 * @route '/sso/choose-account'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::store
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:31
 * @route '/sso/choose-account'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
const SsoChooseAccountController = { create, store }

export default SsoChooseAccountController