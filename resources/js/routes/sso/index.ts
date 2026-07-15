import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import chooseAccount352260 from './choose-account'
/**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
export const callback = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: callback.url(options),
    method: 'post',
})

callback.definition = {
    methods: ["post"],
    url: '/sso/callback',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
callback.url = (options?: RouteQueryOptions) => {
    return callback.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
callback.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: callback.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
    const callbackForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: callback.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
        callbackForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: callback.url(options),
            method: 'post',
        })
    
    callback.form = callbackForm
/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::chooseAccount
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
export const chooseAccount = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: chooseAccount.url(options),
    method: 'get',
})

chooseAccount.definition = {
    methods: ["get","head"],
    url: '/sso/choose-account',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::chooseAccount
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
chooseAccount.url = (options?: RouteQueryOptions) => {
    return chooseAccount.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::chooseAccount
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
chooseAccount.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: chooseAccount.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::chooseAccount
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
chooseAccount.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: chooseAccount.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::chooseAccount
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
    const chooseAccountForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: chooseAccount.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::chooseAccount
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
        chooseAccountForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: chooseAccount.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Auth\SsoChooseAccountController::chooseAccount
 * @see app/Http/Controllers/Auth/SsoChooseAccountController.php:19
 * @route '/sso/choose-account'
 */
        chooseAccountForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: chooseAccount.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    chooseAccount.form = chooseAccountForm
const sso = {
    callback: Object.assign(callback, callback),
chooseAccount: Object.assign(chooseAccount, chooseAccount352260),
}

export default sso