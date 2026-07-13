interface AlertErrorProps {
    errors: string[];
}

export default function AlertError({ errors }: AlertErrorProps) {
    if (!errors.length) {
        return null;
    }

    return (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
            <ul className="space-y-1">
                {errors.map((error, index) => (
                    <li key={`${error}-${index}`}>{error}</li>
                ))}
            </ul>
        </div>
    );
}
