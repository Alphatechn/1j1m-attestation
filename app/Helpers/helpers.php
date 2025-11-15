<?php

if (!function_exists('formatDate')) {
    /**
     * Formater une date
     */
    function formatDate($date, $format = 'd/m/Y')
    {
        if (!$date) return null;
        return \Carbon\Carbon::parse($date)->format($format);
    }
}

if (!function_exists('formatDateTime')) {
    /**
     * Formater une date et heure
     */
    function formatDateTime($datetime, $format = 'd/m/Y H:i')
    {
        if (!$datetime) return null;
        return \Carbon\Carbon::parse($datetime)->format($format);
    }
}

if (!function_exists('getMoisNom')) {
    /**
     * Obtenir le nom du mois en français
     */
    function getMoisNom($numero)
    {
        $mois = [
            '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
            '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
            '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
        ];

        return $mois[str_pad($numero, 2, '0', STR_PAD_LEFT)] ?? '';
    }
}

if (!function_exists('getStatusBadge')) {
    /**
     * Obtenir le badge HTML pour un statut
     */
    function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">En attente</span>',
            'sent' => '<span class="badge bg-success">Envoyée</span>',
            'active' => '<span class="badge bg-success">Actif</span>',
            'inactive' => '<span class="badge bg-secondary">Inactif</span>',
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
    }
}

if (!function_exists('userCan')) {
    /**
     * Vérifier si l'utilisateur a une permission
     */
    function userCan($permission)
    {
        return auth()->check() && auth()->user()->can($permission);
    }
}

if (!function_exists('userHasRole')) {
    /**
     * Vérifier si l'utilisateur a un rôle
     */
    function userHasRole($role)
    {
        return auth()->check() && auth()->user()->hasRole($role);
    }
}

if (!function_exists('generateUniqueCode')) {
    /**
     * Générer un code unique
     */
    function generateUniqueCode($prefix = '', $length = 8)
    {
        return $prefix . strtoupper(\Illuminate\Support\Str::random($length));
    }
}

if (!function_exists('fileSize')) {
    /**
     * Formater la taille d'un fichier
     */
    function fileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

if (!function_exists('sanitizeFileName')) {
    /**
     * Nettoyer un nom de fichier
     */
    function sanitizeFileName($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return strtolower($filename);
    }
}

if (!function_exists('jsonResponse')) {
    /**
     * Retourner une réponse JSON standardisée
     */
    function jsonResponse($success, $message = '', $data = [], $code = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}

if (!function_exists('successResponse')) {
    /**
     * Retourner une réponse de succès
     */
    function successResponse($message = 'Opération réussie', $data = [])
    {
        return jsonResponse(true, $message, $data, 200);
    }
}

if (!function_exists('errorResponse')) {
    /**
     * Retourner une réponse d'erreur
     */
    function errorResponse($message = 'Une erreur est survenue', $code = 500)
    {
        return jsonResponse(false, $message, [], $code);
    }
}

if (!function_exists('getMonthsList')) {
    /**
     * Obtenir la liste des mois
     */
    function getMonthsList()
    {
        return [
            '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
            '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
            '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
        ];
    }
}

if (!function_exists('getYearsList')) {
    /**
     * Obtenir la liste des années (10 ans en arrière et 5 ans en avant)
     */
    function getYearsList()
    {
        $currentYear = date('Y');
        $years = [];

        for ($i = $currentYear - 10; $i <= $currentYear + 5; $i++) {
            $years[$i] = $i;
        }

        return $years;
    }
}
