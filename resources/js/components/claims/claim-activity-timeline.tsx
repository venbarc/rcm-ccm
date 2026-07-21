import type { ClaimDetailActivity } from '@/components/claims/detail-types';
import { ArrowRight } from 'lucide-react';
import type { UIEvent } from 'react';

interface ClaimActivityTimelineProps {
    activities: ClaimDetailActivity[];
    hasMore: boolean;
    isLoading: boolean;
    onLoadMore: () => void;
}

const displayValue = (value: unknown) => {
    if (value === null || value === undefined || value === '') return 'N/A';
    if (typeof value === 'object') return JSON.stringify(value);

    return String(value).replaceAll('_', ' ');
};

const displayField = (field: string) => field.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const changedFields = (activity: ClaimDetailActivity) => {
    if (!activity.after) return [];

    return Object.keys(activity.after)
        .filter((field) => JSON.stringify(activity.before?.[field]) !== JSON.stringify(activity.after?.[field]))
        .map((field) => ({ field, before: activity.before?.[field], after: activity.after?.[field] }));
};

export function ClaimActivityTimeline({ activities, hasMore, isLoading, onLoadMore }: ClaimActivityTimelineProps) {
    const handleScroll = (event: UIEvent<HTMLDivElement>) => {
        const target = event.currentTarget;
        if (hasMore && !isLoading && target.scrollTop + target.clientHeight >= target.scrollHeight - 24) {
            onLoadMore();
        }
    };

    if (activities.length === 0) {
        return <p className="text-muted-foreground py-8 text-center text-sm">No activity recorded yet.</p>;
    }

    return (
        <div className="max-h-[520px] overflow-y-auto" onScroll={handleScroll}>
            <div className="space-y-1">
                {activities.map((activity) => {
                    const changes = changedFields(activity);

                    return (
                        <div className="space-y-2 py-4" key={activity.id}>
                            <p className="text-sm">
                                <span className="font-semibold">{activity.user?.name ?? 'System'}</span> Updated claim
                                {activity.cpt_code ? ` - CPT ${activity.cpt_code}` : ''}
                            </p>
                            {changes.length > 0 && (
                                <div className="text-muted-foreground space-y-1 text-sm">
                                    {changes.map((change) => (
                                        <p className="flex flex-wrap items-center gap-1.5" key={change.field}>
                                            <span className="text-foreground font-medium">{displayField(change.field)}:</span>
                                            <span className="line-through">{displayValue(change.before)}</span>
                                            <ArrowRight aria-hidden="true" className="size-3.5 shrink-0" />
                                            <span className="text-foreground">{displayValue(change.after)}</span>
                                        </p>
                                    ))}
                                </div>
                            )}
                        </div>
                    );
                })}
                {isLoading && <p className="text-muted-foreground py-3 text-center text-xs">Loading more activity...</p>}
            </div>
        </div>
    );
}
