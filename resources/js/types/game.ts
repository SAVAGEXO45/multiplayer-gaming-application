export type GameStats = {
    games_played: number;
    wins: number;
    losses: number;
    draws: number;
    points: number;
    win_rate: number;
};

export type Game = {
    id: number;
    slug: string;
    name: string;
    tagline: string | null;
    description: string;
    instructions?: string;
    rules?: string;
    icon: string;
    accent: string;
    min_players: number;
    max_players: number;
    supports_multiplayer: boolean;
    points_win: number;
    points_draw?: number;
    points_loss?: number;
    points_participation?: number;
    difficulties?: string[];
    stats?: GameStats;
};

export type PlayerSummary = {
    id?: number;
    user_id?: number;
    username: string;
    name: string;
    avatar_url: string;
    points?: number;
    bio?: string | null;
    role?: string;
    is_ready?: boolean;
    is_host?: boolean;
};

export type LeaderboardEntry = {
    rank: number;
    user_id: number;
    username: string;
    name: string;
    avatar_url: string;
    points: number;
    wins: number;
    losses: number;
    draws: number;
    games_played: number;
    best_streak: number;
    win_rate: number;
};

export type GameResultItem = {
    id: number;
    result: 'win' | 'loss' | 'draw';
    points_awarded: number;
    score: number;
    is_multiplayer: boolean;
    created_at: string | null;
    opponent?: string | null;
    game?: {
        name: string;
        slug: string;
        icon: string;
        accent?: string;
    };
};

export type SessionState = {
    players?: number[];
    marks?: Record<string, string>;
    board?: (string | null)[];
    winning_line?: number[] | null;
    move_count?: number;
    round?: number;
    round_limit?: number;
    wins_needed?: number;
    scores?: Record<string, number>;
    ai_score?: number;
    choices?: Record<string, string>;
    history?: Array<Record<string, unknown>>;
    min?: number;
    max?: number;
    max_attempts?: number;
    attempts?: Array<{ guess: number; feedback: string; remaining: number }>;
    attempts_used?: number;
    attempts_left?: number;
    won?: boolean;
    secret_number?: number | null;
    [key: string]: unknown;
};

export type GameSession = {
    id: number;
    uuid: string;
    status: 'active' | 'finished' | 'abandoned';
    difficulty: string;
    state: SessionState;
    current_turn_user_id: number | null;
    winner_id: number | null;
    is_draw: boolean;
    game?: Game;
    room_code?: string | null;
    players?: PlayerSummary[];
    results?: Array<{
        id: number;
        result: string;
        points_awarded: number;
        score: number;
        user?: PlayerSummary;
    }>;
    started_at: string | null;
    finished_at: string | null;
    created_at: string;
};

export type RoomSummary = {
    code: string;
    name: string;
    status: 'waiting' | 'in_progress' | 'finished' | 'closed';
    max_players: number;
    is_private?: boolean;
    has_password?: boolean;
    players_count: number;
    is_member?: boolean;
    host: string | { username: string; avatar_url?: string };
    game: {
        name: string;
        slug: string;
        icon: string;
        accent?: string;
        instructions?: string;
    };
    players?: PlayerSummary[];
};

export type AchievementItem = {
    id: number;
    slug: string;
    name: string;
    description: string;
    icon: string;
    tier: string;
    criteria_type: string;
    criteria_value: number;
    points_reward: number;
    unlocked: boolean;
    unlocked_at: string | null;
    progress: number;
};

export type RewardItem = {
    id: number;
    slug: string;
    name: string;
    description: string;
    type: string;
    icon: string;
    cost: number;
    stock: number | null;
    owned: boolean;
    available: boolean;
};

export type RewardTransactionItem = {
    id: number;
    type: string;
    amount: number;
    balance_after: number;
    reason: string;
    reward?: RewardItem | null;
    created_at: string | null;
};

export type Paginator<T> = {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};
