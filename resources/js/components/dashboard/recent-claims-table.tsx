import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import type { RecentClaim } from './types';
import { formatCurrency } from './types';

export function RecentClaimsTable({ claims }: { claims: RecentClaim[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-xl">Recent Claims</CardTitle>
                <CardDescription>Latest claim IDs added to the system</CardDescription>
            </CardHeader>
            <CardContent className="overflow-x-auto">
                <table className="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr className="text-muted-foreground border-b text-left">
                            <th className="h-10 px-2 font-medium">Claim #</th>
                            <th className="h-10 px-2 font-medium">Patient</th>
                            <th className="h-10 px-2 font-medium">Lines</th>
                            <th className="h-10 px-2 text-right font-medium">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        {claims.map((claim) => (
                            <tr key={claim.id} className="hover:bg-muted/40 border-b transition-colors last:border-0">
                                <td className="p-2">
                                    <Link href={claim.view_url} className="text-primary font-medium hover:underline">
                                        {claim.claim_number}
                                    </Link>
                                </td>
                                <td className="p-2">{claim.patient_name ?? 'N/A'}</td>
                                <td className="p-2">
                                    <Badge variant="secondary">
                                        {claim.lines_count} line{claim.lines_count === 1 ? '' : 's'}
                                    </Badge>
                                </td>
                                <td className="p-2 text-right tabular-nums">{formatCurrency(claim.total_balance)}</td>
                            </tr>
                        ))}
                        {claims.length === 0 && (
                            <tr>
                                <td colSpan={4} className="text-muted-foreground p-8 text-center">
                                    No claims found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </CardContent>
        </Card>
    );
}
