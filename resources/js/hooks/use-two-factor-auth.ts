import { useState } from 'react';

export const OTP_MAX_LENGTH = 6;

const jsonHeaders = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
};

function getCsrfToken() {
    return document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.getAttribute('content');
}

async function fetchJson<T>(url: string, init?: RequestInit): Promise<T> {
    const csrfToken = getCsrfToken();
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            ...jsonHeaders,
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            ...(init?.headers ?? {}),
        },
        ...init,
    });

    if (!response.ok) {
        throw new Error('Unable to complete the request.');
    }

    return response.json() as Promise<T>;
}

export function useTwoFactorAuth() {
    const [qrCodeSvg, setQrCodeSvg] = useState('');
    const [recoveryCodesList, setRecoveryCodesList] = useState<string[]>([]);
    const [errors, setErrors] = useState<string[]>([]);

    const clearSetupData = () => {
        setQrCodeSvg('');
        setRecoveryCodesList([]);
        setErrors([]);
    };

    const fetchRecoveryCodes = async () => {
        try {
            const response = await fetchJson<string[]>(route('two-factor.recovery-codes'));
            setRecoveryCodesList(response);
            setErrors([]);
        } catch (error) {
            setErrors([error instanceof Error ? error.message : 'Unable to load recovery codes.']);
        }
    };

    const fetchSetupData = async () => {
        try {
            const response = await fetchJson<{ svg: string }>(route('two-factor.qr-code'));
            setQrCodeSvg(response.svg);
            setErrors([]);
            await fetchRecoveryCodes();
        } catch (error) {
            setErrors([error instanceof Error ? error.message : 'Unable to load two-factor setup details.']);
        }
    };

    return {
        clearSetupData,
        errors,
        fetchRecoveryCodes,
        fetchSetupData,
        hasSetupData: Boolean(qrCodeSvg),
        qrCodeSvg,
        recoveryCodesList,
    };
}
