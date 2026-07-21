import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export function useInertiaLoading() {
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        const removeStartListener = router.on('start', () => setIsLoading(true));
        const removeFinishListener = router.on('finish', () => setIsLoading(false));

        return () => {
            removeStartListener();
            removeFinishListener();
        };
    }, []);

    return isLoading;
}
