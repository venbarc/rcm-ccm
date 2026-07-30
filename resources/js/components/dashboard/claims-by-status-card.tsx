import { workStatusBadgeStyle } from '@/components/claims/utils';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { ReactNode } from 'react';
import type { ClaimByStatus } from './types';
import { formatCount, formatCurrency } from './types';

interface ClaimsByStatusCardProps {
    filters: ReactNode;
    statuses: ClaimByStatus[];
}

export function ClaimsByStatusCard({ filters, statuses }: ClaimsByStatusCardProps) {
    const totalCount = statuses.reduce((sum, item) => sum + item.count, 0);

    return (
        <Card className="h-full">
            <CardHeader className="space-y-4">
                <div>
                    <CardTitle>Claims by Status</CardTitle>
                    <CardDescription>Distribution of claim statuses</CardDescription>
                </div>
                <div className="border-muted/60 bg-muted/15 rounded-lg border p-3">{filters}</div>
            </CardHeader>
            <CardContent className="h-full">
                {statuses.length === 0 ? (
                    <div className="border-muted/60 bg-muted/10 text-muted-foreground rounded-md border border-dashed px-4 py-8 text-center text-sm">
                        No status data found.
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                        {statuses.map((item) => (
                            <div key={item.status} className="border-muted/60 bg-muted/20 min-w-0 rounded-md border p-2">
                                <div className="flex min-w-0 items-start justify-between gap-2">
                                    <TooltipProvider delayDuration={200}>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Badge
                                                    style={workStatusBadgeStyle(item.color)}
                                                    variant="outline"
                                                    className="inline-flex max-w-[calc(100%-4.75rem)] border text-[11px]"
                                                >
                                                    <span className="block truncate">{item.label}</span>
                                                </Badge>
                                            </TooltipTrigger>
                                            <TooltipContent className="max-w-xs text-xs break-words">{item.label}</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                    <span className="text-muted-foreground shrink-0 text-[11px] whitespace-nowrap tabular-nums">
                                        {formatCount(item.count)} claims
                                    </span>
                                </div>
                                <p className="mt-1 text-sm font-semibold tabular-nums md:text-base">{formatCurrency(item.amount)}</p>
                                <div className="bg-primary/20 mt-1 h-3 overflow-hidden rounded-full">
                                    <div
                                        className="bg-primary h-full rounded-full"
                                        style={{ width: `${totalCount > 0 ? (item.count / totalCount) * 100 : 0}%` }}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
