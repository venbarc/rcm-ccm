import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { ManagedUser } from '@/components/users/types';
import type { AccountTypeOption } from '@/types';
import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

interface EditAccessDialogProps {
    user: ManagedUser;
    accountTypes: AccountTypeOption[];
    onClose: () => void;
}

export function EditAccessDialog({ user, accountTypes, onClose }: EditAccessDialogProps) {
    const form = useForm({
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
                        Manage account access for {user.name}. Roles are controlled by OneAccess and cannot be changed here.
                    </DialogDescription>
                </DialogHeader>
                <div className="border-border bg-secondary/70 rounded-xl border p-4">
                    <p className="font-semibold text-slate-900">{user.name}</p>
                    <p className="text-sm text-slate-600">{user.email}</p>
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
                                    className="accent-primary"
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
                        <Save />
                        {form.processing ? 'Saving...' : 'Save access'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
