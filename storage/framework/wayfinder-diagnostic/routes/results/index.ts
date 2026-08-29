import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import attachments from './attachments'
import eventSecretariat from './event-secretariat'
import medalAwards from './medal-awards'
/**
* @see \App\Http\Controllers\ResultController::index
 * @see app/Http/Controllers/ResultController.php:51
 * @route '/results'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/results',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ResultController::index
 * @see app/Http/Controllers/ResultController.php:51
 * @route '/results'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultController::index
 * @see app/Http/Controllers/ResultController.php:51
 * @route '/results'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ResultController::index
 * @see app/Http/Controllers/ResultController.php:51
 * @route '/results'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ResultWorkflowController::form
 * @see app/Http/Controllers/ResultWorkflowController.php:34
 * @route '/results/{result}/form'
 */
export const form = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: form.url(args, options),
    method: 'get',
})

form.definition = {
    methods: ["get","head"],
    url: '/results/{result}/form',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::form
 * @see app/Http/Controllers/ResultWorkflowController.php:34
 * @route '/results/{result}/form'
 */
form.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return form.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::form
 * @see app/Http/Controllers/ResultWorkflowController.php:34
 * @route '/results/{result}/form'
 */
form.get = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: form.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ResultWorkflowController::form
 * @see app/Http/Controllers/ResultWorkflowController.php:34
 * @route '/results/{result}/form'
 */
form.head = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: form.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ResultWorkflowController::submit
 * @see app/Http/Controllers/ResultWorkflowController.php:124
 * @route '/results/{result}/submit'
 */
export const submit = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/results/{result}/submit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::submit
 * @see app/Http/Controllers/ResultWorkflowController.php:124
 * @route '/results/{result}/submit'
 */
submit.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return submit.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::submit
 * @see app/Http/Controllers/ResultWorkflowController.php:124
 * @route '/results/{result}/submit'
 */
submit.post = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ResultWorkflowController::tmConfirm
 * @see app/Http/Controllers/ResultWorkflowController.php:157
 * @route '/results/{result}/tm-confirmation'
 */
export const tmConfirm = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: tmConfirm.url(args, options),
    method: 'post',
})

tmConfirm.definition = {
    methods: ["post"],
    url: '/results/{result}/tm-confirmation',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::tmConfirm
 * @see app/Http/Controllers/ResultWorkflowController.php:157
 * @route '/results/{result}/tm-confirmation'
 */
tmConfirm.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return tmConfirm.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::tmConfirm
 * @see app/Http/Controllers/ResultWorkflowController.php:157
 * @route '/results/{result}/tm-confirmation'
 */
tmConfirm.post = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: tmConfirm.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ResultWorkflowController::returnMethod
 * @see app/Http/Controllers/ResultWorkflowController.php:181
 * @route '/results/{result}/return'
 */
export const returnMethod = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: returnMethod.url(args, options),
    method: 'post',
})

returnMethod.definition = {
    methods: ["post"],
    url: '/results/{result}/return',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::returnMethod
 * @see app/Http/Controllers/ResultWorkflowController.php:181
 * @route '/results/{result}/return'
 */
returnMethod.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return returnMethod.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::returnMethod
 * @see app/Http/Controllers/ResultWorkflowController.php:181
 * @route '/results/{result}/return'
 */
returnMethod.post = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: returnMethod.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ResultWorkflowController::official
 * @see app/Http/Controllers/ResultWorkflowController.php:217
 * @route '/results/{result}/official'
 */
export const official = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: official.url(args, options),
    method: 'post',
})

official.definition = {
    methods: ["post"],
    url: '/results/{result}/official',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::official
 * @see app/Http/Controllers/ResultWorkflowController.php:217
 * @route '/results/{result}/official'
 */
official.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return official.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::official
 * @see app/Http/Controllers/ResultWorkflowController.php:217
 * @route '/results/{result}/official'
 */
official.post = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: official.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ResultWorkflowController::reopen
 * @see app/Http/Controllers/ResultWorkflowController.php:236
 * @route '/results/{result}/reopen'
 */
export const reopen = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reopen.url(args, options),
    method: 'post',
})

reopen.definition = {
    methods: ["post"],
    url: '/results/{result}/reopen',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::reopen
 * @see app/Http/Controllers/ResultWorkflowController.php:236
 * @route '/results/{result}/reopen'
 */
reopen.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return reopen.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::reopen
 * @see app/Http/Controllers/ResultWorkflowController.php:236
 * @route '/results/{result}/reopen'
 */
reopen.post = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reopen.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ResultController::validate
 * @see app/Http/Controllers/ResultController.php:379
 * @route '/results/{result}/validate'
 */
export const validate = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: validate.url(args, options),
    method: 'patch',
})

validate.definition = {
    methods: ["patch"],
    url: '/results/{result}/validate',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ResultController::validate
 * @see app/Http/Controllers/ResultController.php:379
 * @route '/results/{result}/validate'
 */
validate.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return validate.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultController::validate
 * @see app/Http/Controllers/ResultController.php:379
 * @route '/results/{result}/validate'
 */
validate.patch = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: validate.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ResultController::correct
 * @see app/Http/Controllers/ResultController.php:417
 * @route '/results/{result}/correct'
 */
export const correct = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: correct.url(args, options),
    method: 'patch',
})

correct.definition = {
    methods: ["patch"],
    url: '/results/{result}/correct',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ResultController::correct
 * @see app/Http/Controllers/ResultController.php:417
 * @route '/results/{result}/correct'
 */
correct.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return correct.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultController::correct
 * @see app/Http/Controllers/ResultController.php:417
 * @route '/results/{result}/correct'
 */
correct.patch = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: correct.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ResultController::destroy
 * @see app/Http/Controllers/ResultController.php:468
 * @route '/results/{result}'
 */
export const destroy = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/results/{result}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ResultController::destroy
 * @see app/Http/Controllers/ResultController.php:468
 * @route '/results/{result}'
 */
destroy.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return destroy.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultController::destroy
 * @see app/Http/Controllers/ResultController.php:468
 * @route '/results/{result}'
 */
destroy.delete = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ResultController::store
 * @see app/Http/Controllers/ResultController.php:305
 * @route '/results'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/results',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ResultController::store
 * @see app/Http/Controllers/ResultController.php:305
 * @route '/results'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultController::store
 * @see app/Http/Controllers/ResultController.php:305
 * @route '/results'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ResultController::update
 * @see app/Http/Controllers/ResultController.php:333
 * @route '/results/{result}'
 */
export const update = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/results/{result}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ResultController::update
 * @see app/Http/Controllers/ResultController.php:333
 * @route '/results/{result}'
 */
update.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return update.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultController::update
 * @see app/Http/Controllers/ResultController.php:333
 * @route '/results/{result}'
 */
update.put = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})
const results = {
    index: Object.assign(index, index),
form: Object.assign(form, form),
attachments: Object.assign(attachments, attachments),
submit: Object.assign(submit, submit),
tmConfirm: Object.assign(tmConfirm, tmConfirm),
return: Object.assign(returnMethod, returnMethod),
eventSecretariat: Object.assign(eventSecretariat, eventSecretariat),
official: Object.assign(official, official),
medalAwards: Object.assign(medalAwards, medalAwards),
reopen: Object.assign(reopen, reopen),
validate: Object.assign(validate, validate),
correct: Object.assign(correct, correct),
destroy: Object.assign(destroy, destroy),
store: Object.assign(store, store),
update: Object.assign(update, update),
}

export default results