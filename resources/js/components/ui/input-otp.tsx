import { cn } from '@/lib/utils';
import { OTPInput, OTPInputContext } from 'input-otp';
import { Minus } from 'lucide-react';
import { ComponentPropsWithoutRef, ElementRef, forwardRef, useContext } from 'react';

const InputOTP = forwardRef<ElementRef<typeof OTPInput>, ComponentPropsWithoutRef<typeof OTPInput>>(
    ({ className, containerClassName, ...props }, ref) => (
    <OTPInput
        ref={ref}
        containerClassName={cn('flex items-center gap-2', containerClassName)}
        className={cn('disabled:cursor-not-allowed', className)}
        {...props}
    />
));

InputOTP.displayName = 'InputOTP';

function InputOTPGroup({ className, ...props }: ComponentPropsWithoutRef<'div'>) {
    return <div className={cn('flex items-center', className)} {...props} />;
}

function InputOTPSlot({
    index,
    className,
    ...props
}: ComponentPropsWithoutRef<'div'> & {
    index: number;
}) {
    const inputOTPContext = useContext(OTPInputContext);
    const slot = inputOTPContext.slots[index];

    return (
        <div
            className={cn(
                'relative flex h-10 w-10 items-center justify-center rounded-md border border-input text-sm shadow-xs transition-all',
                slot.isActive && 'z-10 ring-1 ring-ring',
                className,
            )}
            {...props}
        >
            {slot.char !== null ? slot.char : slot.placeholderChar}
            {slot.hasFakeCaret && (
                <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div className="h-4 w-px animate-caret-blink bg-foreground duration-1000" />
                </div>
            )}
        </div>
    );
}

function InputOTPSeparator(props: ComponentPropsWithoutRef<'div'>) {
    return (
        <div role="separator" {...props}>
            <Minus className="h-4 w-4" />
        </div>
    );
}

export { InputOTP, InputOTPGroup, InputOTPSlot, InputOTPSeparator };
