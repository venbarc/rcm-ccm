import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { PayerBalance } from './types';
import { formatCount, formatCurrency } from './types';

export function PayerBalanceCard({ payerBalance }: { payerBalance: PayerBalance | null }) {
    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="text-xl">Top Payer Balance</CardTitle>
                <CardDescription>Largest outstanding payer in the selected range</CardDescription>
            </CardHeader>
            <CardContent>
                {payerBalance ? (
                    <div className="space-y-1">
                        <p className="text-muted-foreground text-sm font-medium">{payerBalance.payer}</p>
                        <p className="text-2xl font-semibold tabular-nums">{formatCount(payerBalance.count)} lines</p>
                        <p className="text-base font-medium tabular-nums">{formatCurrency(payerBalance.amount)}</p>
                    </div>
                ) : (
                    <div className="border-muted/60 bg-muted/10 text-muted-foreground rounded-md border border-dashed px-4 py-8 text-center text-sm">
                        No payer balance data found.
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
