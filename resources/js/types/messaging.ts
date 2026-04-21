export type ChatPlatform = 'telegram';

export interface ChatPlatformIntegration {
    platform: ChatPlatform;
    label: string;
    bot_username: string;
    deep_link_url: string;
    linking_command: string;
    is_connected: boolean;
    linking_token: string | null;
    token_expires_at: string | null;
    connected_at: string | null;
}
