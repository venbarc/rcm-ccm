import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { useCallback, useEffect, useRef, useState } from 'react';

interface TruncatedTooltipTextProps {
    className?: string;
    textClassName?: string;
    value: string;
}

export function TruncatedTooltipText({ className, textClassName, value }: TruncatedTooltipTextProps) {
    const textRef = useRef<HTMLSpanElement>(null);
    const [isTruncated, setIsTruncated] = useState(false);
    const [isOpen, setIsOpen] = useState(false);

    const measureOverflow = useCallback(() => {
        const element = textRef.current;
        const overflows = Boolean(element && element.scrollWidth > element.clientWidth + 1);
        setIsTruncated(overflows);
        if (!overflows) {
            setIsOpen(false);
        }
    }, []);

    useEffect(() => {
        measureOverflow();

        const element = textRef.current;
        if (!element || typeof ResizeObserver === 'undefined') {
            window.addEventListener('resize', measureOverflow);

            return () => window.removeEventListener('resize', measureOverflow);
        }

        const observer = new ResizeObserver(measureOverflow);
        observer.observe(element);

        return () => observer.disconnect();
    }, [measureOverflow, value]);

    const text = (
        <span className={cn('block max-w-full min-w-0 truncate', textClassName)} ref={textRef}>
            {value}
        </span>
    );

    return (
        <TooltipProvider delayDuration={250}>
            <Tooltip onOpenChange={(open) => setIsOpen(isTruncated && open)} open={isTruncated && isOpen}>
                <TooltipTrigger asChild>
                    <div className={cn('max-w-full min-w-0', isTruncated && 'cursor-help', className)}>{text}</div>
                </TooltipTrigger>
                <TooltipContent className="max-w-sm break-words whitespace-normal" side="top">
                    {value}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
