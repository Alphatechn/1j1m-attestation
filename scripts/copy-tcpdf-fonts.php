<?php

// TCPDF cherche ses fichiers de police dans vendor/tecnickcom/tcpdf/fonts/.
// Les polices personnalisées (Cinzel, Montserrat, PlayfairDisplay) utilisées
// par AttestationService sont versionnées dans storage/all_font/ car
// vendor/ n'est pas suivi par git. On les recopie ici après chaque
// composer install/update pour que la génération de PDF fonctionne
// même après un clone/déploiement à neuf.

$source = __DIR__ . '/../storage/all_font';
$destination = __DIR__ . '/../vendor/tecnickcom/tcpdf/fonts';

if (!is_dir($source) || !is_dir($destination)) {
    exit(0);
}

foreach (scandir($source) as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }

    $src = $source . '/' . $file;
    if (is_file($src)) {
        copy($src, $destination . '/' . $file);
    }
}
