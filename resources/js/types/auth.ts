export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    /** Voir App\Enums\RoleEnum côté backend ; null pour un utilisateur pas
     * encore rattaché à une entreprise/un rôle. */
    role: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
};

export type Auth = {
    user: User;
};
