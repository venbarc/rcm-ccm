import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { ManagedUser } from '@/components/users/types';
import type { AccountTypeOption } from '@/types';
import { useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

interface EditAccessDialogProps {
    user: ManagedUser;
    accountTypes: AccountTypeOption[];
    onClose: () => void;
}

export function EditAccessDialog({ user, accountTypes, onClose }: EditAccessDialogProps) {
    const form = useForm({
        is_admin: user.is_admin,
        can_assign_claims: user.can_assign_claims,
        account_types: user.account_types ?? [],
    });
    const toggleAccount = (value: string) =>
        form.setData(
            'account_types',
            form.data.account_types.includes(value) ? form.data.account_types.filter((item) => item !== value) : [...form.data.account_types, value],
        );
    const save = () => form.patch(`/user-management/${user.id}`, { preserveScroll: true, onSuccess: onClose });

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit user access</DialogTitle>
                    <DialogDescription>
                        Permissions apply to {user.name}. OneAccess remains the identity provider and no local approval is required.
                    </DialogDescription>
                </DialogHeader>
                <div className="rounded-xl border border-sky-100 bg-sky-50/70 p-4">
                    <p className="font-semibold text-slate-900">{user.name}</p>
                    <p className="text-sm text-slate-600">{user.email}</p>
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm">
                        <input
                            checked={form.data.is_admin}
                            className="mt-1 accent-blue-700"
                            onChange={(event) => form.setData('is_admin', event.target.checked)}
                            type="checkbox"
                        />
                        <span>
                            <strong className="block text-slate-900">Administrator</strong>
                            <span className="text-muted-foreground text-xs">Manages access and their own team</span>
                        </span>
                    </label>
                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm">
                        <input
                            checked={form.data.can_assign_claims}
                            className="mt-1 accent-blue-700"
                            onChange={(event) => form.setData('can_assign_claims', event.target.checked)}
                            type="checkbox"
                        />
                        <span>
                            <strong className="block text-slate-900">Claims manager</strong>
                            <span className="text-muted-foreground text-xs">Can assign claims within their scope</span>
                        </span>
                    </label>
                </div>
                <div>
                    <p className="mb-2 text-sm font-semibold text-slate-900">Account access</p>
                    <div className="grid gap-2 sm:grid-cols-2">
                        {accountTypes.map((account) => (
                            <label
                                className={`flex items-center gap-3 rounded-xl border p-3 text-sm ${account.ready ? 'cursor-pointer bg-white' : 'text-muted-foreground cursor-not-allowed bg-slate-50'}`}
                                key={account.value}
                            >
                                <input
                                    checked={form.data.account_types.includes(account.value)}
                                    className="accent-blue-700"
                                    disabled={!account.ready}
                                    onChange={() => toggleAccount(account.value)}
                                    type="checkbox"
                                />
                                <span>{account.label}</span>
                                {!account.ready && (
                                    <Badge className="ml-auto" variant="outline">
                                        Coming soon
                                    </Badge>
                                )}
                            </label>
                        ))}
                    </div>
                    <InputError message={form.errors.account_types} />
                </div>
                <DialogFooter>
                    <Button onClick={onClose} variant="outline">
                        Cancel
                    </Button>
                    <Button disabled={form.processing} onClick={save}>
                        <ShieldCheck />
                        {form.processing ? 'Saving...' : 'Save access'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
