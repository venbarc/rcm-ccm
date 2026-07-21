const supportedAccountThemes = new Set(['wc_health', 'tricity_pain_associates', 'principle_spine_and_pain']);

export const resolveAccountTheme = (activeAccount?: string | null) =>
    activeAccount && supportedAccountThemes.has(activeAccount) ? activeAccount : 'default';
