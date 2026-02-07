<?php

/**
 * Exemple d'ItemProcessor pour exporter les données en JSON
 * 
 * Ce processeur sauvegarde les items scrapés dans un fichier JSON.
 * 
 * Pour l'utiliser :
 * 1. Copiez ce fichier dans app/ItemProcessors/
 * 2. Ajoutez-le à la propriété $itemProcessors de votre Spider
 * 3. Configurez le chemin du fichier de sortie si nécessaire
 */

namespace App\ItemProcessors;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Support\Configurable;

class JsonExportProcessor implements ItemProcessorInterface
{
    use Configurable;

    /**
     * Chemin du fichier de sortie
     */
    private string $outputPath = 'scraped_data.json';

    /**
     * Disk de stockage Laravel
     */
    private string $disk = 'local';

    /**
     * Tous les items collectés
     */
    private array $items = [];

    /**
     * Configure le processeur
     */
    public function configure(array $options): void
    {
        $this->outputPath = $options['output_path'] ?? $this->outputPath;
        $this->disk = $options['disk'] ?? $this->disk;
    }

    /**
     * Traite l'item et l'ajoute à la collection
     */
    public function processItem(ItemInterface $item): ItemInterface
    {
        // Ajouter l'item à notre collection
        $this->items[] = $item->all();

        // Sauvegarder après chaque item (mode append)
        $this->saveToFile();

        Log::info('Item ajouté au fichier JSON', [
            'count' => count($this->items),
            'file' => $this->outputPath,
        ]);

        return $item;
    }

    /**
     * Sauvegarde les items dans un fichier JSON
     */
    private function saveToFile(): void
    {
        try {
            // Lire les données existantes si le fichier existe
            $existingData = [];
            if (Storage::disk($this->disk)->exists($this->outputPath)) {
                $content = Storage::disk($this->disk)->get($this->outputPath);
                $existingData = json_decode($content, true) ?? [];
            }

            // Fusionner avec les nouvelles données
            $allData = array_merge($existingData, $this->items);

            // Encoder en JSON avec une belle mise en forme
            $json = json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Sauvegarder dans le fichier
            Storage::disk($this->disk)->put($this->outputPath, $json);

            // Vider notre collection temporaire
            $this->items = [];

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'export JSON', [
                'error' => $e->getMessage(),
                'file' => $this->outputPath,
            ]);
        }
    }
}
