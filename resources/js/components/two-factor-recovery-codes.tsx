import AlertError from '@/components/alert-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/react';
import { Eye, EyeOff, RefreshCw } from 'lucide-react';
import { useState } from 'react';

interface TwoFactorRecoveryCodesProps {
    errors: string[];
    fetchRecoveryCodes: () => Promise<void>;
    recoveryCodesList: string[];
}

export default function TwoFactorRecoveryCodes({
    errors,
    fetchRecoveryCodes,
    recoveryCodesList,
}: TwoFactorRecoveryCodesProps) {
    const [codesAreVisible, setCodesAreVisible] = useState(false);
    const [processing, setProcessing] = useState(false);

    const toggleCodes = async () => {
        if (!codesAreVisible && !recoveryCodesList.length) {
            await fetchRecoveryCodes();
        }

        setCodesAreVisible((current) => !current);
    };

    const regenerateCodes = () => {
        setProcessing(true);

        router.post(
            route('two-factor.regenerate-recovery-codes'),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    void fetchRecoveryCodes();
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Recovery codes</CardTitle>
                <CardDescription>
                    Keep these in a secure location. Each code can be used once if your authenticator app is unavailable.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="flex flex-wrap gap-3">
                    <Button type="button" variant="secondary" onClick={() => void toggleCodes()}>
                        {codesAreVisible ? <EyeOff /> : <Eye />}
                        {codesAreVisible ? 'Hide codes' : 'View codes'}
                    </Button>
                    <Button type="button" variant="outline" onClick={regenerateCodes} disabled={processing}>
                        <RefreshCw className={processing ? 'animate-spin' : ''} />
                        Regenerate
                    </Button>
                </div>

                <AlertError errors={errors} />

                {codesAreVisible && (
                    <div className="grid gap-2 rounded-lg bg-muted p-4 font-mono text-sm">
                        {recoveryCodesList.length ? (
                            recoveryCodesList.map((code, index) => <div key={`${code}-${index}`}>{code}</div>)
                        ) : (
                            <div>Loading recovery codes...</div>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
