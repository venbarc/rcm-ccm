import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CurrentAccountController::update
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
export const update = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/account-type/switch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CurrentAccountController::update
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CurrentAccountController::update
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
update.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\CurrentAccountController::update
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
    const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\CurrentAccountController::update
 * @see app/Http/Controllers/CurrentAccountController.php:12
 * @route '/account-type/switch'
 */
        updateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(options),
            method: 'post',
        })
    
    update.form = updateForm
const CurrentAccountController = { update }

export default CurrentAccountController