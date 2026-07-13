import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { KeyRound, ShieldCheck, Smartphone } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface TwoFactorChallengeForm {
    code: string;
    recovery_code: string;
}

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<TwoFactorChallengeForm>({
        code: '',
        recovery_code: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('two-factor.login.store'), {
            onFinish: () => reset(showRecoveryInput ? 'recovery_code' : 'code'),
        });
    };

    return (
        <AuthLayout
            title={showRecoveryInput ? 'Use recovery code' : 'Two-factor authentication'}
            description={
                showRecoveryInput
                    ? 'Enter one of your recovery codes to finish signing in.'
                    : 'Enter the six-digit code from your authenticator app.'
            }
        >
            <Head title="Two-factor authentication" />

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="flex justify-center">
                    <div className="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                        {showRecoveryInput ? <KeyRound className="h-8 w-8" /> : <Smartphone className="h-8 w-8" />}
                    </div>
                </div>

                {showRecoveryInput ? (
                    <div className="grid gap-2">
                        <Input
                            name="recovery_code"
                            value={data.recovery_code}
                            onChange={(event) => setData('recovery_code', event.target.value)}
                            placeholder="XXXXX-XXXXX"
                            autoFocus
                            autoComplete="one-time-code"
                        />
                        <InputError message={errors.recovery_code} />
                    </div>
                ) : (
                    <div className="grid gap-3">
                        <div className="flex justify-center">
                            <InputOTP
                                maxLength={OTP_MAX_LENGTH}
                                value={data.code}
                                onChange={(value) => setData('code', value)}
                                pattern={REGEXP_ONLY_DIGITS}
                            >
                                <InputOTPGroup className="gap-2">
                                    {Array.from({ length: OTP_MAX_LENGTH }, (_, index) => (
                                        <InputOTPSlot key={index} index={index} />
                                    ))}
                                </InputOTPGroup>
                            </InputOTP>
                        </div>
                        <InputError message={errors.code} />
                    </div>
                )}

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing ? (
                        <>
                            <Spinner />
                            Verifying...
                        </>
                    ) : (
                        <>
                            <ShieldCheck className="h-4 w-4" />
                            Verify and continue
                        </>
                    )}
                </Button>

                <button
                    type="button"
                    className="text-sm text-primary hover:underline"
                    onClick={() => {
                        setShowRecoveryInput((current) => !current);
                        clearErrors();
                        reset();
                    }}
                >
                    {showRecoveryInput ? 'Use authenticator code instead' : 'Use a recovery code instead'}
                </button>
            </form>
        </AuthLayout>
    );
}
