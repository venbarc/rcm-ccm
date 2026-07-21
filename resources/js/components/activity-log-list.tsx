import { DataLoadingOverlay } from '@/components/data-loading-overlay';
import { Pagination, type PaginationLink } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

export interface ActivityLogItem {
    id: number;
    action: string;
    description: string;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
    created_at: string;
    user: { name: string; email: string } | null;
    claim: { external_id: string; patient_name: string } | null;
}

interface ActivityLogListProps {
    activities: {
        data: ActivityLogItem[];
        links: PaginationLink[];
    };
    isLoading: boolean;
}

export function ActivityLogList({ activities, isLoading }: ActivityLogListProps) {
    return (
        <Card>
            <CardContent className="p-0">
                <DataLoadingOverlay isLoading={isLoading} label="Loading activity...">
                    <div className="divide-y">
                        {activities.data.length === 0 ? (
                            <p className="text-muted-foreground p-12 text-center">No activity recorded yet.</p>
                        ) : (
                            activities.data.map((activity) => (
                                <div className="grid gap-3 p-4 md:grid-cols-[170px_1fr_auto] md:items-center" key={activity.id}>
                                    <div>
                                        <p className="text-sm font-medium">{activity.user?.name ?? 'System'}</p>
                                        <p className="text-muted-foreground text-xs">{new Date(activity.created_at).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm">{activity.description}</p>
                                        {activity.claim && (
                                            <p className="text-muted-foreground text-xs">
                                                {activity.claim.external_id} - {activity.claim.patient_name}
                                            </p>
                                        )}
                                    </div>
                                    <Badge variant="outline">{activity.action.replaceAll('_', ' ')}</Badge>
                                </div>
                            ))
                        )}
                    </div>
                    <div className="border-t p-4">
                        <Pagination links={activities.links} />
                    </div>
                </DataLoadingOverlay>
            </CardContent>
        </Card>
    );
}
