<?php

/**
 * Exemple de Middleware pour ajouter des headers personnalisés
 * 
 * Ce middleware ajoute un User-Agent personnalisé et d'autres headers
 * à toutes les requêtes HTTP.
 * 
 * Pour l'utiliser :
 * 1. Copiez ce fichier dans app/Middleware/
 * 2. Ajoutez-le à la propriété $downloaderMiddleware de votre Spider
 */

namespace App\Middleware;

use RoachPHP\Downloader\Middleware\RequestMiddlewareInterface;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Support\Configurable;

class CustomHeadersMiddleware implements RequestMiddlewareInterface
{
    use Configurable;

    /**
     * User-Agent à utiliser
     */
    private string $userAgent = 'Mozilla/5.0 (compatible; MyBot/1.0; +http://example.com/bot)';

    /**
     * Headers supplémentaires
     */
    private array $headers = [];

    /**
     * Configure le middleware
     */
    public function configure(array $options): void
    {
        $this->userAgent = $options['user_agent'] ?? $this->userAgent;
        $this->headers = $options['headers'] ?? $this->headers;
    }

    /**
     * Modifie la requête avant qu'elle soit envoyée
     */
    public function handleRequest(Request $request): Request
    {
        // Ajouter le User-Agent
        $request = $request->withHeader('User-Agent', $this->userAgent);

        // Ajouter les headers supplémentaires
        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        // Headers par défaut pour simuler un navigateur
        $request = $request->withHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
        $request = $request->withHeader('Accept-Language', 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7');
        $request = $request->withHeader('Accept-Encoding', 'gzip, deflate');
        $request = $request->withHeader('Connection', 'keep-alive');

        return $request;
    }

    /**
     * Traite la réponse reçue
     */
    public function handleResponse(Response $response): Response
    {
        // On peut logger ou traiter la réponse ici si nécessaire
        return $response;
    }
}
