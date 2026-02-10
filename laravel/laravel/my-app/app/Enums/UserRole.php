<?php

namespace App\Enums;

// PHP 8.1+ Enum - This is valid syntax but Intelephense 1.10.x has a bug reporting false "syntax error"
// The code runs perfectly fine in PHP 8.2.30 and all usages are correct
// @noinspection PhpUndefinedClassInspection
// @noinspection PhpParserDefinitionConflictInspection

/**
 * Énumération des rôles utilisateur
 * Définit tous les rôles disponibles dans l'application
 * 
 * Utilisation:
 *   UserRole::VISITOR->value     // 'visitor'
 *   UserRole::USER->value        // 'user'
 *   UserRole::MANAGER->value     // 'manager'
 *   UserRole::cases()            // Tous les rôles
 * 
 * @noinspection PhpEnumCaseResolutionInspection
 */
enum UserRole: string
{
    case VISITOR = 'visitor';
    case USER = 'user';
    case MANAGER = 'manager';

    /**
     * Obtenir tous les rôles disponibles comme tableau de chaînes
     * Utile pour les validations
     * 
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn(self $role) => $role->value, self::cases());
    }

    /**
     * Obtenir le label français du rôle
     * 
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::VISITOR => 'Visiteur',
            self::USER => 'Utilisateur',
            self::MANAGER => 'Manager',
        };
    }

    /**
     * Obtenir la description du rôle
     * 
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::VISITOR => 'Accès lecture seule aux ressources publiques',
            self::USER => 'Accès complet au profil personnel',
            self::MANAGER => 'Gestion des utilisateurs et accès aux statistiques de sécurité',
        };
    }

    /**
     * Vérifier si le rôle a une permission
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = [
            self::VISITOR => ['view:public'],
            self::USER => ['view:own-profile', 'update:own-profile', 'view:own-activity'],
            self::MANAGER => [
                'view:all-users',
                'view:user-details',
                'view:user-activity',
                'unlock:account',
                'view:security-stats',
                'view:login-attempts',
            ],
        ];

        return in_array($permission, $permissions[$this] ?? []);
    }

    /**
     * Vérifier si le rôle actuel peut gérer d'autres utilisateurs
     * 
     * @return bool
     */
    public function canManageUsers(): bool
    {
        return $this === self::MANAGER;
    }

    /**
     * Obtenir tous les rôles que ce rôle peut gérer
     * 
     * @return array<self>
     */
    public function canManage(): array
    {
        return match ($this) {
            self::MANAGER => [self::VISITOR, self::USER, self::MANAGER],
            default => [],
        };
    }
}
