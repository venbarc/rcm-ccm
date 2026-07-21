import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\CurrentAccountController::switchMethod
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
export const switchMethod = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: switchMethod.url(options),
    method: 'post',
})

switchMethod.definition = {
    methods: ["post"],
    url: '/account-type/switch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CurrentAccountController::switchMethod
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
switchMethod.url = (options?: RouteQueryOptions) => {
    return switchMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CurrentAccountController::switchMethod
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
switchMethod.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: switchMethod.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CurrentAccountController::switchMethod
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
    const switchMethodForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: switchMethod.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CurrentAccountController::switchMethod
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
        switchMethodForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: switchMethod.url(options),
            method: 'post',
        })
    
    switchMethod.form = switchMethodForm
const accountType = {
    switch: Object.assign(switchMethod, switchMethod),
}

export default accountType