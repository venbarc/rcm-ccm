import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
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
const chooseAccount = {
    store: Object.assign(store, store),
}

export default chooseAccount