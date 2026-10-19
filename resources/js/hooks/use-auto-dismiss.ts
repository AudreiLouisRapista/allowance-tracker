import { useEffect, useState } from 'react';

// Every flash message stays visible for 5 seconds.
export const FLASH_DURATION_MS = 5000;

export function useAutoDismiss<T>(
    value: T | null | undefined,
    duration = FLASH_DURATION_MS,
): T | null {
    const [visibleValue, setVisibleValue] = useState<T | null>(value ?? null);

    useEffect(() => {
        setVisibleValue(value ?? null);

        if (value === null || value === undefined) {
            return;
        }

        const timerId = setTimeout(
            () => setVisibleValue(null),
            duration,
        );

        return () => clearTimeout(timerId);
    }, [value, duration]);

    return visibleValue;
}
