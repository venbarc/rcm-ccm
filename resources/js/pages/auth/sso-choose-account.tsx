import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { FormEvent } from 'react';

interface Account { id: number; email: string }

export default function SsoChooseAccount({ accounts }: { accounts: Account[] }) {
    const form = useForm<{ user_id: number | null }>({ user_id: null });
    const submit = (event: FormEvent) => { event.preventDefault(); form.post('/sso/choose-account'); };
    return <AuthLayout title="Choose your local account" description="Your verified One Access emails match more than one RCM CCM account."><Head title="Choose an account" /><form className="space-y-5" onSubmit={submit}><div className="space-y-3">{accounts.map((account) => <label className={`flex cursor-pointer items-center gap-3 rounded-lg border p-4 ${form.data.user_id === account.id ? 'border-primary ring-2 ring-primary/20' : ''}`} key={account.id}><input checked={form.data.user_id === account.id} onChange={() => form.setData('user_id', account.id)} type="radio" /><Mail className="size-4 text-muted-foreground" /><span className="text-sm font-medium">{account.email}</span></label>)}</div>{form.errors.user_id && <p className="text-sm text-destructive">{form.errors.user_id}</p>}<Button className="w-full" disabled={form.processing || form.data.user_id === null}>Continue</Button></form></AuthLayout>;
}
