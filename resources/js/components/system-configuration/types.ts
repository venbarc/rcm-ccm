export interface ConfigurationUser {
    id: number;
    name: string;
    email: string;
}

export interface ConfigurationOption {
    id: number;
    option_type: string;
    value: string;
    label: string;
    color: string | null;
    added_by: ConfigurationUser | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface ConfigurationSectionData {
    type: string;
    label: string;
    can_restore_defaults: boolean;
    options: ConfigurationOption[];
}
