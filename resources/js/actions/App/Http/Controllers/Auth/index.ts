import SsoCallbackController from './SsoCallbackController'
import SsoChooseAccountController from './SsoChooseAccountController'
const Auth = {
    SsoCallbackController: Object.assign(SsoCallbackController, SsoCallbackController),
SsoChooseAccountController: Object.assign(SsoChooseAccountController, SsoChooseAccountController),
}

export default Auth