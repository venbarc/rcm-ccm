import HeadingSmall from '@/components/heading-small';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { OTP_MAX_LENGTH, useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { ShieldBan, ShieldCheck } from 'lucide-react';

interface TwoFactorProps {
    requiresConfirmation?: boolean;
    twoFactorConfirmed?: boolean;
    twoFactorEnabled?: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Two-factor authentication',
        href: '/settings/two-factor',
    },
];

export default function TwoFactor({
    requiresConfirmation = false,
    twoFactorConfirmed = false,
    twoFactorEnabled = false,
}: TwoFactorProps) {
    const setupState = useTwoFactorAuth();
    const confirmForm = useForm({
        code: '',
    });

    const enableTwoFactor = () => {
        router.post(
            route('two-factor.enable'),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    void setupState.fetchSetupData();
                },
            },
        );
    };

    const disableTwoFactor = () => {
        router.delete(route('two-factor.disable'), {
            preserveScroll: true,
            onSuccess: () => setupState.clearSetupData(),
        });
    };

    const confirmTwoFactor = () => {
        confirmForm.post(route('two-factor.confirm'), {
            preserveScroll: true,
            onSuccess: () => {
                confirmForm.reset();
                void setupState.fetchRecoveryCodes();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Two-factor authentication" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Two-factor authentication"
                        description="Protect your account with an authenticator app and one-time recovery codes."
                    />

                    <div className="space-y-4 rounded-xl border bg-card p-6">
                        <div className="flex items-center gap-3">
                            <Badge variant={twoFactorEnabled ? 'default' : 'destructive'}>
                                {twoFactorEnabled ? 'Enabled' : 'Disabled'}
                            </Badge>
                            {requiresConfirmation && twoFactorEnabled && !twoFactorConfirmed && (
                                <Badge variant="secondary">Confirmation required</Badge>
                            )}
                        </div>

                        <p className="text-sm text-muted-foreground">
                            {twoFactorEnabled
                                ? 'Your account can require a second factor at sign-in.'
                                : 'Enable two-factor authentication to require a six-digit code during sign-in.'}
                        </p>

                        <div className="flex flex-wrap gap-3">
                            {twoFactorEnabled ? (
                                <Button type="button" variant="destructive" onClick={disableTwoFactor}>
                                    <ShieldBan />
                                    Disable 2FA
                                </Button>
                            ) : (
                                <Button type="button" onClick={enableTwoFactor}>
                                    <ShieldCheck />
                                    Enable 2FA
                                </Button>
                            )}

                            {twoFactorEnabled && !setupState.hasSetupData && (
                                <Button type="button" variant="secondary" onClick={() => void setupState.fetchSetupData()}>
                                    Load setup details
                                </Button>
                            )}
                        </div>

                        {setupState.errors.length > 0 && (
                            <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                                {setupState.errors.join(' ')}
                            </div>
                        )}

                        {twoFactorEnabled && setupState.qrCodeSvg && (
                            <div className="space-y-4 rounded-lg border bg-muted/40 p-4">
                                <div>
                                    <p className="font-medium">Authenticator app setup</p>
                                    <p className="text-sm text-muted-foreground">
                                        Scan this QR code with Google Authenticator, Microsoft Authenticator, or another TOTP app.
                                    </p>
                                </div>

                                <div
                                    className="inline-block rounded-lg bg-white p-4"
                                    dangerouslySetInnerHTML={{ __html: setupState.qrCodeSvg }}
                                />

                                {requiresConfirmation && !twoFactorConfirmed && (
                                    <form
                                        className="space-y-3"
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            confirmTwoFactor();
                                        }}
                                    >
                                        <label className="block text-sm font-medium" htmlFor="two-factor-code">
                                            Confirm setup
                                        </label>
                                        <Input
                                            id="two-factor-code"
                                            value={confirmForm.data.code}
                                            onChange={(event) => confirmForm.setData('code', event.target.value.slice(0, OTP_MAX_LENGTH))}
                                            inputMode="numeric"
                                            autoComplete="one-time-code"
                                            placeholder="123456"
                                        />
                                        <p className="text-sm text-red-600">{confirmForm.errors.code}</p>
                                        <Button type="submit" disabled={confirmForm.processing}>
                                            Confirm code
                                        </Button>
                                    </form>
                                )}
                            </div>
                        )}
                    </div>

                    {twoFactorEnabled && (
                        <TwoFactorRecoveryCodes
                            errors={setupState.errors}
                            fetchRecoveryCodes={setupState.fetchRecoveryCodes}
                            recoveryCodesList={setupState.recoveryCodesList}
                        />
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
