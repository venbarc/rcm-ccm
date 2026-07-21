import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Search } from 'lucide-react';
import type { ComponentProps } from 'react';

export function SearchInput({ className, ...props }: ComponentProps<typeof Input>) {
    return (
        <div className="relative">
            <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
            <Input className={cn('pl-9', className)} {...props} />
        </div>
    );
}
