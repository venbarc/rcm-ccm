interface RadialMetricCardProps {
    label: string;
    value: string;
    percentage: number;
    colorClass: string;
    helperText: string;
}

export function RadialMetricCard({ label, value, percentage, colorClass, helperText }: RadialMetricCardProps) {
    const normalizedPercentage = Math.min(Math.max(percentage, 0), 100);
    const size = 128;
    const strokeWidth = 12;
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const strokeDashoffset = circumference - (normalizedPercentage / 100) * circumference;

    return (
        <div className="border-muted/60 bg-muted/20 rounded-md border p-3">
            <div className="flex justify-center">
                <div className="relative size-32">
                    <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`} className="size-32">
                        <circle
                            cx={size / 2}
                            cy={size / 2}
                            r={radius}
                            fill="none"
                            stroke="currentColor"
                            strokeWidth={strokeWidth}
                            className="text-muted/40"
                        />
                        <circle
                            cx={size / 2}
                            cy={size / 2}
                            r={radius}
                            fill="none"
                            stroke="currentColor"
                            strokeWidth={strokeWidth}
                            strokeLinecap="round"
                            strokeDasharray={`${circumference} ${circumference}`}
                            strokeDashoffset={strokeDashoffset}
                            transform={`rotate(-90 ${size / 2} ${size / 2})`}
                            className={colorClass}
                        />
                    </svg>
                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                        <p className="text-lg font-semibold">{normalizedPercentage.toFixed(1)}%</p>
                        <p className="text-muted-foreground text-[11px]">{label}</p>
                    </div>
                </div>
            </div>
            <p className="mt-2 text-center text-sm font-semibold">{value}</p>
            <p className="text-muted-foreground text-center text-xs">{helperText}</p>
        </div>
    );
}
