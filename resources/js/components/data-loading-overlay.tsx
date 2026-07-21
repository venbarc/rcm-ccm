import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-react';
import type { ReactNode } from 'react';

interface DataLoadingOverlayProps {
    children: ReactNode;
    isLoading: boolean;
    label?: string;
    className?: string;
}

export function DataLoadingOverlay({ children, isLoading, label = 'Loading data...', className }: DataLoadingOverlayProps) {
    return (
        <div aria-busy={isLoading} className={cn('relative', className)}>
            {isLoading && (
                <div className="bg-background/70 absolute inset-0 z-20 flex min-h-32 items-center justify-center backdrop-blur-[1px]">
                    <div className="bg-background text-muted-foreground flex items-center gap-2 rounded-md border px-3 py-2 text-sm shadow-sm">
                        <Loader2 className="size-4 animate-spin" />
                        <span>{label}</span>
                    </div>
                </div>
            )}
            <div className={cn('transition-opacity duration-150', isLoading && 'pointer-events-none opacity-50')}>{children}</div>
        </div>
    );
}
