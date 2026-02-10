<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Roles Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des rôles utilisateur et leurs permissions associées
    |
    */

    'roles' => [
        'visitor' => [
            'label' => 'Visiteur',
            'description' => 'Accès en lecture seule, pas d\'authentification requise pour certaines routes',
            'permissions' => [
                'view:public',
            ],
        ],

        'user' => [
            'label' => 'Utilisateur',
            'description' => 'Utilisateur standard avec accès limité',
            'permissions' => [
                'view:own-profile',
                'update:own-profile',
                'view:own-activity',
            ],
        ],

        'manager' => [
            'label' => 'Manager',
            'description' => 'Manager avec permissions de gestion des utilisateurs',
            'permissions' => [
                'view:all-users',
                'view:user-details',
                'view:user-activity',
                'unlock:account',
                'view:security-stats',
                'view:login-attempts',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | Rôle par défaut attribué aux nouveaux utilisateurs
    |
    */

    'default' => 'user',

    /*
    |--------------------------------------------------------------------------
    | Super Admin Role
    |--------------------------------------------------------------------------
    |
    | Rôle administrateur (pour référence, peut être utilisé si nécessaire)
    | Note: Actuellement non utilisé, mais défini pour extensibilité future
    |
    */

    'super_admin' => 'admin',

];
