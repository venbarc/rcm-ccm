import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
const SsoCallbackController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: SsoCallbackController.url(options),
    method: 'post',
})

SsoCallbackController.definition = {
    methods: ["post"],
    url: '/sso/callback',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
SsoCallbackController.url = (options?: RouteQueryOptions) => {
    return SsoCallbackController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
SsoCallbackController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: SsoCallbackController.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
    const SsoCallbackControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: SsoCallbackController.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Auth\SsoCallbackController::__invoke
 * @see app/Http/Controllers/Auth/SsoCallbackController.php:18
 * @route '/sso/callback'
 */
        SsoCallbackControllerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: SsoCallbackController.url(options),
            method: 'post',
        })
    
    SsoCallbackController.form = SsoCallbackControllerForm
export default SsoCallbackController