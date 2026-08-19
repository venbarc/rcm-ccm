import { Button } from '@/components/ui/button';
import { Download } from 'lucide-react';
import type { DashboardPanelDateFilters } from './types';

interface SummaryExportButtonProps {
    filters: DashboardPanelDateFilters;
    panel: 'invoiced-summary' | 'credit-status-summary';
    prefix: 'invoiced' | 'credit_status';
}

/**
 * Downloads the panel exactly as rendered. The href carries the panel's own resolved
 * date range, so the CSV always matches the rows and grand total on screen.
 */
export function SummaryExportButton({ filters, panel, prefix }: SummaryExportButtonProps) {
    const params = new URLSearchParams();
    const ranges: Array<[string, string | null]> = [
        [`${prefix}_invoice_start`, filters.invoiceStart],
        [`${prefix}_invoice_end`, filters.invoiceEnd],
        [`${prefix}_service_start`, filters.serviceStart],
        [`${prefix}_service_end`, filters.serviceEnd],
    ];

    ranges.forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    const query = params.toString();

    return (
        <Button asChild size="sm" variant="outline">
            <a href={`/dashboard-export/${panel}${query ? `?${query}` : ''}`}>
                <Download />
                Export
            </a>
        </Button>
    );
}
