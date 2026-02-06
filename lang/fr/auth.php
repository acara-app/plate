<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines (French)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user.
    |
    */

    'login' => [
        'page_title' => 'Connexion à Acara Plate | Nutrition IA pour le diabète',
        'title' => 'Se connecter',
        'description' => 'Entrez votre adresse e-mail et votre mot de passe',
        'email' => 'Adresse e-mail',
        'email_placeholder' => 'email@example.com',
        'password' => 'Mot de passe',
        'password_placeholder' => 'Mot de passe',
        'remember_me' => 'Se souvenir de moi',
        'submit' => 'Se connecter',
        'forgot_password' => 'Mot de passe oublié?',
        'no_account' => 'Pas de compte?',
        'sign_up' => 'S\'inscrire',
        'or' => 'Ou',
    ],

    'register' => [
        'page_title' => 'Créer votre compte Acara Plate | Commencez votre parcours',
        'title' => 'Créer un compte',
        'description' => 'Entrez vos informations ci-dessous pour créer votre compte',
        'name' => 'Nom',
        'name_placeholder' => 'Nom complet',
        'email' => 'Adresse e-mail',
        'email_placeholder' => 'email@example.com',
        'password' => 'Mot de passe',
        'password_placeholder' => 'Mot de passe',
        'password_confirmation' => 'Confirmer le mot de passe',
        'password_confirmation_placeholder' => 'Confirmer le mot de passe',
        'submit' => 'Créer un compte',
        'already_have_account' => 'Vous avez déjà un compte?',
        'log_in' => 'Se connecter',
        'or' => 'Ou',
        'terms_acceptance' => 'En m\'inscrivant, je confirme que j\'ai au moins 18 ans et j\'accepte les',
        'terms_of_service' => 'Conditions d\'utilisation',
        'and' => 'et',
        'privacy_policy' => 'Politique de confidentialité',
    ],

    'password_reset' => [
        'request_page_title' => 'Réinitialiser votre mot de passe | Aide Acara Plate',
        'reset_page_title' => 'Créez un nouveau mot de passe | Acara Plate',
        'request_title' => 'Réinitialiser le mot de passe',
        'request_description' => 'Entrez votre adresse e-mail pour recevoir un lien de réinitialisation',
        'email' => 'Adresse e-mail',
        'email_placeholder' => 'email@example.com',
        'submit' => 'Envoyer le lien de réinitialisation',
        'back_to_login' => 'Retour à la connexion',
        'reset_title' => 'Réinitialiser votre mot de passe',
        'reset_description' => 'Entrez votre nouveau mot de passe',
        'password' => 'Nouveau mot de passe',
        'password_placeholder' => 'Nouveau mot de passe',
        'password_confirmation' => 'Confirmer le mot de passe',
        'password_confirmation_placeholder' => 'Confirmer le mot de passe',
        'reset_submit' => 'Réinitialiser le mot de passe',
    ],

    'two_factor' => [
        'title' => 'Authentification à deux facteurs',
        'description' => 'Veuillez confirmer l\'accès à votre compte en entrant le code d\'authentification fourni par votre application d\'authentification.',
        'code' => 'Code',
        'code_placeholder' => 'Code d\'authentification',
        'recovery_code' => 'Code de récupération',
        'recovery_code_placeholder' => 'Code de récupération',
        'use_recovery_code' => 'Utiliser un code de récupération',
        'use_authentication_code' => 'Utiliser un code d\'authentification',
        'submit' => 'Se connecter',
    ],

    'email_verification' => [
        'page_title' => 'Vérifiez votre e-mail | Sécurité Acara Plate',
        'title' => 'Vérifier votre adresse e-mail',
        'description' => 'Merci de vous être inscrit! Avant de commencer, pourriez-vous vérifier votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer? Si vous n\'avez pas reçu l\'e-mail, nous vous en enverrons un autre avec plaisir.',
        'resend' => 'Renvoyer l\'e-mail de vérification',
        'logout' => 'Se déconnecter',
        'verification_sent' => 'Un nouveau lien de vérification a été envoyé à votre adresse e-mail.',
    ],

    'password_confirmation' => [
        'title' => 'Confirmez votre mot de passe',
        'description' => 'Ceci est une zone sécurisée de l\'application. Veuillez confirmer votre mot de passe avant de continuer.',
        'page_title' => 'Confirmer le mot de passe',
        'password_label' => 'Mot de passe',
        'password_placeholder' => 'Mot de passe',
        'confirm_button' => 'Confirmer le mot de passe',
    ],

    'two_factor_settings' => [
        'title' => 'Authentification à deux facteurs',
        'description' => 'Gérez vos paramètres d\'authentification à deux facteurs',
        'enabled_badge' => 'Activé',
        'disabled_badge' => 'Désactivé',
        'enabled_description' => 'Avec l\'authentification à deux facteurs activée, vous serez invité à saisir un code PIN sécurisé et aléatoire lors de la connexion, que vous pourrez récupérer depuis l\'application compatible TOTP sur votre téléphone.',
        'disabled_description' => 'Lorsque vous activez l\'authentification à deux facteurs, vous serez invité à saisir un code PIN sécurisé lors de la connexion. Ce code PIN peut être récupéré depuis une application compatible TOTP sur votre téléphone.',
        'enable_button' => 'Activer 2FA',
        'disable_button' => 'Désactiver 2FA',
        'continue_setup' => 'Continuer la configuration',
    ],

    'two_factor_challenge' => [
        'title' => 'Authentification à deux facteurs',
        'code_title' => 'Code d\'authentification',
        'code_description' => 'Entrez le code d\'authentification fourni par votre application d\'authentification.',
        'recovery_title' => 'Code de récupération',
        'recovery_description' => 'Veuillez confirmer l\'accès à votre compte en entrant l\'un de vos codes de récupération d\'urgence.',
        'recovery_placeholder' => 'Entrez le code de récupération',
        'continue_button' => 'Continuer',
        'or_you_can' => 'ou vous pouvez',
        'use_recovery_code' => 'vous connecter avec un code de récupération',
        'use_auth_code' => 'vous connecter avec un code d\'authentification',
    ],

    'failed' => 'Identifiant ou mot de passe incorrect.',
    'password' => 'Mot de passe incorrect.',
    'throttle' => 'Trop de tentatives. Veuillez réessayer dans :seconds secondes.',
];
